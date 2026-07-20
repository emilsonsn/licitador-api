<?php

namespace App\Console\Commands;

use App\Services\LocalizadorEditais\LocalizadorEditaisBrowserAuthenticator;
use App\Services\LocalizadorEditais\LocalizadorEditaisSessionStore;
use Illuminate\Console\Command;

class LocalizadorEditaisAuthenticate extends Command
{
    protected $signature = 'app:localizador-editais-authenticate';

    protected $description = 'Autentica no Localizador de Editais pelo navegador e persiste a sessão';

    public function handle(
        LocalizadorEditaisBrowserAuthenticator $authenticator,
        LocalizadorEditaisSessionStore $store
    ): int {
        try {
            $session = $authenticator->authenticate();
            $store->save($session['cookie'], $session['nonce']);
            $this->info('Autenticação concluída e sessão armazenada.');

            return self::SUCCESS;
        } catch (\Throwable $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }
    }
}
