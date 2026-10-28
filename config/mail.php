<?php
return [
    'from_address' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
    'from_name'    => env('MAIL_FROM_NAME', 'AfriBolt Technologies'),
    // Uses PHP mail(). Swap MailService::deliver() for SMTP/API once the email provider is confirmed.
    'max_attempts' => 3,
];
