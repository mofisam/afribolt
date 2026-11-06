<?php
namespace App\Services\Jobs;

/** Rule-based cleaning/normalising used before (and as a fallback for) AI. */
final class JobCleaner
{
    public const STATES = ['Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT', 'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara'];

    private const CITIES = [
        'Lagos' => ['lagos', 'ikeja', 'lekki', 'victoria island', 'yaba', 'surulere', 'ikoyi', 'ajah', 'apapa', 'ikorodu', 'oshodi', 'maryland', 'festac'],
        'FCT' => ['abuja', 'fct', 'garki', 'wuse', 'maitama', 'gwarinpa', 'jabi', 'asokoro', 'kubwa', 'lugbe'],
        'Rivers' => ['port harcourt', 'rivers'], 'Oyo' => ['ibadan', 'oyo'], 'Edo' => ['benin city', 'edo'], 'Kano' => ['kano'], 'Enugu' => ['enugu'],
        'Imo' => ['owerri'], 'Akwa Ibom' => ['uyo', 'akwa ibom'], 'Cross River' => ['calabar', 'cross river'], 'Ogun' => ['abeokuta', 'ogun', 'sagamu', 'ota'],
        'Kwara' => ['ilorin', 'kwara'], 'Plateau' => ['jos', 'plateau'], 'Kaduna' => ['kaduna', 'zaria'], 'Borno' => ['maiduguri', 'borno'], 'Delta' => ['warri', 'asaba', 'delta state'],
        'Ondo' => ['akure', 'ondo'], 'Anambra' => ['awka', 'onitsha', 'nnewi', 'anambra'], 'Ebonyi' => ['abakaliki', 'ebonyi'], 'Osun' => ['osogbo', 'ile-ife', 'osun'],
        'Bayelsa' => ['yenagoa', 'bayelsa'], 'Kogi' => ['lokoja', 'kogi'], 'Benue' => ['makurdi', 'benue'], 'Nasarawa' => ['lafia', 'nasarawa'], 'Niger' => ['minna', 'niger state'],
        'Sokoto' => ['sokoto'], 'Katsina' => ['katsina'], 'Bauchi' => ['bauchi'], 'Gombe' => ['gombe'], 'Adamawa' => ['yola', 'adamawa'], 'Ekiti' => ['ado-ekiti', 'ado ekiti', 'ekiti'],
        'Abia' => ['umuahia', 'aba', 'abia'], 'Jigawa' => ['dutse', 'jigawa'], 'Kebbi' => ['birnin kebbi', 'kebbi'], 'Taraba' => ['jalingo', 'taraba'], 'Yobe' => ['damaturu', 'yobe'], 'Zamfara' => ['gusau', 'zamfara'],
    ];

    /** HTML -> clean plain text (keeps paragraph/list breaks). */
    public static function text(?string $html, int $max = 6000): string
    {
        $t = (string) $html;
        $t = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $t);
        $t = preg_replace('#<\s*(br|/p|/div|/h[1-6]|/tr)\s*/?>#i', "\n", $t);
        $t = preg_replace('#<\s*li\b[^>]*>#i', "\n- ", $t);
        $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = preg_replace("/[ \t\x{00A0}]+/u", ' ', $t);
        $t = preg_replace("/\n\s*\n\s*\n+/", "\n\n", trim($t));
        $t = preg_replace('/[^\P{C}\n]+/u', '', $t); // drop control chars
        return mb_substr($t, 0, $max);
    }

    public static function line(?string $s, int $max = 200): string
    {
        return mb_substr(trim(preg_replace('/\s+/u', ' ', self::text($s, $max * 2))), 0, $max);
    }

    /** @return array{0:?string,1:?string} [state, city] */
    public static function place(string $text): array
    {
        $t = ' ' . mb_strtolower($text) . ' ';
        foreach (self::CITIES as $state => $needles) {
            foreach ($needles as $n) {
                if (preg_match('/(?<![a-z])' . preg_quote($n, '/') . '(?![a-z])/', $t)) {
                    $city = in_array($n, ['fct', 'lagos', 'rivers', 'oyo', 'edo', 'kano', 'delta state', 'niger state', 'plateau', 'ondo', 'osun', 'ogun', 'kwara', 'borno'], true) ? null : ucwords($n);
                    return [$state, $city];
                }
            }
        }
        return [null, null];
    }

    public static function workMode(string $t): string
    {
        $t = mb_strtolower($t);
        if (preg_match('/\bhybrid\b/', $t)) { return 'hybrid'; }
        if (preg_match('/\b(remote|work from home|work-from-home|wfh)\b/', $t)) { return 'remote'; }
        if (preg_match('/\b(on-?site|in[- ]office|office-based)\b/', $t)) { return 'onsite'; }
        return 'unspecified';
    }

    public static function employmentType(string $t): string
    {
        $t = mb_strtolower($t);
        return match (true) {
            (bool) preg_match('/\b(intern|internship|trainee|graduate trainee|nysc)\b/', $t) => 'internship',
            (bool) preg_match('/\b(part[- ]time)\b/', $t) => 'part_time',
            (bool) preg_match('/\b(contract|freelance|consultant)\b/', $t) => 'contract',
            (bool) preg_match('/\b(temporary|temp)\b/', $t) => 'temporary',
            (bool) preg_match('/\b(volunteer)\b/', $t) => 'volunteer',
            (bool) preg_match('/\b(full[- ]time|permanent)\b/', $t) => 'full_time',
            default => 'unspecified',
        };
    }

    public static function level(string $t): string
    {
        $t = mb_strtolower($t);
        return match (true) {
            (bool) preg_match('/\b(chief|director|head of|vp|vice president|c[eftmo]o|general manager)\b/', $t) => 'executive',
            (bool) preg_match('/\b(senior|sr\.?|lead|principal|manager)\b/', $t) => 'senior',
            (bool) preg_match('/\b(junior|jr\.?|entry|graduate|trainee|intern|associate|assistant|fresher)\b/', $t) => 'entry',
            (bool) preg_match('/\b(mid[- ]level|intermediate|officer|executive|specialist|analyst)\b/', $t) => 'mid',
            default => 'unspecified',
        };
    }

    /** @return array{0:?float,1:?float,2:?string} [min, max, currency] parsed from free text such as "₦150,000 - ₦250,000" or "N200k". */
    public static function salary(string $t): array
    {
        $cur = null;
        if (preg_match('/₦|\bNGN\b|\bnaira\b|(?<![A-Za-z])N(?=\s?\d)/iu', $t)) { $cur = 'NGN'; }
        elseif (preg_match('/\$|\bUSD\b/i', $t)) { $cur = 'USD'; }
        elseif (preg_match('/£|\bGBP\b/i', $t)) { $cur = 'GBP'; }
        elseif (preg_match('/€|\bEUR\b/i', $t)) { $cur = 'EUR'; }
        if (!$cur || !preg_match_all('/(\d[\d,]*(?:\.\d+)?)\s*(k|m|million)?/i', $t, $m, PREG_SET_ORDER)) { return [null, null, null]; }
        $nums = [];
        foreach ($m as $x) {
            $v = (float) str_replace(',', '', $x[1]);
            $suffix = strtolower($x[2] ?? '');
            if ($suffix === 'k') { $v *= 1000; } elseif ($suffix === 'm' || $suffix === 'million') { $v *= 1000000; }
            if ($v >= 1000) { $nums[] = $v; }
        }
        if (!$nums) { return [null, null, null]; }
        sort($nums);
        return [$nums[0], count($nums) > 1 ? end($nums) : null, $cur];
    }

    public static function canonical(string $title, ?string $company, ?string $state): string
    {
        $n = fn($s) => preg_replace('/[^a-z0-9]+/', ' ', mb_strtolower(trim((string) $s)));
        return sha1($n($title) . '|' . $n($company) . '|' . $n($state));
    }

    public static function date(?string $s): ?string
    {
        if (!$s) { return null; }
        $ts = strtotime($s);
        return ($ts && $ts > 946684800 && $ts < time() + 3 * 365 * 86400) ? gmdate('Y-m-d H:i:s', $ts) : null;
    }
}
