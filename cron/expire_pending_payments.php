<?php
// */15 * * * *  php /path/to/afribolt-platform/cron/expire_pending_payments.php
// Releases seats held by students who registered but never paid (hold time: config/payment.php).
require __DIR__ . '/bootstrap.php';

$minutes = (int) config('payment.pending_hold_minutes', 60);
$n = App\Core\Database::execute(
    "UPDATE enrolments e SET e.status = 'cancelled', e.cancelled_at = UTC_TIMESTAMP(),
            e.admin_notes = CONCAT(COALESCE(e.admin_notes, ''), 'Auto-cancelled: payment not completed. ')
     WHERE e.status = 'pending_payment'
       AND e.registered_at < (UTC_TIMESTAMP() - INTERVAL ? MINUTE)
       AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.enrolment_id = e.id
                       AND (p.status = 'successful' OR (p.status IN ('initiated','pending') AND p.created_at > (UTC_TIMESTAMP() - INTERVAL ? MINUTE))))",
    [$minutes, $minutes]
);
echo gmdate('c') . " released {$n} unpaid seat(s)\n";
