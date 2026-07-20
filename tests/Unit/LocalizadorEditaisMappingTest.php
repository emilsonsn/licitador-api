<?php

namespace Tests\Unit;

use App\Services\Tender\TenderService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class LocalizadorEditaisMappingTest extends TestCase
{
    public function test_parses_pncp_number_from_localizador_response(): void
    {
        $method = new ReflectionMethod(TenderService::class, 'parseLocalizadorEditalNumber');

        $this->assertSame(
            ['06441430000125', 2026, 58],
            $method->invoke(new TenderService(), '06441430000125-1-000058/2026')
        );
    }

    public function test_maps_localizador_modality_to_project_pncp_id(): void
    {
        $method = new ReflectionMethod(TenderService::class, 'localizadorModalityId');
        $service = new TenderService();

        $this->assertSame(12, $method->invoke($service, 'Credenciamento'));
        $this->assertSame(6, $method->invoke($service, 'Pregão Eletrônico'));
        $this->assertNull($method->invoke($service, 'Modalidade desconhecida'));
    }

    public function test_removes_only_null_values_from_existing_tender_update(): void
    {
        $method = new ReflectionMethod(TenderService::class, 'removeNullValuesForUpdate');

        $this->assertSame([
            'proposal_closing_date' => '2026-07-30 10:00:00',
            'observations' => '',
            'value' => 0,
        ], $method->invoke(new TenderService(), [
            'proposal_closing_date' => '2026-07-30 10:00:00',
            'publication_date' => null,
            'observations' => '',
            'value' => 0,
        ]));
    }
}
