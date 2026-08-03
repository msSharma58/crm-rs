<?php

return [
    'meta' => [
        'graph_version' => env('META_GRAPH_VERSION', 'v21.0'),
        'graph_base' => env('META_GRAPH_BASE', 'https://graph.facebook.com'),
        // Platform-level defaults (optional). Per-org credentials live in integration_settings.
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'default_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN', 'homora_meta_verify'),
    ],

    'facebook' => [
        'enabled' => env('FACEBOOK_LEADS_ENABLED', true),
    ],

    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', true),
        'default_template_language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'en'),
    ],
];
