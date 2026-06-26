<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Proposal;
use App\Models\Tender;
use App\Models\User;
use App\Services\Tender\TenderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Schema;
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
