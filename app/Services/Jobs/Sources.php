<?php
namespace App\Services\Jobs;

/**
 * Source adapters. Each takes the job_sources row and returns raw postings:
 * [ ['external_id','title','company','location','url','description','posted_at','deadline','salary_text'], ... ]
 * Configure each source in Admin > Job sources. Only add sources whose terms permit automated access.
 */
final class Sources
{
    public static function fetch(array $source): array
    {
        $cfg = json_decode((string) ($source['config'] ?? ''), true) ?: [];
        $raw = match ($source['source_type']) {
            'rss' => self::rss($source, $cfg),
            'jooble' => self::jooble($source, $cfg),
            'json_api' => self::jsonApi($source, $cfg),
            'jsonld' => self::jsonLd($source, $cfg),
            default => throw new \RuntimeException('Unknown source type.'),
        };
        return array_slice($raw, 0, (int) config('jobs.max_items_per_source', 100));
    }

    /** Replace {{env:NAME}} in config strings so API keys stay in .env, not in the database. */
    private static function env(string $s): string
    {
        return preg_replace_callback('/\{\{env:([A-Z0-9_]+)\}\}/', fn($m) => (string) env($m[1], ''), $s);
    }

    private static function dig(array $a, string $path): mixed
    {
        foreach (explode('.', $path) as $k) { if (!is_array($a) || !array_key_exists($k, $a)) { return null; } $a = $a[$k]; }
        return $a;
    }

    // ---- RSS / Atom ----
    private static function rss(array $s, array $cfg): array
    {
        $xml = HttpClient::get(self::env((string) $s['endpoint_url']), [], (bool) ($cfg['respect_robots'] ?? true));
        $prev = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        libxml_use_internal_errors($prev);
        if (!$doc) { throw new \RuntimeException('The feed is not valid XML.'); }
        $out = [];
        $items = $doc->channel->item ?? $doc->entry ?? [];
        foreach ($items as $it) {
            $ns = $it->getNamespaces(true);
            $content = isset($ns['content']) ? (string) $it->children($ns['content'])->encoded : '';
            $link = (string) ($it->link['href'] ?? $it->link ?? '');
            $out[] = [
                'external_id' => (string) ($it->guid ?? $it->id ?? $link), 'title' => (string) $it->title, 'company' => null,
                'location' => null, 'url' => $link, 'description' => $content ?: (string) ($it->description ?? $it->summary ?? ''),
                'posted_at' => (string) ($it->pubDate ?? $it->published ?? $it->updated ?? ''), 'deadline' => null, 'salary_text' => null,
            ];
        }
        return $out;
    }

    // ---- Jooble API (https://jooble.org/api/about) ----
    private static function joobleCall(string $base, string $key, array $body): array
    {
        $raw = HttpClient::postJson(rtrim($base, '/') . '/' . rawurlencode($key), $body);
        $res = json_decode($raw, true);
        if (!is_array($res)) { throw new \RuntimeException('not JSON: ' . mb_substr(trim(strip_tags($raw)), 0, 100)); }
        return $res;
    }

    /** Find a base URL + location that actually returns Nigerian jobs. @return array{0:string,1:string}|null */
    private static function joobleProbe(array $cfg, string $key, array &$notes): ?array
    {
        $bases = (array) ($cfg['base_urls'] ?? ['https://jooble.org/api/', 'https://ng.jooble.org/api/']);
        $locs = (array) ($cfg['locations'] ?? [$cfg['location'] ?? 'Nigeria', 'Lagos', 'Abuja', '']);
        $kw = (string) (((array) ($cfg['keywords'] ?? ['']))[0] ?? '');
        foreach ($bases as $base) {
            foreach ($locs as $loc) {
                $label = parse_url($base, PHP_URL_HOST) . ' / location "' . $loc . '"';
                try {
                    $res = self::joobleCall($base, $key, ['keywords' => $kw, 'location' => $loc, 'page' => 1]);
                    $n = count($res['jobs'] ?? []);
                    $notes[] = "{$label}: HTTP " . HttpClient::$lastCode . ", {$n} jobs (total " . ($res['totalCount'] ?? '?') . ')' . (isset($res['error']) ? ' error=' . json_encode($res['error']) : '');
                    if ($n > 0) { return [$base, $loc]; }
                } catch (\Throwable $e) { $notes[] = "{$label}: " . $e->getMessage(); }
            }
        }
        return null;
    }

    private static function jooble(array $s, array $cfg): array
    {
        $key = (string) config('jobs.jooble_key', '');
        if ($key === '') { throw new \RuntimeException('JOOBLE_API_KEY is not set in .env.'); }
        $notes = [];
        $probe = self::joobleProbe($cfg, $key, $notes);
        if (!$probe) {
            throw new \RuntimeException('Jooble returned no jobs. Tried: ' . implode(' | ', $notes) . '. Check that your Jooble key is enabled for Nigeria, or use another source type.');
        }
        [$base, $loc] = $probe;
        $out = [];
        foreach ((array) ($cfg['keywords'] ?? ['']) as $kw) {
            for ($page = 1; $page <= (int) ($cfg['pages'] ?? 2); $page++) {
                try { $res = self::joobleCall($base, $key, ['keywords' => $kw, 'location' => $loc, 'page' => $page]); } catch (\Throwable $e) { break; }
                foreach ($res['jobs'] ?? [] as $j) {
                    $out[] = ['external_id' => (string) ($j['id'] ?? $j['link'] ?? ''), 'title' => (string) ($j['title'] ?? ''), 'company' => $j['company'] ?? null,
                        'location' => $j['location'] ?? null, 'url' => (string) ($j['link'] ?? ''), 'description' => (string) ($j['snippet'] ?? ''),
                        'posted_at' => $j['updated'] ?? null, 'deadline' => null, 'salary_text' => $j['salary'] ?? null];
                }
                if (empty($res['jobs'])) { break; }
            }
        }
        return $out;
    }

    /** Plain-English report for the admin "Test" button: what the source actually returns, without saving anything. */
    public static function diagnose(array $source): string
    {
        $cfg = json_decode((string) ($source['config'] ?? ''), true) ?: [];
        if ($source['source_type'] === 'jooble') {
            $key = (string) config('jobs.jooble_key', '');
            if ($key === '') { return 'JOOBLE_API_KEY is not set in .env.'; }
            $notes = [];
            $hit = self::joobleProbe($cfg, $key, $notes);
            return ($hit ? 'WORKS with ' . parse_url($hit[0], PHP_URL_HOST) . ' and location "' . $hit[1] . '". ' : 'NO JOBS RETURNED. ') . 'Probes: ' . implode(' | ', $notes);
        }
        try {
            $items = self::fetch($source);
            return count($items) . ' posting(s) found.' . ($items ? ' First: "' . mb_substr((string) $items[0]['title'], 0, 80) . '" (' . ($items[0]['company'] ?? 'no company') . ').' : ' Check the URL and settings (for JSON APIs: items_path and map).');
        } catch (\Throwable $e) { return 'FAILED: ' . $e->getMessage() . (HttpClient::$lastCode ? ' (last HTTP status ' . HttpClient::$lastCode . ')' : ''); }
    }

    // ---- Generic JSON API with a field map ----
    private static function jsonApi(array $s, array $cfg): array
    {
        $headers = [];
        foreach ((array) ($cfg['headers'] ?? []) as $k => $v) { $headers[$k] = self::env((string) $v); }
        $data = json_decode(HttpClient::get(self::env((string) $s['endpoint_url']), $headers, (bool) ($cfg['respect_robots'] ?? false)), true);
        if (!is_array($data)) { throw new \RuntimeException('The API did not return JSON.'); }
        $items = isset($cfg['items_path']) && $cfg['items_path'] !== '' ? self::dig($data, $cfg['items_path']) : $data;
        $m = (array) ($cfg['map'] ?? []);
        $f = function (array $it, string $k) use ($m) {
            if (!isset($m[$k])) { return null; }
            if (is_array($m[$k])) { // several fields joined, e.g. ["job_city","job_state"]
                return implode(', ', array_filter(array_map(fn($p) => is_scalar($v = self::dig($it, $p)) ? trim((string) $v) : '', $m[$k])));
            }
            return self::dig($it, $m[$k]);
        };
        $out = [];
        foreach ((array) $items as $it) {
            if (!is_array($it)) { continue; }
            $out[] = ['external_id' => (string) ($f($it, 'id') ?? $f($it, 'url')), 'title' => (string) $f($it, 'title'), 'company' => is_string($c = $f($it, 'company')) ? $c : null,
                'location' => is_string($l = $f($it, 'location')) ? $l : null, 'url' => (string) $f($it, 'url'), 'description' => is_string($d = $f($it, 'description')) ? $d : '',
                'posted_at' => $f($it, 'posted_at'), 'deadline' => $f($it, 'deadline'), 'salary_text' => is_string($sa = $f($it, 'salary')) ? $sa : null];
        }
        return $out;
    }

    // ---- Pages carrying schema.org JobPosting JSON-LD (only where the site's terms/robots allow) ----
    private static function jsonLd(array $s, array $cfg): array
    {
        $urls = (array) ($cfg['urls'] ?? []);
        if (!empty($s['endpoint_url'])) { array_unshift($urls, $s['endpoint_url']); }
        $out = [];
        $detail = [];
        foreach ($urls as $u) {
            $html = HttpClient::get(self::env((string) $u), [], true);
            if (!empty($cfg['link_pattern'])) { // listing page: discover links to detail pages
                $dom = new \DOMDocument(); $prev = libxml_use_internal_errors(true); $dom->loadHTML($html); libxml_use_internal_errors($prev);
                foreach ($dom->getElementsByTagName('a') as $a) {
                    $href = $a->getAttribute('href');
                    if ($href && @preg_match('#' . str_replace('#', '\#', $cfg['link_pattern']) . '#', $href)) {
                        $abs = parse_url($href, PHP_URL_SCHEME) ? $href : rtrim(preg_replace('#^(https?://[^/]+).*$#', '$1', $u), '/') . '/' . ltrim($href, '/');
                        $detail[$abs] = true;
                        if (count($detail) >= (int) ($cfg['max_detail_pages'] ?? 25)) { break 2; }
                    }
                }
            } else { $out = array_merge($out, self::jobPostings($html, $u)); }
        }
        foreach (array_keys($detail) as $u) { try { $out = array_merge($out, self::jobPostings(HttpClient::get($u, [], true), $u)); } catch (\Throwable $e) { /* skip one bad page */ } }
        return $out;
    }

    private static function jobPostings(string $html, string $pageUrl): array
    {
        $out = [];
        if (!preg_match_all('#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $m)) { return []; }
        $walk = function ($node) use (&$walk, &$out, $pageUrl) {
            if (!is_array($node)) { return; }
            $type = $node['@type'] ?? null;
            if ($type === 'JobPosting' || (is_array($type) && in_array('JobPosting', $type, true))) {
                $loc = $node['jobLocation'] ?? null; $loc = is_array($loc) && isset($loc[0]) ? $loc[0] : $loc;
                $addr = is_array($loc) ? ($loc['address'] ?? []) : [];
                $sal = $node['baseSalary']['value'] ?? null;
                $out[] = ['external_id' => (string) (is_array($node['identifier'] ?? null) ? ($node['identifier']['value'] ?? '') : ($node['identifier'] ?? '')) ?: (string) ($node['url'] ?? $pageUrl),
                    'title' => (string) ($node['title'] ?? ''), 'company' => is_array($node['hiringOrganization'] ?? null) ? ($node['hiringOrganization']['name'] ?? null) : null,
                    'location' => trim(implode(', ', array_filter([$addr['addressLocality'] ?? null, $addr['addressRegion'] ?? null, $addr['addressCountry'] ?? null]))),
                    'url' => (string) ($node['url'] ?? $pageUrl), 'description' => (string) ($node['description'] ?? ''), 'posted_at' => $node['datePosted'] ?? null,
                    'deadline' => $node['validThrough'] ?? null,
                    'salary_text' => is_array($sal) ? trim(($node['baseSalary']['currency'] ?? '') . ' ' . ($sal['minValue'] ?? '') . ' ' . ($sal['maxValue'] ?? '')) : null];
                return;
            }
            foreach ($node as $v) { if (is_array($v)) { $walk($v); } }
        };
        foreach ($m[1] as $json) { $walk(json_decode(html_entity_decode(trim($json)), true)); }
        return $out;
    }
}
