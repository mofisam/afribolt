<?php
return [
    'provider' => env('PAYMENT_PROVIDER', 'paystack'),
    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY', ''),
        'secret_key' => env('PAYSTACK_SECRET_KEY', ''),
        'base_url'   => 'https://api.paystack.co',
    ],
    // Minutes an unpaid enrolment holds a seat before cron/expire_pending_payments.php releases it
    'pending_hold_minutes' => 60,
];
