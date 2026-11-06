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
    private static function jooble(array $s, array $cfg): array
    {
        $key = (string) config('jobs.jooble_key', '');
        if ($key === '') { throw new \RuntimeException('JOOBLE_API_KEY is not set in .env.'); }
        $out = [];
        foreach ((array) ($cfg['keywords'] ?? ['']) as $kw) {
            for ($page = 1; $page <= (int) ($cfg['pages'] ?? 2); $page++) {
                $res = json_decode(HttpClient::postJson('https://jooble.org/api/' . rawurlencode($key), ['keywords' => $kw, 'location' => $cfg['location'] ?? 'Nigeria', 'page' => $page]), true);
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

    // ---- Generic JSON API with a field map ----
    private static function jsonApi(array $s, array $cfg): array
    {
        $headers = [];
        foreach ((array) ($cfg['headers'] ?? []) as $k => $v) { $headers[$k] = self::env((string) $v); }
        $data = json_decode(HttpClient::get(self::env((string) $s['endpoint_url']), $headers, (bool) ($cfg['respect_robots'] ?? false)), true);
        if (!is_array($data)) { throw new \RuntimeException('The API did not return JSON.'); }
        $items = isset($cfg['items_path']) && $cfg['items_path'] !== '' ? self::dig($data, $cfg['items_path']) : $data;
        $m = (array) ($cfg['map'] ?? []);
        $f = fn(array $it, string $k) => isset($m[$k]) ? self::dig($it, $m[$k]) : null;
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
