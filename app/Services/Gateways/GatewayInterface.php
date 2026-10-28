<?php
namespace App\Services\Gateways;

interface GatewayInterface
{
    public function code(): string;

    /** @return array{authorization_url:string, reference:string} */
    public function initialize(string $email, float $amount, string $currency, string $reference, string $callbackUrl): array;

    /** Ask the provider (server-to-server) what happened. Never trust the browser redirect alone.
     *  @return array{success:bool, amount:float, currency:string, transaction_id:?string, method:?string, raw:array} */
    public function verify(string $reference): array;

    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool;
}
