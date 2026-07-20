<?php

namespace App\Console\Commands;

use App\Services\Routines\RoutinesService;
use Illuminate\Console\Command;

class LocalizadorEditaisSearch extends Command
{
    protected $signature = 'app:localizador-editais-search';

    protected $description = 'Importa licitações do Localizador de Editais';

    public function __construct(private RoutinesService $routineService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        return $this->routineService->populate_database_localizador_editais()
            ? self::SUCCESS
            : self::FAILURE;
    }
}
