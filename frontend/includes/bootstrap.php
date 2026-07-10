<?php
// Legacy app bootstrap for path + session consistency.
if (!defined('LEGACY_BASE_PATH')) {
    define('LEGACY_BASE_PATH', dirname(__DIR__));
}
if (!defined('LEGACY_BASE_URL')) {
    $baseUrl = getenv('LEGACY_BASE_URL');

    if ($baseUrl === false || $baseUrl === '') {
        $appUrl = getenv('APP_URL');
        if ($appUrl !== false && $appUrl !== '') {
            $baseUrl = $appUrl;
        }
    }

    if ($baseUrl === false || $baseUrl === '') {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseUrl = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($baseUrl === '/') {
            $baseUrl = '';
        }

        // If routed through /views or /api, normalize back to app root.
        foreach (['/views', '/api', '/public'] as $segment) {
            if ($baseUrl === $segment) {
                $baseUrl = '';
                break;
            }
            if (str_ends_with($baseUrl, $segment)) {
                $baseUrl = substr($baseUrl, 0, -strlen($segment));
                break;
            }
        }
    }

    $baseUrl = rtrim($baseUrl, '/');
    define('LEGACY_BASE_URL', $baseUrl);
}
if (session_status() === PHP_SESSION_NONE) {
    $sessionPath = session_save_path();

    if ($sessionPath === '' || !is_dir($sessionPath) || !is_writable($sessionPath)) {
        $fallbackSessionPath = dirname(__DIR__, 2) . '/backend/storage/framework/sessions';

        if (!is_dir($fallbackSessionPath)) {
            mkdir($fallbackSessionPath, 0777, true);
        }

        if (is_dir($fallbackSessionPath) && is_writable($fallbackSessionPath)) {
            session_save_path($fallbackSessionPath);
        }
    }

    session_start();
}
?>
