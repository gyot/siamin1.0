<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Open Graph metadata renderer
    |--------------------------------------------------------------------------
    |
    | spa_index is the built Vue SPA shell (dist/index.html). The OG endpoint
    | injects link-preview <meta> tags into it for crawler bots (WhatsApp,
    | Telegram, Facebook, etc.) which do not execute JavaScript. Override the
    | path via OG_SPA_INDEX when deploying to another host (e.g. cPanel).
    |
    */

    'spa_index' => env('OG_SPA_INDEX', '/home/bpmp/website/siamin-ui/dist/index.html'),
    'base_url' => env('OG_BASE_URL'),
    'cache_ttl' => env('OG_CACHE_TTL', 1800),
];
