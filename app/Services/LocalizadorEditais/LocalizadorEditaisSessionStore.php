<?php

namespace App\Services\LocalizadorEditais;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class LocalizadorEditaisSessionStore
{
    private const PATH = 'private/localizador-editais-session.enc';

    public function load(): ?array
    {
        if (! Storage::disk('local')->exists(self::PATH)) {
            return null;
        }

        try {
            $session = json_decode(
                Crypt::decryptString(Storage::disk('local')->get(self::PATH)),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            return isset($session['cookie'], $session['nonce']) ? $session : null;
        } catch (\Throwable) {
            $this->forget();

            return null;
        }
    }

    public function save(string $cookie, string $nonce): void
    {
        Storage::disk('local')->put(self::PATH, Crypt::encryptString(json_encode([
            'cookie' => $cookie,
            'nonce' => $nonce,
            'updated_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR)));
    }

    public function forget(): void
    {
        Storage::disk('local')->delete(self::PATH);
    }
}
