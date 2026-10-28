<?php
use App\Controllers\Webhook\PaymentWebhookController;

// No session, no CSRF (see CsrfProtect). Authenticated by the provider's HMAC signature.
$router->post('/webhooks/paystack', [PaymentWebhookController::class, 'handle']);
