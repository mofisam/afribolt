<?php
namespace App\Services;

/** Recognises the video links we allow: YouTube, Vimeo, or a direct https .mp4/.webm file. Nothing else is embedded. */
final class VideoService
{
    /** @return array{provider:string, id?:string, hash?:?string, url?:string}|null */
    public static function parse(string $url): ?array
    {
        $url = trim($url);
        if (!preg_match('#^https://#i', $url)) { return null; }
        $host = preg_replace('/^www\./', '', strtolower((string) parse_url($url, PHP_URL_HOST)));
        $path = (string) parse_url($url, PHP_URL_PATH);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $q);

        $id = '';
        if ($host === 'youtu.be') { $id = trim($path, '/'); }
        elseif (in_array($host, ['youtube.com', 'm.youtube.com', 'youtube-nocookie.com'], true)) {
            $id = (string) ($q['v'] ?? (preg_match('#^/(?:embed|shorts|live)/([\w-]{11})#', $path, $m) ? $m[1] : ''));
        }
        if ($id !== '' && preg_match('/^[\w-]{11}$/', $id)) { return ['provider' => 'youtube', 'id' => $id]; }

        if (in_array($host, ['vimeo.com', 'player.vimeo.com'], true) && preg_match('#/(?:video/)?(\d+)(?:/([a-f0-9]+))?#', $path, $m)) {
            return ['provider' => 'vimeo', 'id' => $m[1], 'hash' => $m[2] ?? ($q['h'] ?? null)];
        }
        if (preg_match('#\.(mp4|webm)$#i', $path)) { return ['provider' => 'file', 'url' => $url]; }
        return null;
    }

    public static function embedUrl(array $v): string
    {
        if ($v['provider'] === 'youtube') {
            return 'https://www.youtube-nocookie.com/embed/' . $v['id'] . '?rel=0&modestbranding=1&playsinline=1&enablejsapi=1';
        }
        if ($v['provider'] === 'vimeo') {
            return 'https://player.vimeo.com/video/' . $v['id'] . (!empty($v['hash']) ? '?h=' . $v['hash'] : '');
        }
        return $v['url'] ?? '';
    }
}
