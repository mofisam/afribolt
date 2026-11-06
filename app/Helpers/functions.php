<?php
use App\Core\{Config, Csrf, Request, Session};

function env(string $key, mixed $default = null): mixed { return Config::env($key, $default); }
function config(string $key, mixed $default = null): mixed { return Config::get($key, $default); }
function base_path(string $path = ''): string { return Config::basePath() . ($path ? '/' . ltrim($path, '/') : ''); }

/** HTML-escape. Use on EVERY value printed in a view. */
function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function url(string $path = ''): string
{
    $base = rtrim((string) config('app.url', ''), '/');
    if ($base === '') {
        $base = Request::basePath();
    }
    return $base . '/' . ltrim($path, '/');
}
function asset(string $path): string { return url('assets/' . ltrim($path, '/')); }
function csrf_field(): string { return Csrf::field(); }
function csrf_token(): string { return Csrf::token(); }
function old(string $key, mixed $default = ''): mixed { return Session::old($key, $default); }
function field_error(string $key): string
{
    $m = Session::error($key);
    return $m ? '<p class="mt-1 text-sm text-red-600">' . e($m) . '</p>' : '';
}

function slugify(string $text): string
{
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $t = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $t !== false ? $t : $text), '-'));
    return $t !== '' ? $t : 'item';
}

/** 3725 -> "1:02:05" (or "2:05"). With $long: "1 hr 2 min". */
function fmt_duration(int $seconds, bool $long = false): string
{
    $h = intdiv($seconds, 3600); $m = intdiv($seconds % 3600, 60); $s = $seconds % 60;
    if ($long) { return $h > 0 ? $h . ' hr' . ($m ? " {$m} min" : '') : max(1, $m) . ' min'; }
    return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
}

/**
 * Safe "markdown-lite" for lesson articles. Everything is HTML-escaped first, then a small set of
 * formatting is re-added: #/##/### headings, **bold**, *italic*, `code`, ``` fenced code ```,
 * - bullets, 1. numbered, > quotes and [text](https://link) links.
 */
function render_content(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $blocks = [];
    $text = preg_replace_callback('/```[a-zA-Z]*\n(.*?)```/s', function ($m) use (&$blocks) {
        $blocks[] = '<pre><code>' . e(rtrim($m[1])) . '</code></pre>';
        return "\n@@BLOCK" . (count($blocks) - 1) . "@@\n";
    }, $text);

    $inline = function (string $s): string {
        $s = e($s);
        $s = preg_replace('/`([^`]+)`/', '<code>$1</code>', $s);
        $s = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $s);
        $s = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $s);
        return preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', fn($m) => '<a href="' . $m[2] . '" target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>', $s);
    };

    $out = []; $list = null; $para = [];
    $flush = function () use (&$out, &$para, $inline) { if ($para) { $out[] = '<p>' . implode('<br>', array_map($inline, $para)) . '</p>'; $para = []; } };
    $close = function () use (&$out, &$list) { if ($list) { $out[] = "</{$list}>"; $list = null; } };

    foreach (explode("\n", $text) as $line) {
        $t = trim($line);
        if ($t === '') { $flush(); $close(); continue; }
        if (preg_match('/^@@BLOCK(\d+)@@$/', $t, $m)) { $flush(); $close(); $out[] = $blocks[(int) $m[1]]; continue; }
        if (preg_match('/^(#{1,3})\s+(.+)$/', $t, $m)) { $flush(); $close(); $n = strlen($m[1]) + 1; $out[] = "<h{$n}>" . $inline($m[2]) . "</h{$n}>"; continue; }
        if (preg_match('/^[-*]\s+(.+)$/', $t, $m)) { $flush(); if ($list !== 'ul') { $close(); $out[] = '<ul>'; $list = 'ul'; } $out[] = '<li>' . $inline($m[1]) . '</li>'; continue; }
        if (preg_match('/^\d+[.)]\s+(.+)$/', $t, $m)) { $flush(); if ($list !== 'ol') { $close(); $out[] = '<ol>'; $list = 'ol'; } $out[] = '<li>' . $inline($m[1]) . '</li>'; continue; }
        if (preg_match('/^>\s?(.*)$/', $t, $m)) { $flush(); $close(); $out[] = '<blockquote>' . $inline($m[1]) . '</blockquote>'; continue; }
        $close();
        $para[] = $t;
    }
    $flush(); $close();
    return implode("\n", $out);
}

/** "3 days ago" style label for a UTC timestamp. */
function time_ago(?string $utc): string
{
    if (!$utc) { return ''; }
    $s = max(0, time() - strtotime($utc . ' UTC'));
    return match (true) {
        $s < 3600 => max(1, intdiv($s, 60)) . ' min ago', $s < 86400 => intdiv($s, 3600) . ' hr ago',
        $s < 7 * 86400 => ($d = intdiv($s, 86400)) . ($d === 1 ? ' day ago' : ' days ago'), $s < 60 * 86400 => ($w = intdiv($s, 7 * 86400)) . ($w === 1 ? ' week ago' : ' weeks ago'),
        default => local_date($utc, null, 'j M Y'),
    };
}

/** Human salary string for a job row (parsed numbers first, else the raw text if short). */
function job_salary(array $j): string
{
    $a = (float) ($j['salary_min'] ?? 0); $b = (float) ($j['salary_max'] ?? 0); $cur = $j['salary_currency'] ?: 'NGN';
    if ($a > 0 && $b > 0 && $b != $a) { return money($a, $cur) . ' - ' . money($b, $cur); }
    if ($a > 0 || $b > 0) { return money($a ?: $b, $cur); }
    $t = trim((string) ($j['salary_text'] ?? ''));
    return mb_strlen($t) <= 40 ? $t : '';
}

function job_labels(): array
{
    return ['type' => ['full_time' => 'Full time', 'part_time' => 'Part time', 'contract' => 'Contract', 'internship' => 'Internship', 'temporary' => 'Temporary', 'volunteer' => 'Volunteer', 'unspecified' => ''],
            'mode' => ['onsite' => 'On-site', 'remote' => 'Remote', 'hybrid' => 'Hybrid', 'unspecified' => ''],
            'level' => ['entry' => 'Entry level', 'mid' => 'Mid level', 'senior' => 'Senior', 'executive' => 'Executive', 'unspecified' => '']];
}
