<?php

$origins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('FRONTEND_URL', 'http://localhost:5173'))
)));

return [
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_methods' => ['*'],
'allowed_origins' => [
    'http://dg.raomtech.com',
    'https://dg.raomtech.com',
    'http://localhost:5173',
    'http://localhost:5175',
    'http://localhost:5177',
],
'allowed_headers' => ['*'],
'supports_credentials' => true,
];
