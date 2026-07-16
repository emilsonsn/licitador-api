<?php

namespace Tests\Feature;

use App\Models\Tender;
use App\Services\Tender\TenderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenderOriginDomainFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        $this->app['db']->purge('sqlite');
        $this->app['db']->reconnect('sqlite');

        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->string('origin_url')->nullable();
            $table->dateTime('proposal_closing_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('favorite_tenders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tender_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tender_id');
            $table->unsignedBigInteger('user_id');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function test_filters_tenders_by_a_list_of_origin_url_prefixes(): void
    {
        Tender::create(['origin_url' => 'http://www.varginha.mg.gov.br/licitacao/232323/index.html']);
        Tender::create(['origin_url' => 'https://pncp.gov.br/app/editais/123']);
        Tender::create(['origin_url' => 'https://bllcompras.com/process/456']);

        $request = Request::create('/api/tender/search', 'GET', [
            'origin_domains' => [
                'http://www.varginha.mg.gov.br',
                'https://pncp.gov.br',
            ],
        ]);

        $result = app(TenderService::class)->search($request);

        $this->assertTrue($result['status']);
        $this->assertSame(2, $result['data']->total());
        $this->assertEqualsCanonicalizing(
            [
                'http://www.varginha.mg.gov.br/licitacao/232323/index.html',
                'https://pncp.gov.br/app/editais/123',
            ],
            $result['data']->getCollection()->pluck('origin_url')->all()
        );
    }

    public function test_accepts_origin_domains_as_a_comma_separated_string(): void
    {
        Tender::create(['origin_url' => 'https://comprasbr.com.br/process/123']);
        Tender::create(['origin_url' => 'https://portal.licitanet.com.br/process/456']);

        $request = Request::create('/api/tender/search', 'GET', [
            'origin_domains' => 'https://comprasbr.com.br, https://portal.licitanet.com.br',
        ]);

        $result = app(TenderService::class)->search($request);

        $this->assertTrue($result['status']);
        $this->assertSame(2, $result['data']->total());
    }
}
