<?php
// Legacy app bootstrap for path + session consistency.
if (!defined('LEGACY_BASE_PATH')) {
    define('LEGACY_BASE_PATH', dirname(__DIR__));
}
if (!defined('LEGACY_BASE_URL')) {
    $baseUrl = getenv('LEGACY_BASE_URL');
    if ($baseUrl === false || $baseUrl === '') {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseUrl = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($baseUrl === '/') {
            $baseUrl = '';
        }
    }
    $baseUrl = rtrim($baseUrl, '/');
    define('LEGACY_BASE_URL', $baseUrl);
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
