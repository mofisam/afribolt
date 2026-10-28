<?php
namespace App\Core;

/** AES-256-GCM for small secrets stored in the DB (meeting passcodes). Needs APP_KEY in .env. */
final class Crypto
{
    private static function key(): string
    {
        $raw = base64_decode((string) Config::get('app.key', ''), true);
        if ($raw === false || strlen($raw) !== 32) {
            throw new \RuntimeException('APP_KEY is missing or invalid. Generate one: php -r "echo base64_encode(random_bytes(32));"');
        }
        return $raw;
    }

    public static function encrypt(string $plain): string
    {
        $iv = random_bytes(12);
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(string $payload): ?string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 29) { return null; }
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        return $plain === false ? null : $plain;
    }
}
