<?php
/**
 * Front controller. Every request enters here.
 * Only the /public folder should be the web server's document root.
 */
define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/Autoloader.php';
App\Core\Autoloader::register(BASE_PATH . '/app');

require BASE_PATH . '/app/Helpers/functions.php';
require BASE_PATH . '/app/Helpers/date.php';
require BASE_PATH . '/app/Helpers/money.php';

(new App\Core\App(BASE_PATH))->run();
