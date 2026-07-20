<?php

namespace Tests\Feature;

use App\Services\LocalizadorEditais\LocalizadorEditaisSessionStore;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LocalizadorEditaisSessionCommandTest extends TestCase
{
    public function test_imports_cookie_and_nonce_from_browser_curl(): void
    {
        Storage::fake('local');
        $path = tempnam(sys_get_temp_dir(), 'localizador-curl-');
        file_put_contents($path, <<<'CURL'
curl 'https://painel.example.test/wp-json/lc/v1/licitacoes' \
  -b 'wordpress_logged_in_test=session; PHPSESSID=test' \
  -H 'x-wp-nonce: nonce123'
CURL);

        $this->artisan('app:localizador-editais-session', ['curl_file' => $path])
            ->assertSuccessful();

        $session = app(LocalizadorEditaisSessionStore::class)->load();
        $this->assertSame('wordpress_logged_in_test=session; PHPSESSID=test', $session['cookie']);
        $this->assertSame('nonce123', $session['nonce']);

        unlink($path);
    }
}
