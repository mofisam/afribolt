<?php
namespace App\Services\Gateways;

/** Example adapter. The gateway is still "to be confirmed" in the requirements - keep other adapters beside this one. */
class PaystackGateway implements GatewayInterface
{
    private string $secret;
    private string $base;

    public function __construct()
    {
        $this->secret = (string) config('payment.paystack.secret_key');
        $this->base = rtrim((string) config('payment.paystack.base_url'), '/');
    }

    public function code(): string { return 'paystack'; }

    public function initialize(string $email, float $amount, string $currency, string $reference, string $callbackUrl): array
    {
        $res = $this->request('POST', '/transaction/initialize', [
            'email' => $email, 'amount' => to_minor($amount), 'currency' => $currency,
            'reference' => $reference, 'callback_url' => $callbackUrl,
        ]);
        if (empty($res['status']) || empty($res['data']['authorization_url'])) {
            throw new \RuntimeException('Payment initialisation failed: ' . ($res['message'] ?? 'unknown error'));
        }
        return ['authorization_url' => $res['data']['authorization_url'], 'reference' => $res['data']['reference'] ?? $reference];
    }

    public function verify(string $reference): array
    {
        $res = $this->request('GET', '/transaction/verify/' . rawurlencode($reference));
        $d = $res['data'] ?? [];
        return [
            'success'        => !empty($res['status']) && ($d['status'] ?? '') === 'success',
            'amount'         => ((float) ($d['amount'] ?? 0)) / 100,
            'currency'       => (string) ($d['currency'] ?? ''),
            'transaction_id' => isset($d['id']) ? (string) $d['id'] : null,
            'method'         => $d['channel'] ?? null,
            'raw'            => $res,
        ];
    }

    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        if (!$this->secret || !$signatureHeader) { return false; }
        return hash_equals(hash_hmac('sha512', $rawBody, $this->secret), $signatureHeader);
    }

    private function request(string $method, string $path, ?array $body = null): array
    {
        $ch = curl_init($this->base . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->secret, 'Content-Type: application/json'],
        ]);
        if ($body !== null) { curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); }
        $out = curl_exec($ch);
        if ($out === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Gateway unreachable: ' . $err);
        }
        curl_close($ch);
        return json_decode($out, true) ?: [];
    }
}
