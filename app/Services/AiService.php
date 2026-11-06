<?php
namespace App\Services;

/** Minimal Anthropic Messages API client (JSON in, JSON out). No SDK needed. */
final class AiService
{
    public static function enabled(): bool { return (string) config('jobs.api_key', '') !== ''; }

    /**
     * Ask Claude for a JSON answer.
     * @throws \RuntimeException on transport/API/parse errors
     */
    public static function json(string $system, string $user, int $maxTokens = 2500, ?string $model = null): array
    {
        if (!self::enabled()) { throw new \RuntimeException('ANTHROPIC_API_KEY is not set.'); }
        $payload = json_encode([
            'model' => $model ?: config('jobs.model', 'claude-haiku-5-5'), 'max_tokens' => $maxTokens,
            'system' => $system, 'messages' => [['role' => 'user', 'content' => $user]],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        for ($try = 1; $try <= 2; $try++) {
            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_TIMEOUT => 90, CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HTTPHEADER => ['content-type: application/json', 'anthropic-version: 2023-06-01', 'x-api-key: ' . config('jobs.api_key')],
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if ($body === false) { if ($try === 1) { sleep(2); continue; } throw new \RuntimeException('AI request failed: ' . $err); }
            if (in_array($code, [429, 500, 502, 503, 529], true) && $try === 1) { sleep(3); continue; }
            $res = json_decode($body, true);
            if ($code >= 400 || !is_array($res)) { throw new \RuntimeException('AI API error ' . $code . ': ' . substr((string) ($res['error']['message'] ?? $body), 0, 200)); }
            $text = '';
            foreach ($res['content'] ?? [] as $block) { if (($block['type'] ?? '') === 'text') { $text .= $block['text']; } }
            return self::decode($text);
        }
        throw new \RuntimeException('AI request failed.');
    }

    private static function decode(string $text): array
    {
        $text = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text)));
        $data = json_decode($text, true);
        if (!is_array($data) && preg_match('/[\[{].*[\]}]/s', $text, $m)) { $data = json_decode($m[0], true); }
        if (!is_array($data)) { throw new \RuntimeException('AI returned something that was not valid JSON.'); }
        return $data;
    }
}
