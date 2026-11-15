<?php
$host = env('MAIL_HOST', '');
return [
    // smtp = send through your mail provider (set MAIL_HOST etc. in .env). mail = PHP mail() (often blocked or marked as spam).
    'driver'       => env('MAIL_DRIVER', $host ? 'smtp' : 'mail'),
    'host'         => $host,
    'port'         => (int) env('MAIL_PORT', 587),
    'encryption'   => strtolower((string) env('MAIL_ENCRYPTION', 'tls')), // tls (STARTTLS, port 587) | ssl (port 465) | none
    'username'     => (string) env('MAIL_USERNAME', ''),
    'password'     => (string) env('MAIL_PASSWORD', ''),
    'timeout'      => (int) env('MAIL_TIMEOUT', 20),
    'from_address' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
    'from_name'    => env('MAIL_FROM_NAME', 'AfriBolt Technologies'),
    'max_attempts' => 3,
];
