<?php
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only administrators can view audit logs.']);
    exit();
}

$startDate = trim((string) ($_GET['start_date'] ?? ''));
$endDate = trim((string) ($_GET['end_date'] ?? ''));
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = isset($_GET['per_page']) && is_numeric($_GET['per_page']) ? max(1, min(100, (int) $_GET['per_page'])) : 20;
$eventType = trim((string) ($_GET['event_type'] ?? ''));
$auditableType = trim((string) ($_GET['auditable_type'] ?? ''));
$auditableId = isset($_GET['auditable_id']) && is_numeric($_GET['auditable_id']) ? (int) $_GET['auditable_id'] : null;
$userId = isset($_GET['user_id']) && is_numeric($_GET['user_id']) ? (int) $_GET['user_id'] : null;

try {
    if (!\Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'message' => 'Audit trail table not found. Please run "php artisan migrate" in the backend first.',
        ]);
        exit();
    }

    $query = \App\Models\AuditLog::query()
        ->with('user:id,username,role')
        ->orderByDesc('created_at');

    if ($startDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        $query->where('created_at', '>=', $startDate . ' 00:00:00');
    }

    if ($endDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        $query->where('created_at', '<=', $endDate . ' 23:59:59');
    }

    if ($eventType !== '') {
        $query->where('event_type', $eventType);
    }

    if ($auditableType !== '') {
        $query->where('auditable_type', $auditableType);
    }

    if ($auditableId !== null) {
        $query->where('auditable_id', $auditableId);
    }

    if ($userId !== null) {
        $query->where('user_id', $userId);
    }

    $logs = $query->paginate($perPage, ['*'], 'page', $page);

    $data = $logs->getCollection()->map(function (\App\Models\AuditLog $log): array {
        return [
            'id' => $log->id,
            'user_id' => $log->user_id,
            'event_type' => $log->event_type,
            'auditable_type' => $log->auditable_type,
            'auditable_id' => $log->auditable_id,
            'message' => $log->message,
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
            'created_at' => optional($log->created_at)->format('Y-m-d H:i:s'),
            'user' => $log->user ? [
                'id' => $log->user->id,
                'username' => $log->user->username,
                'role' => $log->user->role,
            ] : null,
        ];
    })->values();

    echo json_encode([
        'success' => true,
        'data' => $data,
        'meta' => [
            'current_page' => $logs->currentPage(),
            'per_page' => $logs->perPage(),
            'total' => $logs->total(),
            'last_page' => $logs->lastPage(),
        ],
    ]);
} catch (Throwable $e) {
    error_log('Audit log API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load audit logs right now.']);
}
