<?php

namespace App\Services\LocalizadorEditais;

use RuntimeException;
use Symfony\Component\Process\Process;

class LocalizadorEditaisBrowserAuthenticator
{
    public function authenticate(): array
    {
        $output = storage_path('app/private/localizador-editais-browser-session.json');
        $profile = storage_path('app/private/localizador-editais-browser-profile');
        $script = base_path('scripts/localizador_editais_auth.py');

        $process = new Process([
            config('services.localizador_editais.python_binary', 'python3'),
            $script,
            '--output',
            $output,
            '--profile-dir',
            $profile,
        ], base_path(), [
            'LOCALIZADOR_EDITAIS_BASE_URL' => config('services.localizador_editais.base_url'),
            'LOCALIZADOR_EDITAIS_USERNAME' => config('services.localizador_editais.username'),
            'LOCALIZADOR_EDITAIS_PASSWORD' => config('services.localizador_editais.password'),
            'LOCALIZADOR_EDITAIS_BROWSER_BINARY' => config('services.localizador_editais.browser_binary'),
            'LOCALIZADOR_EDITAIS_BROWSER_HEADLESS' => config('services.localizador_editais.browser_headless') ? 'true' : 'false',
        ]);
        $process->setTimeout(config('services.localizador_editais.browser_timeout', 120));
        $process->run();

        if (! $process->isSuccessful()) {
            $detail = trim($process->getErrorOutput());
            if (is_file($output)) {
                $error = json_decode(file_get_contents($output), true);
                $detail = $error['error'] ?? $detail;
                unlink($output);
            }

            throw new RuntimeException('Falha no login pelo navegador: '.$detail);
        }

        try {
            $session = json_decode(file_get_contents($output), true, 512, JSON_THROW_ON_ERROR);
        } finally {
            if (is_file($output)) {
                unlink($output);
            }
        }

        if (empty($session['cookie']) || empty($session['nonce'])) {
            throw new RuntimeException('O script Python não retornou uma sessão válida.');
        }

        return $session;
    }
}
