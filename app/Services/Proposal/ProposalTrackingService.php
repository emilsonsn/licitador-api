<?php

namespace App\Services\Proposal;

use App\Enums\ProposalTrackingItemResult;
use App\Enums\ProposalTrackingStatus;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\ProposalTracking;
use App\Models\ProposalTrackingItem;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class ProposalTrackingService
{
    public function get(int $proposalId): array
    {
        try {
            $proposal = $this->findUserProposal($proposalId);
            $tracking = $this->ensureTracking($proposal);

            return ['status' => true, 'data' => $this->payload($proposal, $tracking)];
        } catch (ModelNotFoundException) {
            return ['status' => false, 'error' => 'Proposta não encontrada.', 'statusCode' => 404];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function update($request, int $proposalId): array
    {
        try {
            $validator = Validator::make($request->all(), [
                'discount_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
                'items' => ['sometimes', 'array'],
                'items.*.proposal_item_id' => ['required', 'integer', 'distinct', 'exists:proposal_items,id'],
                'items.*.result' => ['sometimes', new Enum(ProposalTrackingItemResult::class)],
                'items.*.minimum_unit_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
                'items.*.rankings' => ['sometimes', 'array', 'max:3'],
                'items.*.rankings.*.position' => ['required', 'integer', 'between:1,3'],
                'items.*.rankings.*.company' => ['required', 'string', 'max:255'],
                'items.*.rankings.*.brand' => ['nullable', 'string', 'max:255'],
                'items.*.rankings.*.price' => ['required', 'numeric', 'min:0'],
            ]);

            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }

            $proposal = $this->findUserProposal($proposalId);
            $tracking = $this->ensureTracking($proposal);

            if ($tracking->status === ProposalTrackingStatus::Finished) {
                return [
                    'status' => false,
                    'error' => 'O acompanhamento está finalizado. Reabra-o antes de editar.',
                    'statusCode' => 409,
                ];
            }

            $data = $validator->validated();
            $submittedItems = collect($data['items'] ?? []);
            $proposalItemIds = $proposal->items()->pluck('id');

            if ($submittedItems->contains(fn ($item) => ! $proposalItemIds->contains($item['proposal_item_id']))) {
                return [
                    'status' => false,
                    'error' => 'Um ou mais itens não pertencem à proposta informada.',
                    'statusCode' => 400,
                ];
            }

            $trackingItems = $tracking->items()
                ->with('rankings')
                ->whereIn('proposal_item_id', $submittedItems->pluck('proposal_item_id'))
                ->get()
                ->keyBy('proposal_item_id');

            foreach ($submittedItems as $submittedItem) {
                $trackingItem = $trackingItems->get($submittedItem['proposal_item_id']);
                $result = $submittedItem['result'] ?? $trackingItem->result->value;
                $rankingCount = array_key_exists('rankings', $submittedItem)
                    ? count($submittedItem['rankings'])
                    : $trackingItem->rankings->count();

                if (array_key_exists('rankings', $submittedItem)) {
                    $positions = collect($submittedItem['rankings'])->pluck('position');

                    if ($positions->unique()->count() !== $positions->count()) {
                        return [
                            'status' => false,
                            'error' => 'As posições da classificação não podem se repetir no mesmo item.',
                            'statusCode' => 400,
                        ];
                    }
                }

                if (
                    $result === ProposalTrackingItemResult::Pending->value
                    && array_key_exists('rankings', $submittedItem)
                    && count($submittedItem['rankings']) > 0
                ) {
                    return [
                        'status' => false,
                        'error' => 'Itens pendentes não podem possuir classificação.',
                        'statusCode' => 400,
                    ];
                }

                if ($result !== ProposalTrackingItemResult::Pending->value && $rankingCount === 0) {
                    return [
                        'status' => false,
                        'error' => 'Informe ao menos uma posição ao classificar um item como vencedor ou perdido.',
                        'statusCode' => 400,
                    ];
                }
            }

            DB::transaction(function () use ($tracking, $data, $submittedItems, $trackingItems) {
                if (array_key_exists('discount_percentage', $data)) {
                    $tracking->discount_percentage = $data['discount_percentage'];
                }

                $tracking->last_updated_by = Auth::id();
                $tracking->save();

                foreach ($submittedItems as $submittedItem) {
                    $trackingItem = $trackingItems->get($submittedItem['proposal_item_id']);

                    if (! $trackingItem) {
                        continue;
                    }

                    if (array_key_exists('minimum_unit_price', $submittedItem)) {
                        $trackingItem->minimum_unit_price = $submittedItem['minimum_unit_price'];
                    }

                    if (array_key_exists('result', $submittedItem)) {
                        $trackingItem->result = $submittedItem['result'];

                        if ($submittedItem['result'] === ProposalTrackingItemResult::Pending->value) {
                            $trackingItem->classified_at = null;
                            $trackingItem->classified_by = null;
                            $trackingItem->rankings()->delete();
                        } else {
                            $trackingItem->classified_at = now();
                            $trackingItem->classified_by = Auth::id();
                        }
                    }

                    if (array_key_exists('rankings', $submittedItem)) {
                        $trackingItem->rankings()->delete();
                        $trackingItem->rankings()->createMany(
                            collect($submittedItem['rankings'])
                                ->sortBy('position')
                                ->values()
                                ->all()
                        );
                    }

                    $trackingItem->save();
                }
            });

            return [
                'status' => true,
                'data' => $this->payload($proposal->fresh(), $tracking->fresh()),
            ];
        } catch (ModelNotFoundException) {
            return ['status' => false, 'error' => 'Proposta não encontrada.', 'statusCode' => 404];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function applyDiscount($request, int $proposalId): array
    {
        try {
            $validator = Validator::make($request->all(), [
                'discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            ]);

            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }

            $proposal = $this->findUserProposal($proposalId);
            $tracking = $this->ensureTracking($proposal);

            if ($tracking->status === ProposalTrackingStatus::Finished) {
                return [
                    'status' => false,
                    'error' => 'O acompanhamento está finalizado. Reabra-o antes de editar.',
                    'statusCode' => 409,
                ];
            }

            $percentage = $validator->validated()['discount_percentage'];

            DB::transaction(function () use ($tracking, $percentage) {
                $tracking->update([
                    'discount_percentage' => $percentage,
                    'last_updated_by' => Auth::id(),
                ]);

                $tracking->load('items.proposalItem');

                foreach ($tracking->items as $trackingItem) {
                    $trackingItem->minimum_unit_price = $this->discountedUnitPrice(
                        $trackingItem->proposalItem->unit_price,
                        $percentage
                    );
                    $trackingItem->save();
                }
            });

            return [
                'status' => true,
                'data' => $this->payload($proposal->fresh(), $tracking->fresh()),
            ];
        } catch (ModelNotFoundException) {
            return ['status' => false, 'error' => 'Proposta não encontrada.', 'statusCode' => 404];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function finish(int $proposalId): array
    {
        return $this->changeStatus($proposalId, ProposalTrackingStatus::Finished);
    }

    public function reopen(int $proposalId): array
    {
        return $this->changeStatus($proposalId, ProposalTrackingStatus::Open);
    }

    public function printData(int $proposalId): array
    {
        $result = $this->get($proposalId);

        if (! $result['status']) {
            return $result;
        }

        $result['data']['document'] = [
            'title' => 'Acompanhamento de Licitação',
            'generated_at' => now()->toISOString(),
            'print_css_hint' => 'Ocultar controles interativos e imprimir o conteúdo em formato A4.',
        ];

        return $result;
    }

    public function export(int $proposalId): array
    {
        $result = $this->get($proposalId);

        if (! $result['status']) {
            return $result;
        }

        $stream = fopen('php://temp', 'w+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, [
            'Item',
            'Resultado',
            'Quantidade',
            'Unidade',
            'Especificação',
            'Marca',
            'Preço unitário',
            'Preço unitário mínimo',
            'Valor total',
            'Valor total mínimo',
            'Empresa 1º lugar',
            'Marca 1º lugar',
            'Preço 1º lugar',
            'Empresa 2º lugar',
            'Marca 2º lugar',
            'Preço 2º lugar',
            'Empresa 3º lugar',
            'Marca 3º lugar',
            'Preço 3º lugar',
        ], ';');

        foreach ($result['data']['items'] as $item) {
            $rankings = collect($item['rankings'])->keyBy('position');
            fputcsv($stream, [
                $item['item'],
                $item['result'],
                $item['quantity'],
                $item['unit'],
                $item['specification'],
                $item['brand'],
                $item['unit_price'],
                $item['minimum_unit_price'],
                $item['total_value'],
                $item['minimum_total_value'],
                $rankings->get(1)['company'] ?? null,
                $rankings->get(1)['brand'] ?? null,
                $rankings->get(1)['price'] ?? null,
                $rankings->get(2)['company'] ?? null,
                $rankings->get(2)['brand'] ?? null,
                $rankings->get(2)['price'] ?? null,
                $rankings->get(3)['company'] ?? null,
                $rankings->get(3)['brand'] ?? null,
                $rankings->get(3)['price'] ?? null,
            ], ';');
        }

        fputcsv($stream, [], ';');
        fputcsv($stream, ['Total original', $result['data']['totals']['original']], ';');
        fputcsv($stream, ['Total mínimo', $result['data']['totals']['minimum']], ';');
        fputcsv($stream, ['Total ganho', $result['data']['totals']['won']], ';');

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return [
            'status' => true,
            'data' => [
                'filename' => "acompanhamento-proposta-{$proposalId}.csv",
                'content' => $content,
            ],
        ];
    }

    private function changeStatus(int $proposalId, ProposalTrackingStatus $status): array
    {
        try {
            $proposal = $this->findUserProposal($proposalId);
            $tracking = $this->ensureTracking($proposal);
            $tracking->update([
                'status' => $status->value,
                'last_updated_by' => Auth::id(),
                'finished_at' => $status === ProposalTrackingStatus::Finished ? now() : null,
            ]);

            return ['status' => true, 'data' => $this->payload($proposal, $tracking->fresh())];
        } catch (ModelNotFoundException) {
            return ['status' => false, 'error' => 'Proposta não encontrada.', 'statusCode' => 404];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    private function ensureTracking(Proposal $proposal): ProposalTracking
    {
        return DB::transaction(function () use ($proposal) {
            $tracking = ProposalTracking::firstOrCreate(
                ['proposal_id' => $proposal->id],
                [
                    'status' => ProposalTrackingStatus::Open->value,
                    'last_updated_by' => Auth::id(),
                ]
            );

            $existingItemIds = $tracking->items()->pluck('proposal_item_id');
            $missingItems = $proposal->items()->whereNotIn('id', $existingItemIds)->get();

            if ($missingItems->isNotEmpty()) {
                $tracking->items()->createMany($missingItems->map(fn (ProposalItem $item) => [
                    'proposal_item_id' => $item->id,
                    'result' => ProposalTrackingItemResult::Pending->value,
                ])->all());
            }

            return $tracking;
        });
    }

    private function payload(Proposal $proposal, ProposalTracking $tracking): array
    {
        $proposal->loadMissing(['items', 'company', 'tender']);
        $tracking->load(['items.proposalItem', 'items.rankings']);

        $trackingByProposalItem = $tracking->items->keyBy('proposal_item_id');
        $items = $proposal->items->map(function (ProposalItem $proposalItem) use ($trackingByProposalItem) {
            /** @var ProposalTrackingItem $trackingItem */
            $trackingItem = $trackingByProposalItem->get($proposalItem->id);
            $minimumTotal = $this->multiplyDecimals(
                $proposalItem->quantity,
                $trackingItem?->minimum_unit_price,
                2
            );

            return [
                'proposal_item_id' => $proposalItem->id,
                'item' => $proposalItem->item,
                'quantity' => $proposalItem->quantity,
                'unit' => $proposalItem->unit,
                'specification' => $proposalItem->specification,
                'brand' => $proposalItem->brand,
                'unit_price' => $proposalItem->unit_price,
                'total_value' => $proposalItem->total_value,
                'result' => $trackingItem?->result?->value ?? ProposalTrackingItemResult::Pending->value,
                'minimum_unit_price' => $trackingItem?->minimum_unit_price,
                'minimum_total_value' => $minimumTotal,
                'rankings' => ($trackingItem?->rankings ?? collect())
                    ->sortBy('position')
                    ->map(fn ($ranking) => [
                        'id' => $ranking->id,
                        'position' => $ranking->position,
                        'company' => $ranking->company,
                        'brand' => $ranking->brand,
                        'price' => $ranking->price,
                    ])
                    ->values()
                    ->all() ?? [],
                'classified_at' => $trackingItem?->classified_at?->toISOString(),
                'classified_by' => $trackingItem?->classified_by,
            ];
        })->values();

        $minimumTotal = $items->sum(fn ($item) => (float) ($item['minimum_total_value'] ?? 0));
        $wonTotal = $items
            ->where('result', ProposalTrackingItemResult::Won->value)
            ->sum(fn ($item) => (float) ($item['minimum_total_value'] ?? 0));

        return [
            'proposal' => $proposal,
            'company' => $proposal->company_snapshot ?? $proposal->company,
            'tender' => $proposal->tender_snapshot ?? $proposal->tender,
            'tracking' => [
                'id' => $tracking->id,
                'status' => $tracking->status->value,
                'discount_percentage' => $tracking->discount_percentage,
                'last_updated_by' => $tracking->last_updated_by,
                'finished_at' => $tracking->finished_at?->toISOString(),
                'created_at' => $tracking->created_at?->toISOString(),
                'updated_at' => $tracking->updated_at?->toISOString(),
            ],
            'items' => $items->all(),
            'totals' => [
                'original' => $this->formatDecimal($proposal->total_value ?? $items->sum('total_value'), 2),
                'minimum' => $this->formatDecimal($minimumTotal, 2),
                'won' => $this->formatDecimal($wonTotal, 2),
            ],
            'won_items' => $items
                ->where('result', ProposalTrackingItemResult::Won->value)
                ->values()
                ->all(),
            'declarations' => $proposal->declarations,
            'signature' => [
                'responsible_name' => $proposal->responsible_name,
                'responsible_rg' => $proposal->responsible_rg,
                'responsible_cpf' => $proposal->responsible_cpf,
                'city' => $proposal->city,
                'proposal_date' => $proposal->proposal_date?->format('Y-m-d'),
            ],
        ];
    }

    private function findUserProposal(int $proposalId): Proposal
    {
        return Proposal::query()
            ->where('user_id', Auth::id())
            ->findOrFail($proposalId);
    }

    private function discountedUnitPrice($unitPrice, $percentage): ?string
    {
        if ($unitPrice === null) {
            return null;
        }

        $unitPriceScaled = $this->decimalToScaledInteger($unitPrice, 4);
        $percentageScaled = $this->decimalToScaledInteger($percentage, 4);
        $factorScaled = 1000000 - $percentageScaled;
        $discounted = (int) round(($unitPriceScaled * $factorScaled) / 1000000);

        return $this->scaledIntegerToDecimal($discounted, 4);
    }

    private function multiplyDecimals($left, $right, int $resultScale): ?string
    {
        if ($left === null || $right === null) {
            return null;
        }

        $leftScaled = $this->decimalToScaledInteger($left, 4);
        $rightScaled = $this->decimalToScaledInteger($right, 4);
        $divisor = 10 ** (8 - $resultScale);
        $result = (int) round(($leftScaled * $rightScaled) / $divisor);

        return $this->scaledIntegerToDecimal($result, $resultScale);
    }

    private function decimalToScaledInteger($value, int $scale): int
    {
        $normalized = number_format((float) $value, $scale, '.', '');
        $negative = str_starts_with($normalized, '-');
        $normalized = ltrim($normalized, '-');
        [$integer, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        $scaled = (int) ($integer.str_pad(substr($fraction, 0, $scale), $scale, '0'));

        return $negative ? -$scaled : $scaled;
    }

    private function scaledIntegerToDecimal(int $value, int $scale): string
    {
        $negative = $value < 0;
        $digits = str_pad((string) abs($value), $scale + 1, '0', STR_PAD_LEFT);
        $decimal = substr($digits, 0, -$scale).'.'.substr($digits, -$scale);

        return ($negative ? '-' : '').$decimal;
    }

    private function formatDecimal($value, int $scale): string
    {
        return number_format((float) $value, $scale, '.', '');
    }
}
