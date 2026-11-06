<?php
namespace App\Services\Jobs;

/** Fetches public URLs for the job sources: identifies itself, honours robots.txt, rate-limits per host, blocks private networks. */
final class HttpClient
{
    private static array $robots = [];
    private static array $last = [];

    private static function guard(string $url): array
    {
        $p = parse_url($url);
        if (!$p || !in_array($p['scheme'] ?? '', ['http', 'https'], true) || empty($p['host'])) { throw new \RuntimeException('Only http(s) URLs are allowed.'); }
        $ips = gethostbynamel($p['host']) ?: [];
        if (!$ips) { throw new \RuntimeException('Could not resolve ' . $p['host']); }
        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) { throw new \RuntimeException('Blocked: private or reserved address.'); }
        }
        return $p;
    }

    private static function wait(string $host): void
    {
        $gap = (int) config('jobs.request_delay_ms', 600) * 1000;
        $since = (microtime(true) - (self::$last[$host] ?? 0)) * 1e6;
        if ($since < $gap) { usleep((int) ($gap - $since)); }
        self::$last[$host] = microtime(true);
    }

    public static function allowedByRobots(string $url): bool
    {
        $p = parse_url($url); $host = $p['host'] ?? '';
        if (!isset(self::$robots[$host])) {
            self::$robots[$host] = [];
            try { $txt = self::request(($p['scheme'] ?? 'https') . '://' . $host . '/robots.txt', 'GET', null, [], false); }
            catch (\Throwable $e) { $txt = ''; }
            $applies = false; $rules = [];
            foreach (preg_split('/\R/', $txt) as $line) {
                $line = trim(preg_replace('/#.*/', '', $line));
                if (stripos($line, 'user-agent:') === 0) { $ua = strtolower(trim(substr($line, 11))); $applies = ($ua === '*' || str_contains($ua, 'afriboltjobsbot')); }
                elseif ($applies && stripos($line, 'disallow:') === 0) { $d = trim(substr($line, 9)); if ($d !== '') { $rules[] = $d; } }
            }
            self::$robots[$host] = $rules;
        }
        $path = ($p['path'] ?? '/') . (isset($p['query']) ? '?' . $p['query'] : '');
        foreach (self::$robots[$host] as $d) { if (str_starts_with($path, rtrim($d, '*'))) { return false; } }
        return true;
    }

    /** @return string response body */
    public static function get(string $url, array $headers = [], bool $robots = true): string
    {
        if ($robots && !self::allowedByRobots($url)) { throw new \RuntimeException('robots.txt disallows fetching ' . $url); }
        return self::request($url, 'GET', null, $headers);
    }

    public static function postJson(string $url, array $data, array $headers = []): string
    {
        return self::request($url, 'POST', json_encode($data), $headers + ['content-type' => 'application/json']);
    }

    private static function request(string $url, string $method, ?string $body, array $headers, bool $polite = true): string
    {
        $max = (int) config('jobs.max_bytes', 3145728);
        for ($hop = 0; $hop <= 3; $hop++) {
            $p = self::guard($url);
            if ($polite) { self::wait($p['host']); }
            $ip = gethostbynamel($p['host'])[0];
            $port = $p['port'] ?? (($p['scheme'] === 'https') ? 443 : 80);
            $h = ['User-Agent: ' . config('jobs.user_agent'), 'Accept: */*'];
            foreach ($headers as $k => $v) { $h[] = is_int($k) ? $v : "{$k}: {$v}"; }
            $buf = ''; $location = null;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $h, CURLOPT_TIMEOUT => (int) config('jobs.timeout', 20), CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS, CURLOPT_ENCODING => '',
                CURLOPT_RESOLVE => ["{$p['host']}:{$port}:{$ip}"], // pin the IP we validated (no DNS rebinding)
                CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$location) { if (stripos($line, 'location:') === 0) { $location = trim(substr($line, 9)); } return strlen($line); },
                CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$buf, $max) { $buf .= $chunk; return strlen($buf) > $max ? 0 : strlen($chunk); },
            ]);
            if ($body !== null) { curl_setopt($ch, CURLOPT_POSTFIELDS, $body); }
            $ok = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if (in_array($code, [301, 302, 303, 307, 308], true) && $location) {
                $url = (parse_url($location, PHP_URL_SCHEME) ? $location : ($p['scheme'] . '://' . $p['host'] . '/' . ltrim($location, '/')));
                if ($code === 303) { $method = 'GET'; $body = null; }
                continue;
            }
            if ($ok === false && strlen($buf) <= $max) { throw new \RuntimeException('Request failed: ' . $err); }
            if ($code >= 400) { throw new \RuntimeException("HTTP {$code} from " . $p['host']); }
            return $buf;
        }
        throw new \RuntimeException('Too many redirects.');
    }
}
