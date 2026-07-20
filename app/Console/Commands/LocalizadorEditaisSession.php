<?php

namespace App\Console\Commands;

use App\Services\LocalizadorEditais\LocalizadorEditaisSessionStore;
use Illuminate\Console\Command;

class LocalizadorEditaisSession extends Command
{
    protected $signature = 'app:localizador-editais-session {curl_file : Arquivo contendo o curl copiado do navegador}';

    protected $description = 'Salva de forma criptografada a sessão do Localizador de Editais';

    public function handle(LocalizadorEditaisSessionStore $store): int
    {
        $path = $this->argument('curl_file');

        if (! is_file($path) || ! is_readable($path)) {
            $this->error('O arquivo informado não existe ou não pode ser lido.');

            return self::FAILURE;
        }

        $curl = file_get_contents($path);
        $cookie = $this->extract($curl, '/(?:^|\s)-b\s+(["\'])(.*?)\1/s');
        $nonce = $this->extract($curl, '/(?:^|\s)-H\s+(["\'])x-wp-nonce:\s*([^"\']+)\1/i');

        if (! $cookie || ! $nonce) {
            $this->error('Não foi possível localizar o cookie (-b) e o cabeçalho x-wp-nonce no curl.');

            return self::FAILURE;
        }

        $store->save($cookie, trim($nonce));
        $this->info('Sessão salva de forma criptografada em storage/app/private.');

        return self::SUCCESS;
    }

    private function extract(string $content, string $pattern): ?string
    {
        return preg_match($pattern, $content, $matches) ? $matches[2] : null;
    }
}
