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

];
