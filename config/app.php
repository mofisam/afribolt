<?php
return [
    'name'     => env('APP_NAME', 'AfriBolt Learning'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => (bool) env('APP_DEBUG', false),
    'url'      => env('APP_URL', ''),
    'key'      => env('APP_KEY', ''),
    // Display timezone only. Storage and the PHP runtime are always UTC.
    // While true, policy pages show a "draft" banner and highlight terms the client must confirm.
    'legal_draft' => (bool) env('LEGAL_DRAFT', true),
    'timezone' => env('APP_TIMEZONE', 'Africa/Lagos'),
];
