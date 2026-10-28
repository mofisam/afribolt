<?php
/** All DB timestamps are UTC. These helpers convert for display only. */

function utc_now(): string { return gmdate('Y-m-d H:i:s'); }

function utc_dt(?string $utc): ?DateTimeImmutable
{
    return $utc ? new DateTimeImmutable($utc, new DateTimeZone('UTC')) : null;
}

/** ISO-8601 with Z, for JS countdowns: 2026-11-02T09:00:00Z */
function utc_iso(?string $utc): ?string
{
    $d = utc_dt($utc);
    return $d ? $d->format('Y-m-d\TH:i:s\Z') : null;
}

/** Format a UTC DB value in the cohort/session timezone (defaults to config app.timezone). */
function local_date(?string $utc, ?string $tz = null, string $format = 'D, j M Y, g:i A'): string
{
    $d = utc_dt($utc);
    if (!$d) { return ''; }
    return $d->setTimezone(new DateTimeZone($tz ?: config('app.timezone', 'Africa/Lagos')))->format($format);
}

/** Convert a "2026-11-02 09:00" value typed in $tz (admin form) into a UTC DB string. */
function local_to_utc(string $local, string $tz): string
{
    return (new DateTimeImmutable($local, new DateTimeZone($tz)))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}
