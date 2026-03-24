<?php

return [
    'forge' => [
        'enabled'       => env('FORGE_ENABLED', false),
        'url'           => env('FORGE_URL', ''),

        // SSO (authorization code grant) — used for "Sign in with Forge"
        'client_id'     => env('FORGE_CLIENT_ID', ''),
        'client_secret' => env('FORGE_CLIENT_SECRET', ''),
        'redirect_uri'  => env('FORGE_REDIRECT_URI', env('APP_URL') . '/auth/forge/callback'),

        // Machine-to-machine (client credentials grant) — used by ForgeService
        // to fetch projects without a user context. Generate a dedicated client in
        // Forge with: php artisan passport:client --client --name="Codex M2M"
        'm2m_client_id'     => env('FORGE_M2M_CLIENT_ID', ''),
        'm2m_client_secret' => env('FORGE_M2M_CLIENT_SECRET', ''),

        'disable_tls_verification' => env('FORGE_DISABLE_TLS_VERIFICATION', false),
    ],

    'editor' => [
        'default_content_type' => env('CODEX_DEFAULT_CONTENT_TYPE', 'markdown'),
    ],

    'diagrams' => [
        'drawio_url' => env('DRAWIO_URL', 'https://embed.diagrams.net'),
    ],
];
