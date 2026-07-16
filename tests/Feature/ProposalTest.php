<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Proposal;
use App\Models\Tender;
use App\Models\User;
use App\Services\Tender\TenderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ProposalTest extends TestCase
{
    use WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        $this->app['db']->purge('sqlite');
        $this->app['db']->reconnect('sqlite');

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_fill_with_existing_company(): void
    {
        $user = $this->createUser();
        $company = $this->createCompany($user);
        $tender = $this->createTender();

        $this->mockTenderItems($tender->id, [
            [
                'numeroItem' => '1',
                'quantidade' => '2',
                'unidadeMedida' => 'UN',
                'descricao' => 'Produto teste',
                'valorUnitarioEstimado' => '10.50',
            ],
        ]);

        $response = $this->actingAs($user)->postJson('/api/proposal/fill', [
            'tender_id' => $tender->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.company.id', $company->id)
            ->assertJsonPath('data.items.0.total_value', 21);
    }

    public function test_fill_requires_company(): void
    {
        $user = $this->createUser();
        $tender = $this->createTender();

        $response = $this->actingAs($user)->postJson('/api/proposal/fill', [
            'tender_id' => $tender->id,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('status', false);
    }

    public function test_create_recalculates_totals(): void
    {
        $user = $this->createUser();
        $company = $this->createCompany($user);
        $tender = $this->createTender();

        $response = $this->actingAs($user)->postJson('/api/proposal', [
            'company_id' => $company->id,
            'tender_id' => $tender->id,
            'organ_name' => 'Orgao',
            'items' => [
                ['item' => '1', 'quantity' => 2, 'unit_price' => 10, 'total_value' => 999],
                ['item' => '2', 'quantity' => 3, 'unit_price' => 5],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.total_value', '35.00')
            ->assertJsonPath('data.items.0.total_value', '20.00');
    }

    public function test_user_cannot_access_another_user_proposal(): void
    {
        $owner = $this->createUser('owner@example.com');
        $other = $this->createUser('other@example.com');
        $company = $this->createCompany($owner);
        $tender = $this->createTender();
        $proposal = $this->createProposal($owner, $company, $tender);

        $response = $this->actingAs($other)->getJson("/api/proposal/{$proposal->id}");

        $response->assertStatus(404)
            ->assertJsonPath('status', false);
    }

    public function test_update_replaces_items(): void
    {
        $user = $this->createUser();
        $company = $this->createCompany($user);
        $tender = $this->createTender();
        $proposal = $this->createProposal($user, $company, $tender);

        $response = $this->actingAs($user)->patchJson("/api/proposal/{$proposal->id}", [
            'items' => [
                ['item' => 'novo', 'quantity' => 4, 'unit_price' => 2.5, 'brand' => 'Marca A'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.total_value', '10.00')
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.brand', 'Marca A');
    }

    public function test_view_returns_complete_snapshot(): void
    {
        $user = $this->createUser();
        $company = $this->createCompany($user);
        $tender = $this->createTender();
        $proposal = $this->createProposal($user, $company, $tender);
        $proposal->items()->create([
            'item' => '1',
            'quantity' => 1,
            'unit_price' => 9,
            'total_value' => 9,
        ]);

        $response = $this->actingAs($user)->getJson("/api/proposal/{$proposal->id}/view");

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.company.corporate_reason', 'Empresa Teste LTDA')
            ->assertJsonPath('data.tender.organ_name', 'Orgao Teste')
            ->assertJsonCount(1, 'data.items');
    }

    public function test_tracking_is_initialized_with_pending_items(): void
    {
        $user = $this->createUser();
        $company = $this->createCompany($user);
        $tender = $this->createTender();
        $proposal = $this->createProposal($user, $company, $tender);
        $proposal->items()->create([
            'item' => '1',
            'quantity' => 2,
            'unit_price' => 10,
            'total_value' => 20,
        ]);

        $response = $this->actingAs($user)->getJson("/api/proposal/{$proposal->id}/tracking");

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.tracking.status', 'open')
            ->assertJsonPath('data.items.0.result', 'pending')
            ->assertJsonPath('data.items.0.minimum_unit_price', null)
            ->assertJsonPath('data.totals.original', '0.00')
            ->assertJsonPath('data.totals.minimum', '0.00');

        $this->assertDatabaseCount('proposal_trackings', 1);
        $this->assertDatabaseCount('proposal_tracking_items', 1);
    }

    public function test_tracking_applies_non_cumulative_discount_to_all_items(): void
    {
        $user = $this->createUser();
        $company = $this->createCompany($user);
        $tender = $this->createTender();
        $proposal = $this->createProposal($user, $company, $tender);
        $proposal->update(['total_value' => 20]);
        $proposal->items()->create([
            'item' => '1',
            'quantity' => 2,
            'unit_price' => 10,
            'total_value' => 20,
        ]);

        $this->actingAs($user)->postJson("/api/proposal/{$proposal->id}/tracking/apply-discount", [
            'discount_percentage' => 10,
        ])->assertOk()
            ->assertJsonPath('data.items.0.minimum_unit_price', '9.0000')
            ->assertJsonPath('data.items.0.minimum_total_value', '18.00');

        $this->actingAs($user)->postJson("/api/proposal/{$proposal->id}/tracking/apply-discount", [
            'discount_percentage' => 20,
        ])->assertOk()
            ->assertJsonPath('data.items.0.minimum_unit_price', '8.0000')
            ->assertJsonPath('data.items.0.minimum_total_value', '16.00');
    }

    public function test_tracking_updates_results_prices_classification_and_won_total(): void
    {
        $user = $this->createUser();
        $company = $this->createCompany($user);
        $tender = $this->createTender();
        $proposal = $this->createProposal($user, $company, $tender);
        $wonItem = $proposal->items()->create([
            'item' => '1',
            'quantity' => 2,
            'unit_price' => 10,
            'total_value' => 20,
        ]);
        $lostItem = $proposal->items()->create([
            'item' => '2',
            'quantity' => 3,
            'unit_price' => 5,
            'total_value' => 15,
        ]);
        $proposal->update(['total_value' => 35]);

        $response = $this->actingAs($user)->putJson("/api/proposal/{$proposal->id}/tracking", [
            'items' => [
                [
                    'proposal_item_id' => $wonItem->id,
                    'result' => 'won',
                    'minimum_unit_price' => 9,
                    'rankings' => [
                        [
                            'position' => 1,
                            'company' => 'Empresa Teste LTDA',
                            'brand' => 'Marca A',
                            'price' => 9,
                        ],
                        [
                            'position' => 2,
                            'company' => 'Concorrente A',
                            'brand' => 'Marca B',
                            'price' => 9.5,
                        ],
                    ],
                ],
                [
                    'proposal_item_id' => $lostItem->id,
                    'result' => 'lost',
                    'minimum_unit_price' => 4.5,
                    'rankings' => [
                        [
                            'position' => 1,
                            'company' => 'Concorrente B',
                            'brand' => 'Marca C',
                            'price' => 4,
                        ],
                        [
                            'position' => 3,
                            'company' => 'Empresa Teste LTDA',
                            'brand' => 'Marca A',
                            'price' => 5,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.items.0.result', 'won')
            ->assertJsonPath('data.items.1.result', 'lost')
            ->assertJsonPath('data.items.0.rankings.0.position', 1)
            ->assertJsonPath('data.items.0.rankings.0.company', 'Empresa Teste LTDA')
            ->assertJsonPath('data.items.1.rankings.1.position', 3)
            ->assertJsonPath('data.items.1.rankings.1.price', '5.0000')
            ->assertJsonPath('data.totals.minimum', '31.50')
            ->assertJsonPath('data.totals.won', '18.00')
            ->assertJsonCount(1, 'data.won_items');

        $this->assertDatabaseCount('proposal_tracking_item_rankings', 4);

        $this->actingAs($user)
            ->putJson("/api/proposal/{$proposal->id}/tracking", [
                'items' => [[
                    'proposal_item_id' => $wonItem->id,
                    'result' => 'pending',
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('data.items.0.result', 'pending')
            ->assertJsonCount(0, 'data.items.0.rankings');

        $this->assertDatabaseCount('proposal_tracking_item_rankings', 2);
    }

    public function test_finished_tracking_is_read_only_until_reopened(): void
    {
        $user = $this->createUser();
        $company = $this->createCompany($user);
        $tender = $this->createTender();
        $proposal = $this->createProposal($user, $company, $tender);
        $item = $proposal->items()->create(['item' => '1', 'quantity' => 1, 'unit_price' => 10]);

        $this->actingAs($user)
            ->postJson("/api/proposal/{$proposal->id}/tracking/finish")
            ->assertOk()
            ->assertJsonPath('data.tracking.status', 'finished');

        $this->actingAs($user)
            ->putJson("/api/proposal/{$proposal->id}/tracking", [
                'items' => [[
                    'proposal_item_id' => $item->id,
                    'result' => 'won',
                    'rankings' => [[
                        'position' => 1,
                        'company' => 'Empresa Teste LTDA',
                        'brand' => 'Marca',
                        'price' => 10,
                    ]],
                ]],
            ])
            ->assertStatus(409);

        $this->actingAs($user)
            ->postJson("/api/proposal/{$proposal->id}/tracking/reopen")
            ->assertOk()
            ->assertJsonPath('data.tracking.status', 'open');

        $this->actingAs($user)
            ->putJson("/api/proposal/{$proposal->id}/tracking", [
                'items' => [[
                    'proposal_item_id' => $item->id,
                    'result' => 'won',
                    'rankings' => [[
                        'position' => 1,
                        'company' => 'Empresa Teste LTDA',
                        'brand' => 'Marca',
                        'price' => 10,
                    ]],
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('data.items.0.result', 'won');
    }

    public function test_user_cannot_access_another_user_tracking(): void
    {
        $owner = $this->createUser('owner@example.com');
        $other = $this->createUser('other@example.com');
        $company = $this->createCompany($owner);
        $tender = $this->createTender();
        $proposal = $this->createProposal($owner, $company, $tender);

        $this->actingAs($other)
            ->getJson("/api/proposal/{$proposal->id}/tracking")
            ->assertStatus(404);
    }

    public function test_tracking_rejects_an_item_from_another_proposal(): void
    {
        $user = $this->createUser();
        $company = $this->createCompany($user);
        $tender = $this->createTender();
        $proposal = $this->createProposal($user, $company, $tender);
        $otherProposal = $this->createProposal($user, $company, $tender);
        $otherItem = $otherProposal->items()->create([
            'item' => 'outro',
            'quantity' => 1,
            'unit_price' => 10,
        ]);

        $this->actingAs($user)
            ->putJson("/api/proposal/{$proposal->id}/tracking", [
                'items' => [[
                    'proposal_item_id' => $otherItem->id,
                    'result' => 'won',
                ]],
            ])
            ->assertStatus(400)
            ->assertJsonPath('status', false);
    }

    public function test_tracking_rejects_duplicate_positions_in_the_same_item(): void
    {
        $user = $this->createUser();
        $company = $this->createCompany($user);
        $tender = $this->createTender();
        $proposal = $this->createProposal($user, $company, $tender);
        $item = $proposal->items()->create([
            'item' => '1',
            'quantity' => 1,
            'unit_price' => 10,
        ]);

        $this->actingAs($user)
            ->putJson("/api/proposal/{$proposal->id}/tracking", [
                'items' => [[
                    'proposal_item_id' => $item->id,
                    'result' => 'won',
                    'rankings' => [
                        ['position' => 1, 'company' => 'Empresa A', 'price' => 10],
                        ['position' => 1, 'company' => 'Empresa B', 'price' => 11],
                    ],
                ]],
            ])
            ->assertStatus(400)
            ->assertJsonPath('status', false);

        $this->assertDatabaseCount('proposal_tracking_item_rankings', 0);
    }

    public function test_tracking_exposes_print_payload_and_csv_export(): void
    {
        $user = $this->createUser();
        $company = $this->createCompany($user);
        $tender = $this->createTender();
        $proposal = $this->createProposal($user, $company, $tender);
        $proposal->items()->create([
            'item' => '1',
            'quantity' => 1,
            'unit_price' => 10,
            'total_value' => 10,
        ]);

        $this->actingAs($user)
            ->getJson("/api/proposal/{$proposal->id}/tracking/print")
            ->assertOk()
            ->assertJsonPath('data.document.title', 'Acompanhamento de Licitação');

        $export = $this->actingAs($user)
            ->get("/api/proposal/{$proposal->id}/tracking/export");

        $export->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Preço unitário mínimo', $export->getContent());
        $this->assertStringContainsString('Empresa 1º lugar', $export->getContent());
    }

    public function test_catalog_is_initialized_once_from_the_proposal_snapshot(): void
    {
        $user = $this->createUser();
        $company = $this->createCompany($user);
        $tender = $this->createTender();
        $proposal = $this->createProposal($user, $company, $tender);
        $proposal->update(['organ_state' => 'MG', 'purchase_number' => '001/2026']);
        $proposal->items()->create([
            'item' => '1',
            'quantity' => 2,
            'unit' => 'UN',
            'specification' => 'Produto teste',
            'brand' => 'Marca A',
        ]);

        $first = $this->actingAs($user)->getJson("/api/proposal/{$proposal->id}/catalog");
        $second = $this->actingAs($user)->getJson("/api/proposal/{$proposal->id}/catalog");

        $first->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.title', 'Catálogo de Produtos')
            ->assertJsonPath('data.organ_state', 'MG')
            ->assertJsonPath('data.company_snapshot.corporate_reason', 'Empresa Teste LTDA')
            ->assertJsonPath('data.items.0.title', 'Produto teste')
            ->assertJsonPath('data.items.0.quantity', '2.0000')
            ->assertJsonPath('data.items.0.position', 1)
            ->assertJsonPath('data.items.0.image_url', null);

        $second->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertDatabaseCount('proposal_catalogs', 1);
        $this->assertDatabaseCount('proposal_catalog_items', 1);
    }

    public function test_catalog_update_reorders_adds_and_removes_items_transactionally(): void
    {
        $user = $this->createUser();
        $company = $this->createCompany($user);
        $tender = $this->createTender();
        $proposal = $this->createProposal($user, $company, $tender);
        $firstProposalItem = $proposal->items()->create(['item' => '1', 'specification' => 'Remover']);
        $secondProposalItem = $proposal->items()->create(['item' => '2', 'specification' => 'Manter']);

        $catalog = $this->actingAs($user)->getJson("/api/proposal/{$proposal->id}/catalog")->json('data');
        $firstItem = collect($catalog['items'])->firstWhere('proposal_item_id', $firstProposalItem->id);
        $secondItem = collect($catalog['items'])->firstWhere('proposal_item_id', $secondProposalItem->id);

        $response = $this->actingAs($user)->putJson("/api/proposal/{$proposal->id}/catalog", [
            'title' => 'Catálogo personalizado',
            'subtitle' => 'Linha 2026',
            'general_notes' => 'Observação final',
            'items' => [
                [
                    'title' => 'Produto manual',
                    'specification' => 'Incluído fora da proposta',
                    'quantity' => 3,
                    'unit' => 'CX',
                    'brand' => 'Marca B',
                    'position' => 1,
                ],
                [
                    'id' => $secondItem['id'],
                    'proposal_item_id' => $secondProposalItem->id,
                    'title' => 'Produto mantido e editado',
                    'position' => 2,
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Catálogo personalizado')
            ->assertJsonPath('data.items.0.title', 'Produto manual')
            ->assertJsonPath('data.items.0.position', 1)
            ->assertJsonPath('data.items.1.id', $secondItem['id'])
            ->assertJsonPath('data.items.1.position', 2)
            ->assertJsonCount(2, 'data.items');

        $this->assertDatabaseMissing('proposal_catalog_items', ['id' => $firstItem['id']]);
        $this->assertDatabaseHas('proposal_catalog_items', [
            'proposal_catalog_id' => $catalog['id'],
            'proposal_item_id' => null,
            'position' => 1,
        ]);
    }

    public function test_catalog_rejects_items_from_another_catalog_or_proposal(): void
    {
        $user = $this->createUser();
        $company = $this->createCompany($user);
        $tender = $this->createTender();
        $proposal = $this->createProposal($user, $company, $tender);
        $otherProposal = $this->createProposal($user, $company, $tender);
        $proposalItem = $proposal->items()->create(['item' => '1']);
        $otherProposalItem = $otherProposal->items()->create(['item' => '2']);

        $this->actingAs($user)->getJson("/api/proposal/{$proposal->id}/catalog");
        $otherCatalogItemId = $this->actingAs($user)
            ->getJson("/api/proposal/{$otherProposal->id}/catalog")
            ->json('data.items.0.id');

        $this->actingAs($user)->putJson("/api/proposal/{$proposal->id}/catalog", [
            'items' => [[
                'id' => $otherCatalogItemId,
                'proposal_item_id' => $proposalItem->id,
                'position' => 1,
            ]],
        ])->assertStatus(400);

        $catalogItemId = $this->actingAs($user)
            ->getJson("/api/proposal/{$proposal->id}/catalog")
            ->json('data.items.0.id');

        $this->actingAs($user)->putJson("/api/proposal/{$proposal->id}/catalog", [
            'items' => [[
                'id' => $catalogItemId,
                'proposal_item_id' => $otherProposalItem->id,
                'position' => 1,
            ]],
        ])->assertStatus(400);
    }

    public function test_user_cannot_access_another_user_catalog(): void
    {
        $owner = $this->createUser('owner-catalog@example.com');
        $other = $this->createUser('other-catalog@example.com');
        $company = $this->createCompany($owner);
        $tender = $this->createTender();
        $proposal = $this->createProposal($owner, $company, $tender);
        $catalogId = $this->actingAs($owner)
            ->getJson("/api/proposal/{$proposal->id}/catalog")
            ->json('data.id');

        $this->actingAs($other)
            ->getJson("/api/proposal/{$proposal->id}/catalog")
            ->assertStatus(404);
        $this->actingAs($other)
            ->getJson("/api/proposal-catalog/{$catalogId}/view")
            ->assertStatus(404);
    }

    public function test_catalog_item_image_can_be_uploaded_replaced_and_removed(): void
    {
        Storage::fake('public');
        $user = $this->createUser();
        $company = $this->createCompany($user);
        $tender = $this->createTender();
        $proposal = $this->createProposal($user, $company, $tender);
        $proposal->items()->create(['item' => '1', 'specification' => 'Produto']);
        $itemId = $this->actingAs($user)
            ->getJson("/api/proposal/{$proposal->id}/catalog")
            ->json('data.items.0.id');

        $firstUpload = $this->actingAs($user)->postJson(
            "/api/proposal/{$proposal->id}/catalog/items/{$itemId}/image",
            ['image' => UploadedFile::fake()->image('produto.png', 400, 300)]
        );
        $firstUpload->assertOk()->assertJsonPath('data.image_original_name', 'produto.png');
        $firstPath = $this->app['db']->table('proposal_catalog_items')->where('id', $itemId)->value('image_path');
        Storage::disk('public')->assertExists($firstPath);

        $this->actingAs($user)->postJson(
            "/api/proposal/{$proposal->id}/catalog/items/{$itemId}/image",
            ['image' => UploadedFile::fake()->image('produto-novo.webp', 400, 300)]
        )->assertOk();
        $secondPath = $this->app['db']->table('proposal_catalog_items')->where('id', $itemId)->value('image_path');
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);

        $this->actingAs($user)
            ->deleteJson("/api/proposal/{$proposal->id}/catalog/items/{$itemId}/image")
            ->assertOk()
            ->assertJsonPath('data.image_url', null);
        Storage::disk('public')->assertMissing($secondPath);
    }

    public function test_catalog_generate_marks_generation_and_view_returns_print_payload(): void
    {
        $user = $this->createUser();
        $company = $this->createCompany($user);
        $tender = $this->createTender();
        $proposal = $this->createProposal($user, $company, $tender);
        $proposal->items()->create(['item' => '1', 'specification' => 'Produto']);

        $generated = $this->actingAs($user)
            ->postJson("/api/proposal/{$proposal->id}/catalog/generate")
            ->assertOk();
        $catalogId = $generated->json('data.id');
        $this->assertNotNull($generated->json('data.generated_at'));

        $this->actingAs($user)
            ->getJson("/api/proposal-catalog/{$catalogId}/view")
            ->assertOk()
            ->assertJsonPath('data.id', $catalogId)
            ->assertJsonPath('data.items.0.title', 'Produto');
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('has_notification')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('cnpj')->nullable();
            $table->string('corporate_reason')->nullable();
            $table->string('fantasy_name')->nullable();
            $table->string('city')->nullable();
            $table->string('legal_representative_name')->nullable();
            $table->string('legal_representative_rg')->nullable();
            $table->string('legal_representative_cpf')->nullable();
            $table->string('bank')->nullable();
            $table->string('agency')->nullable();
            $table->string('checking_account')->nullable();
            $table->timestamps();
        });

        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->string('number_purchase')->nullable();
            $table->integer('year_purchase')->nullable();
            $table->integer('sequential_purchase')->nullable();
            $table->string('organ_name')->nullable();
            $table->string('uf')->nullable();
            $table->string('process')->nullable();
            $table->date('bid_opening_date')->nullable();
            $table->date('proposal_closing_date')->nullable();
            $table->date('publication_date')->nullable();
            $table->string('api_origin')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('company_id')->nullable();
            $table->foreignId('tender_id');
            $table->string('title')->nullable();
            $table->string('organ_name')->nullable();
            $table->string('organ_state')->nullable();
            $table->string('purchase_number')->nullable();
            $table->string('process_number')->nullable();
            $table->date('receipt_date')->nullable();
            $table->date('opening_date')->nullable();
            $table->longText('declarations')->nullable();
            $table->string('city')->nullable();
            $table->date('proposal_date')->nullable();
            $table->string('responsible_name')->nullable();
            $table->string('responsible_rg')->nullable();
            $table->string('responsible_cpf')->nullable();
            $table->decimal('total_value', 15, 2)->nullable();
            $table->string('status')->default('draft');
            $table->json('company_snapshot')->nullable();
            $table->json('tender_snapshot')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('proposal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id');
            $table->string('item')->nullable();
            $table->decimal('quantity', 15, 4)->nullable();
            $table->string('unit')->nullable();
            $table->text('specification')->nullable();
            $table->string('brand')->nullable();
            $table->decimal('unit_price', 15, 4)->nullable();
            $table->decimal('total_value', 15, 2)->nullable();
            $table->json('source_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('proposal_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->unique();
            $table->decimal('discount_percentage', 7, 4)->nullable();
            $table->string('status')->default('open');
            $table->foreignId('last_updated_by')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('proposal_tracking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_tracking_id');
            $table->foreignId('proposal_item_id');
            $table->string('result')->default('pending');
            $table->decimal('minimum_unit_price', 15, 4)->nullable();
            $table->timestamp('classified_at')->nullable();
            $table->foreignId('classified_by')->nullable();
            $table->timestamps();
        });

        Schema::create('proposal_tracking_item_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_tracking_item_id');
            $table->unsignedTinyInteger('position');
            $table->string('company');
            $table->string('brand')->nullable();
            $table->decimal('price', 15, 4);
            $table->timestamps();
        });

        Schema::create('proposal_catalogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->unique();
            $table->foreignId('user_id');
            $table->foreignId('company_id')->nullable();
            $table->string('title')->default('Catálogo de Produtos');
            $table->string('subtitle')->nullable();
            $table->longText('general_notes')->nullable();
            $table->string('organ_name')->nullable();
            $table->string('organ_state')->nullable();
            $table->string('purchase_number')->nullable();
            $table->string('process_number')->nullable();
            $table->date('receipt_date')->nullable();
            $table->date('opening_date')->nullable();
            $table->json('company_snapshot')->nullable();
            $table->foreignId('last_updated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('proposal_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_catalog_id');
            $table->foreignId('proposal_item_id')->nullable();
            $table->string('title')->nullable();
            $table->longText('specification')->nullable();
            $table->decimal('quantity', 15, 4)->nullable();
            $table->string('unit')->nullable();
            $table->string('brand')->nullable();
            $table->unsignedInteger('position');
            $table->string('image_path')->nullable();
            $table->string('image_original_name')->nullable();
            $table->string('image_mime', 100)->nullable();
            $table->timestamps();
        });
    }

    private function createUser(string $email = 'user@example.com'): User
    {
        return User::create([
            'name' => 'Usuario Teste',
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
    }

    private function createCompany(User $user): Company
    {
        return Company::create([
            'user_id' => $user->id,
            'corporate_reason' => 'Empresa Teste LTDA',
            'city' => 'Juiz de Fora',
            'legal_representative_name' => 'Responsavel',
            'legal_representative_rg' => 'MG123',
            'legal_representative_cpf' => '000.000.000-00',
            'bank' => 'Banco do Brasil',
            'agency' => '0001',
            'checking_account' => '123',
        ]);
    }

    private function createTender(): Tender
    {
        return Tender::create([
            'number_purchase' => '001',
            'year_purchase' => 2026,
            'sequential_purchase' => 10,
            'organ_name' => 'Orgao Teste',
            'uf' => 'MG',
            'process' => 'PROC-1',
            'api_origin' => 'PNCP',
        ]);
    }

    private function createProposal(User $user, Company $company, Tender $tender): Proposal
    {
        return Proposal::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'tender_id' => $tender->id,
            'organ_name' => $tender->organ_name,
            'company_snapshot' => $company->only(['id', 'corporate_reason']),
            'tender_snapshot' => $tender->only(['id', 'organ_name']),
            'total_value' => 0,
        ]);
    }

    private function mockTenderItems(int $tenderId, array $items): void
    {
        $mock = Mockery::mock(TenderService::class);
        $mock->shouldReceive('items')
            ->once()
            ->with($tenderId)
            ->andReturn(['status' => true, 'data' => $items]);

        $this->app->instance(TenderService::class, $mock);
    }
}
