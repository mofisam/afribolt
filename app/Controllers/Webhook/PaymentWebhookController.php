<?php
namespace App\Controllers\Webhook;

use App\Core\{Controller, Response};
use App\Services\WebhookService;

class PaymentWebhookController extends Controller
{
    /** POST /webhooks/paystack: no session, no CSRF; authenticated by HMAC signature instead. */
    public function handle(): void
    {
        $result = (new WebhookService())->handle($this->request->rawBody(), $this->request->header('X-Paystack-Signature'));
        http_response_code($result['status']);
        echo $result['message'];
        exit;
    }
}
