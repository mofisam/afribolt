<?php
/** Shared CLI bootstrap for cron jobs and the seed script. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Core/Autoloader.php';
App\Core\Autoloader::register(BASE_PATH . '/app');
require BASE_PATH . '/app/Helpers/functions.php';
require BASE_PATH . '/app/Helpers/date.php';
require BASE_PATH . '/app/Helpers/money.php';
App\Core\Config::boot(BASE_PATH);
date_default_timezone_set('UTC');
