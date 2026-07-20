<?php

namespace App\Services\Proposal;

use App\Models\Company;
use App\Models\Proposal;
use App\Models\ProposalCatalog;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProposalCatalogService
{
    public function get(int $proposalId): array
    {
        try {
            $proposal = $this->findUserProposal($proposalId);
            $catalog = $this->ensureCatalog($proposal);

            return ['status' => true, 'data' => $this->payload($catalog)];
        } catch (ModelNotFoundException) {
            return ['status' => false, 'error' => 'Proposta não encontrada.', 'statusCode' => 404];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function update(Request $request, int $proposalId): array
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => ['sometimes', 'required', 'string', 'max:255'],
                'subtitle' => ['sometimes', 'nullable', 'string', 'max:255'],
                'general_notes' => ['sometimes', 'nullable', 'string'],
                'organ_name' => ['sometimes', 'nullable', 'string', 'max:255'],
                'organ_state' => ['sometimes', 'nullable', 'string', 'max:255'],
                'purchase_number' => ['sometimes', 'nullable', 'string', 'max:255'],
                'process_number' => ['sometimes', 'nullable', 'string', 'max:255'],
                'receipt_date' => ['sometimes', 'nullable', 'date'],
                'opening_date' => ['sometimes', 'nullable', 'date'],
                'items' => ['sometimes', 'array'],
                'items.*.id' => ['nullable', 'integer', 'distinct', 'exists:proposal_catalog_items,id'],
                'items.*.proposal_item_id' => ['nullable', 'integer', 'distinct', 'exists:proposal_items,id'],
                'items.*.title' => ['nullable', 'string', 'max:255'],
                'items.*.specification' => ['nullable', 'string'],
                'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
                'items.*.unit' => ['nullable', 'string', 'max:255'],
                'items.*.brand' => ['nullable', 'string', 'max:255'],
                'items.*.position' => ['nullable', 'integer', 'min:1', 'distinct'],
            ]);

            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }

            $proposal = $this->findUserProposal($proposalId);
            $catalog = $this->ensureCatalog($proposal);
            $data = $validator->validated();
            $submittedItems = collect($data['items'] ?? []);

            if (array_key_exists('items', $data)) {
                $catalogItems = $catalog->items()->get()->keyBy('id');
                $catalogItemIds = $catalogItems->keys();
                $proposalItemIds = $proposal->items()->pluck('id');

                if ($submittedItems->pluck('id')->filter()->contains(fn ($id) => ! $catalogItemIds->contains($id))) {
                    return [
                        'status' => false,
                        'error' => 'Um ou mais itens não pertencem ao catálogo informado.',
                        'statusCode' => 400,
                    ];
                }

                if ($submittedItems->pluck('proposal_item_id')->filter()->contains(fn ($id) => ! $proposalItemIds->contains($id))) {
                    return [
                        'status' => false,
                        'error' => 'Um ou mais itens de origem não pertencem à proposta informada.',
                        'statusCode' => 400,
                    ];
                }

                foreach ($submittedItems->whereNotNull('id') as $submittedItem) {
                    $catalogItem = $catalogItems->get($submittedItem['id']);

                    if (
                        array_key_exists('proposal_item_id', $submittedItem)
                        && $submittedItem['proposal_item_id'] !== $catalogItem->proposal_item_id
                    ) {
                        return [
                            'status' => false,
                            'error' => 'O item de origem de um item existente não pode ser alterado.',
                            'statusCode' => 400,
                        ];
                    }
                }
            }

            $imagesToDelete = [];

            DB::transaction(function () use ($catalog, $data, $submittedItems, &$imagesToDelete): void {
                $catalog->update(array_merge(
                    $this->catalogAttributes($data),
                    [
                        'last_updated_by' => Auth::id(),
                        'generated_at' => null,
                    ]
                ));

                if (! array_key_exists('items', $data)) {
                    return;
                }

                $existingItems = $catalog->items()->get()->keyBy('id');

                // Libera temporariamente as posições para permitir trocas sem
                // violar a restrição única durante a mesma transação.
                $catalog->items()->update(['position' => DB::raw('position + 1000000')]);

                $orderedItems = $submittedItems
                    ->values()
                    ->sortBy(fn (array $item, int $index) => $item['position'] ?? (1000000 + $index))
                    ->values();
                $keptIds = [];

                foreach ($orderedItems as $index => $itemData) {
                    $attributes = [
                        'title' => $itemData['title'] ?? null,
                        'specification' => $itemData['specification'] ?? null,
                        'quantity' => $itemData['quantity'] ?? null,
                        'unit' => $itemData['unit'] ?? null,
                        'brand' => $itemData['brand'] ?? null,
                        'position' => $index + 1,
                    ];

                    if (! empty($itemData['id'])) {
                        $item = $existingItems->get($itemData['id'])->refresh();
                        $attributes['proposal_item_id'] = $item->proposal_item_id;
                        $item->update($attributes);
                    } else {
                        $attributes['proposal_item_id'] = $itemData['proposal_item_id'] ?? null;
                        $item = $catalog->items()->create($attributes);
                    }

                    $keptIds[] = $item->id;
                }

                $removedItems = $catalog->items()->whereNotIn('id', $keptIds)->get();
                $imagesToDelete = $removedItems->pluck('image_path')->filter()->all();
                $catalog->items()->whereNotIn('id', $keptIds)->delete();
            });

            foreach ($imagesToDelete as $imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            return ['status' => true, 'data' => $this->payload($catalog->fresh())];
        } catch (ModelNotFoundException) {
            return ['status' => false, 'error' => 'Proposta não encontrada.', 'statusCode' => 404];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function uploadImage(Request $request, int $proposalId, int $itemId): array
    {
        try {
            $validator = Validator::make($request->all(), [
                'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ]);

            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }

            $proposal = $this->findUserProposal($proposalId);
            $catalog = $this->ensureCatalog($proposal);
            $item = $catalog->items()->findOrFail($itemId);
            $image = $request->file('image');
            $oldPath = $item->image_path;
            $path = $image->store("proposal-catalogs/{$catalog->id}", 'public');

            try {
                $item->update([
                    'image_path' => $path,
                    'image_original_name' => $image->getClientOriginalName(),
                    'image_mime' => $image->getMimeType(),
                ]);
                $catalog->update([
                    'last_updated_by' => Auth::id(),
                    'generated_at' => null,
                ]);
            } catch (Exception $error) {
                Storage::disk('public')->delete($path);
                throw $error;
            }

            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }

            return ['status' => true, 'data' => $item->fresh()];
        } catch (ModelNotFoundException) {
            return ['status' => false, 'error' => 'Proposta, catálogo ou item não encontrado.', 'statusCode' => 404];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function deleteImage(int $proposalId, int $itemId): array
    {
        try {
            $proposal = $this->findUserProposal($proposalId);
            $catalog = $this->ensureCatalog($proposal);
            $item = $catalog->items()->findOrFail($itemId);
            $oldPath = $item->image_path;

            $item->update([
                'image_path' => null,
                'image_original_name' => null,
                'image_mime' => null,
            ]);
            $catalog->update([
                'last_updated_by' => Auth::id(),
                'generated_at' => null,
            ]);

            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }

            return ['status' => true, 'data' => $item->fresh()];
        } catch (ModelNotFoundException) {
            return ['status' => false, 'error' => 'Proposta, catálogo ou item não encontrado.', 'statusCode' => 404];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function generate(int $proposalId): array
    {
        try {
            $proposal = $this->findUserProposal($proposalId);
            $catalog = $this->ensureCatalog($proposal);
            $catalog->update([
                'generated_at' => now(),
                'last_updated_by' => Auth::id(),
            ]);

            return ['status' => true, 'data' => $this->payload($catalog->fresh())];
        } catch (ModelNotFoundException) {
            return ['status' => false, 'error' => 'Proposta não encontrada.', 'statusCode' => 404];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function view(int $catalogId): array
    {
        try {
            $catalog = ProposalCatalog::where('user_id', Auth::id())->findOrFail($catalogId);

            return ['status' => true, 'data' => $this->payload($catalog)];
        } catch (ModelNotFoundException) {
            return ['status' => false, 'error' => 'Catálogo não encontrado.', 'statusCode' => 404];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    private function findUserProposal(int $proposalId): Proposal
    {
        return Proposal::where('user_id', Auth::id())->findOrFail($proposalId);
    }

    private function ensureCatalog(Proposal $proposal): ProposalCatalog
    {
        return DB::transaction(function () use ($proposal): ProposalCatalog {
            $proposal = Proposal::whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            $existing = $proposal->catalog()->first();

            if ($existing) {
                return $existing;
            }

            $company = $proposal->company;
            $catalog = $proposal->catalog()->create([
                'user_id' => $proposal->user_id,
                'company_id' => $proposal->company_id,
                'title' => 'Catálogo de Produtos',
                'organ_name' => $proposal->organ_name,
                'organ_state' => $proposal->organ_state,
                'purchase_number' => $proposal->purchase_number,
                'process_number' => $proposal->process_number,
                'receipt_date' => $proposal->receipt_date,
                'opening_date' => $proposal->opening_date,
                'company_snapshot' => $company
                    ? $this->companySnapshot($company)
                    : $proposal->company_snapshot,
                'last_updated_by' => Auth::id(),
            ]);

            $items = $proposal->items()->orderBy('id')->get()->values()->map(
                fn ($item, int $index) => [
                    'proposal_item_id' => $item->id,
                    'title' => $item->specification,
                    'specification' => $item->specification,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'brand' => $item->brand,
                    'position' => $index + 1,
                ]
            )->all();

            $catalog->items()->createMany($items);

            return $catalog;
        });
    }

    private function payload(ProposalCatalog $catalog): ProposalCatalog
    {
        return $catalog->load(['items', 'proposal:id,title,status', 'company:id,corporate_reason,fantasy_name']);
    }

    private function catalogAttributes(array $data): array
    {
        return collect($data)->only([
            'title',
            'subtitle',
            'general_notes',
            'organ_name',
            'organ_state',
            'purchase_number',
            'process_number',
            'receipt_date',
            'opening_date',
        ])->all();
    }

    private function companySnapshot(Company $company): array
    {
        return $company->only([
            'id',
            'cnpj',
            'corporate_reason',
            'fantasy_name',
            'street',
            'number',
            'complement',
            'neighborhood',
            'city',
            'state',
            'zipcode',
            'phone',
            'email',
            'logo',
        ]);
    }
}
