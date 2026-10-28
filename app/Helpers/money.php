<?php
function money(float|string|null $amount, string $currency = 'NGN'): string
{
    $symbols = ['NGN' => '₦', 'USD' => '$', 'GBP' => '£', 'EUR' => '€', 'GHS' => 'GH₵', 'KES' => 'KSh '];
    if ((float) $amount == 0.0) { return 'Free'; }
    return ($symbols[$currency] ?? $currency . ' ') . number_format((float) $amount, 2);
}
/** Gateways take minor units (kobo/cents). Avoid floats: round once. */
function to_minor(float|string $amount): int { return (int) round(((float) $amount) * 100); }
