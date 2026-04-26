<?php
declare(strict_types=1);

/**
 * Bootstrap PHPUnit pour le thème G2RD.
 */

$autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__, 2) . '/');
}
