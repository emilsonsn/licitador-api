<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'pncp' => [
        'connect_timeout' => env('PNCP_CONNECT_TIMEOUT', 15),
        'timeout' => env('PNCP_TIMEOUT', 45),
        'retries' => env('PNCP_RETRIES', 3),
        'retry_sleep_seconds' => env('PNCP_RETRY_SLEEP_SECONDS', 5),
        'rate_limit_retries' => env('PNCP_RATE_LIMIT_RETRIES', 5),
        'rate_limit_sleep_seconds' => env('PNCP_RATE_LIMIT_SLEEP_SECONDS', 30),
        'force_ipv4' => env('PNCP_FORCE_IPV4', true),
        'user_agent' => env('PNCP_USER_AGENT', 'Licitador API'),
        'proxy' => env('PNCP_PROXY'),
    ],

    'localizador_editais' => [
        'base_url' => env('LOCALIZADOR_EDITAIS_BASE_URL', 'https://painel.localizadordeeditais.com.br'),
        'username' => env('LOCALIZADOR_EDITAIS_USERNAME'),
        'password' => env('LOCALIZADOR_EDITAIS_PASSWORD'),
        'python_binary' => env('LOCALIZADOR_EDITAIS_PYTHON_BINARY', 'python3'),
        'browser_binary' => env('LOCALIZADOR_EDITAIS_BROWSER_BINARY', '/usr/bin/google-chrome'),
        'browser_headless' => env('LOCALIZADOR_EDITAIS_BROWSER_HEADLESS', true),
        'browser_timeout' => env('LOCALIZADOR_EDITAIS_BROWSER_TIMEOUT', 120),
        'connect_timeout' => env('LOCALIZADOR_EDITAIS_CONNECT_TIMEOUT', 15),
        'timeout' => env('LOCALIZADOR_EDITAIS_TIMEOUT', 45),
        'user_agent' => env('LOCALIZADOR_EDITAIS_USER_AGENT', 'Licitador API'),
    ],

];
