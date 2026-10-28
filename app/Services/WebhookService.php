<?php
namespace App\Services;

use App\Core\Database;
use App\Models\{PaymentGateway, PaymentWebhookEvent};

/** Records each provider event once (unique gateway_id + provider_event_id) and processes it once. */
class WebhookService
{
    public function __construct(private PaymentService $payments = new PaymentService()) {}

    public function handle(string $rawBody, ?string $signature): array
    {
        $gateway = $this->payments->gateway();
        if (!$gateway->verifyWebhookSignature($rawBody, $signature)) {
            return ['status' => 401, 'message' => 'Invalid signature'];
        }
        $event = json_decode($rawBody, true);
        $gw = PaymentGateway::findBy('provider_code', $gateway->code());
        if (!$event || !$gw) {
            return ['status' => 200, 'message' => 'Ignored']; // always 200 to stop endless retries on unknown noise
        }

        $reference = $event['data']['reference'] ?? '';
        $providerEventId = ($event['event'] ?? 'unknown') . ':' . ($event['data']['id'] ?? $reference);

        try {
            $eventId = PaymentWebhookEvent::create([
                'gateway_id' => (int) $gw['id'], 'provider_event_id' => $providerEventId,
                'event_type' => $event['event'] ?? 'unknown', 'payload' => $rawBody,
            ]);
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) { // duplicate key: already received, nothing more to do
                return ['status' => 200, 'message' => 'Duplicate'];
            }
            throw $e;
        }

        if (($event['event'] ?? '') === 'charge.success' && $reference) {
            $result = $this->payments->verifyAndConfirm($reference);
            Database::execute(
                'UPDATE payment_webhook_events SET processing_status = ?, processed_at = UTC_TIMESTAMP(), processing_error = ? WHERE id = ?',
                [$result['ok'] ? 'processed' : 'failed', $result['ok'] ? null : $result['message'], $eventId]
            );
        } else {
            Database::execute("UPDATE payment_webhook_events SET processing_status = 'ignored', processed_at = UTC_TIMESTAMP() WHERE id = ?", [$eventId]);
        }
        return ['status' => 200, 'message' => 'OK'];
    }
}
