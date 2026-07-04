<?php

namespace App\Services\Proposal;

use App\Enums\ProposalStatus;
use App\Models\Company;
use App\Models\Proposal;
use App\Models\Tender;
use App\Services\Tender\TenderService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class ProposalService
{
    public function __construct(private TenderService $tenderService)
    {
    }

    public function search($request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->input('take', 10);

            $proposals = Proposal::query()
                ->with(['company', 'tender'])
                ->where('user_id', $user->id);

            if ($request->filled('search')) {
                $search = $request->input('search');

                $proposals->where(function ($query) use ($search) {
                    $query->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('organ_name', 'LIKE', "%{$search}%")
                        ->orWhere('purchase_number', 'LIKE', "%{$search}%")
                        ->orWhere('process_number', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('tender_id')) {
                $proposals->where('tender_id', $request->input('tender_id'));
            }

            if ($request->filled('company_id')) {
                $proposals->where('company_id', $request->input('company_id'));
            }

            if ($request->filled('status')) {
                $proposals->where('status', $request->input('status'));
            }

            return ['status' => true, 'data' => $proposals->latest()->paginate($perPage)];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function get(int $id)
    {
        try {
            $proposal = $this->findUserProposal($id)->load(['items', 'company', 'tender']);

            return ['status' => true, 'data' => $proposal];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 404];
        }
    }

    public function fill($request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tender_id' => ['required', 'integer', 'exists:tenders,id'],
            ]);

            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }

            $user = Auth::user();
            $company = Company::where('user_id', $user->id)->first();

            if (! $company) {
                return [
                    'status' => false,
                    'error' => 'É necessário cadastrar uma empresa antes de gerar proposta.',
                    'statusCode' => 400,
                ];
            }

            $tender = Tender::findOrFail($request->input('tender_id'));
            $itemsResult = $this->tenderService->items($tender->id);
            $warning = null;

            if (! ($itemsResult['status'] ?? false)) {
                $items = [];
                $warning = $itemsResult['error'] ?? 'Não foi possível buscar os itens da licitação.';
            } else {
                $items = $this->normalizeItems($itemsResult['data'] ?? [], $tender->api_origin);
            }

            $data = [
                'company' => $company,
                'tender' => $tender,
                'proposal' => $this->buildProposalPayload($company, $tender),
                'items' => $items,
            ];

            if ($warning) {
                $data['warning'] = $warning;
            }

            return ['status' => true, 'data' => $data];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function create($request)
    {
        try {
            $validator = $this->proposalValidator($request->all());

            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }

            $user = Auth::user();
            $data = $validator->validated();
            $company = Company::where('user_id', $user->id)->find($data['company_id'] ?? null);
            $tender = Tender::findOrFail($data['tender_id']);

            if (! $company) {
                return [
                    'status' => false,
                    'error' => 'É necessário cadastrar uma empresa antes de salvar proposta.',
                    'statusCode' => 400,
                ];
            }

            $proposal = DB::transaction(function () use ($data, $user, $company, $tender) {
                $items = $this->prepareItems($data['items'] ?? []);

                $proposal = Proposal::create(array_merge(
                    $this->proposalAttributes($data),
                    [
                        'user_id' => $user->id,
                        'company_id' => $company->id,
                        'tender_id' => $tender->id,
                        'company_snapshot' => $this->companySnapshot($company),
                        'tender_snapshot' => $this->tenderSnapshot($tender),
                        'total_value' => $this->sumItems($items),
                    ]
                ));

                $proposal->items()->createMany($items);

                return $proposal->load(['items', 'company', 'tender']);
            });

            return ['status' => true, 'data' => $proposal];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function update($request, int $id)
    {
        try {
            $proposal = $this->findUserProposal($id);
            $validator = $this->proposalValidator($request->all(), false);

            if ($validator->fails()) {
                return ['status' => false, 'error' => $validator->errors(), 'statusCode' => 400];
            }

            $data = $validator->validated();

            DB::transaction(function () use ($proposal, $data) {
                $items = $this->prepareItems($data['items'] ?? []);

                $proposal->update(array_merge(
                    $this->proposalAttributes($data, false),
                    ['total_value' => $this->sumItems($items)]
                ));

                $proposal->items()->delete();
                $proposal->items()->createMany($items);
            });

            return ['status' => true, 'data' => $proposal->fresh()->load(['items', 'company', 'tender'])];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function delete(int $id)
    {
        try {
            $proposal = $this->findUserProposal($id);
            $proposal->delete();

            return ['status' => true, 'data' => $proposal->id];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 404];
        }
    }

    public function view(int $id)
    {
        try {
            $proposal = $this->findUserProposal($id)->load('items');

            return [
                'status' => true,
                'data' => [
                    'proposal' => $proposal,
                    'company' => $proposal->company_snapshot,
                    'tender' => $proposal->tender_snapshot,
                    'items' => $proposal->items,
                    'declarations' => $proposal->declarations,
                    'signature' => [
                        'responsible_name' => $proposal->responsible_name,
                        'responsible_rg' => $proposal->responsible_rg,
                        'responsible_cpf' => $proposal->responsible_cpf,
                        'city' => $proposal->city,
                        'proposal_date' => $proposal->proposal_date,
                    ],
                ],
            ];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 404];
        }
    }

    private function findUserProposal(int $id): Proposal
    {
        return Proposal::where('user_id', Auth::user()->id)->findOrFail($id);
    }

    private function proposalValidator(array $data, bool $creating = true)
    {
        return Validator::make($data, [
            'company_id' => [$creating ? 'required' : 'sometimes', 'integer', 'exists:companies,id'],
            'tender_id' => [$creating ? 'required' : 'sometimes', 'integer', 'exists:tenders,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'organ_name' => ['nullable', 'string', 'max:255'],
            'organ_state' => ['nullable', 'string', 'max:255'],
            'purchase_number' => ['nullable', 'string', 'max:255'],
            'process_number' => ['nullable', 'string', 'max:255'],
            'receipt_date' => ['nullable', 'date'],
            'opening_date' => ['nullable', 'date'],
            'declarations' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'proposal_date' => ['nullable', 'date'],
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'responsible_rg' => ['nullable', 'string', 'max:255'],
            'responsible_cpf' => ['nullable', 'string', 'max:14'],
            'status' => ['nullable', new Enum(ProposalStatus::class)],
            'items' => ['nullable', 'array'],
            'items.*.item' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:255'],
            'items.*.specification' => ['nullable', 'string'],
            'items.*.brand' => ['nullable', 'string', 'max:255'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.source_payload' => ['nullable', 'array'],
        ]);
    }

    private function proposalAttributes(array $data, bool $withDefaults = true): array
    {
        $attributes = [
            'title' => $data['title'] ?? null,
            'organ_name' => $data['organ_name'] ?? null,
            'organ_state' => $data['organ_state'] ?? null,
            'purchase_number' => $data['purchase_number'] ?? null,
            'process_number' => $data['process_number'] ?? null,
            'receipt_date' => $data['receipt_date'] ?? null,
            'opening_date' => $data['opening_date'] ?? null,
            'declarations' => $data['declarations'] ?? null,
            'city' => $data['city'] ?? null,
            'proposal_date' => $data['proposal_date'] ?? now()->toDateString(),
            'responsible_name' => $data['responsible_name'] ?? null,
            'responsible_rg' => $data['responsible_rg'] ?? null,
            'responsible_cpf' => $data['responsible_cpf'] ?? null,
            'status' => $data['status'] ?? ($withDefaults ? ProposalStatus::Draft->value : null),
        ];

        if ($withDefaults) {
            return $attributes;
        }

        return array_filter(
            $attributes,
            fn ($value, $key) => array_key_exists($key, $data),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function prepareItems(array $items): array
    {
        return array_map(function ($item) {
            $quantity = $this->decimal($item['quantity'] ?? null);
            $unitPrice = $this->decimal($item['unit_price'] ?? null);

            return [
                'item' => $item['item'] ?? null,
                'quantity' => $quantity,
                'unit' => $item['unit'] ?? null,
                'specification' => $item['specification'] ?? null,
                'brand' => $item['brand'] ?? null,
                'unit_price' => $unitPrice,
                'total_value' => $quantity !== null && $unitPrice !== null ? round($quantity * $unitPrice, 2) : null,
                'source_payload' => $item['source_payload'] ?? null,
            ];
        }, $items);
    }

    private function normalizeItems(array $items, ?string $origin): array
    {
        return array_map(function ($item) use ($origin) {
            $source = is_array($item) ? $item : (array) $item;
            $quantity = $this->decimal($this->firstValue($source, [
                'quantidade',
                'quantidadeItem',
                'qtde',
                'qtd',
                'quantity',
            ]));
            $unitPrice = $this->decimal($this->firstValue($source, [
                'valorUnitarioEstimado',
                'valorUnitario',
                'valor_unitario',
                'precoUnitario',
                'unit_price',
            ]));

            return [
                'item' => $this->firstValue($source, ['numeroItem', 'item', 'sequencial', 'id', 'numero']),
                'quantity' => $quantity,
                'unit' => $this->firstValue($source, ['unidadeMedida', 'unidade', 'unit', 'siglaUnidadeMedida']),
                'specification' => $this->firstValue($source, ['descricao', 'descricaoItem', 'objeto', 'item_descricao', 'specification', 'nome']),
                'brand' => $this->firstValue($source, ['marca', 'brand']),
                'unit_price' => $unitPrice,
                'total_value' => $quantity !== null && $unitPrice !== null ? round($quantity * $unitPrice, 2) : $this->decimal($this->firstValue($source, ['valorTotal', 'valor_total', 'total_value'])),
                'source_payload' => array_merge($source, ['api_origin' => $origin]),
            ];
        }, $items);
    }

    private function buildProposalPayload(Company $company, Tender $tender): array
    {
        return [
            'organ_name' => $tender->organ_name,
            'organ_state' => $tender->uf,
            'purchase_number' => $tender->number_purchase,
            'process_number' => $tender->process,
            'receipt_date' => $tender->proposal_closing_date,
            'opening_date' => $tender->bid_opening_date,
            'city' => $company->city,
            'proposal_date' => now()->toDateString(),
            'responsible_name' => $company->legal_representative_name,
            'responsible_rg' => $company->legal_representative_rg,
            'responsible_cpf' => $company->legal_representative_cpf,
            'declarations' => $this->defaultDeclarations($company),
        ];
    }

    private function defaultDeclarations(Company $company): string
    {
        return "VALIDADE DA PROPOSTA: DE ACORDO COM EDITAL\n\n"
            . "DECLARAÇÃO:\n"
            . "Declaramos que nos preços propostos estão inclusos todos os custos, encargos, tributos, despesas diretas e indiretas necessárias ao cumprimento integral do objeto.\n\n"
            . "DADOS BANCÁRIOS:\n"
            . "Banco: {$company->bank}\n"
            . "Agência: {$company->agency}\n"
            . "Conta Corrente: {$company->checking_account}";
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
            'legal_representative_name',
            'legal_representative_rg',
            'legal_representative_cpf',
            'bank',
            'agency',
            'checking_account',
            'logo',
        ]);
    }

    private function tenderSnapshot(Tender $tender): array
    {
        return $tender->only([
            'id',
            'value',
            'modality',
            'status',
            'year_purchase',
            'number_purchase',
            'sequential_purchase',
            'organ_cnpj',
            'organ_name',
            'uf',
            'city',
            'object',
            'process',
            'bid_opening_date',
            'proposal_closing_date',
            'publication_date',
            'api_origin',
        ]);
    }

    private function sumItems(array $items): float
    {
        return round(array_sum(array_map(fn ($item) => (float) ($item['total_value'] ?? 0), $items)), 2);
    }

    private function firstValue(array $source, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $source) && $source[$key] !== null && $source[$key] !== '') {
                return $source[$key];
            }
        }

        return null;
    }

    private function decimal($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $value));
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
