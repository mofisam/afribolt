<?php
// * * * * *  php /path/to/afribolt-platform/cron/send_queued_emails.php
require __DIR__ . '/bootstrap.php';
$n = (new App\Services\MailService())->sendQueued(25);
echo gmdate('c') . " sent {$n} email(s)\n";
