<?php
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.',
    ]);
    exit;
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required.',
    ]);
    exit;
}

require_once LEGACY_BASE_PATH . '/config/db.php';
require_once LEGACY_BASE_PATH . '/includes/analytics/AnalyticsRepository.php';
require_once LEGACY_BASE_PATH . '/includes/analytics/AnalyticsService.php';

try {
    $repository = new AnalyticsRepository($pdo);
    $service = new AnalyticsService($repository);
    $dashboard = $service->buildDashboard($_GET);

    echo json_encode($dashboard);
} catch (Throwable $throwable) {
    error_log('Sales analytics API error: ' . $throwable->getMessage());
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to load analytics data right now.',
    ]);
}
