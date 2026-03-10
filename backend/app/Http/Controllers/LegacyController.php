<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;

class LegacyController extends Controller
{
    private string $legacyBasePath;

    public function __construct()
    {
        $this->legacyBasePath = base_path('../frontend');
    }

    public function pos(): Response
    {
        return $this->renderLegacy('views/pos_system.php');
    }

    public function login(): Response
    {
        return $this->renderLegacy('index.php');
    }

    public function processLogin(): Response
    {
        return $this->renderLegacy('process_login.php');
    }

    public function logout(): Response
    {
        return $this->renderLegacy('logout.php');
    }

    public function registerAdmin(): Response
    {
        return $this->renderLegacy('register_admin.php');
    }

    public function view(string $page): Response
    {
        $allowed = [
            'add_product.php',
            'add_stocks.php',
            'categories.php',
            'customers.php',
            'dashboard.php',
            'detailed_sales_report.php',
            'inventory.php',
            'not_found.php',
            'pos_system.php',
            'sales_analytics.php',
            'stock_report.php',
            'suppliers.php',
            'user_management.php',
        ];

        if (!in_array($page, $allowed, true)) {
            return $this->renderLegacy('views/not_found.php');
        }

        return $this->renderLegacy('views/' . $page);
    }

    public function api(string $endpoint): Response
    {
        $allowed = [
            'categories.php',
            'complete_sale.php',
            'customers.php',
            'dashboard.php',
            'inventory.php',
            'products.php',
            'sales_reports.php',
            'stocks.php',
            'suppliers.php',
            'users.php',
        ];

        if (!in_array($endpoint, $allowed, true)) {
            return response()->json(['success' => false, 'message' => 'Not Found'], 404);
        }

        return $this->renderLegacy('api/' . $endpoint);
    }

    private function renderLegacy(string $relativePath): Response
    {
        $fullPath = $this->legacyBasePath . '/' . $relativePath;

        if (!is_file($fullPath)) {
            return response('Not Found', 404);
        }

        ob_start();
        require $fullPath;
        $content = ob_get_clean();

        $status = http_response_code() ?: 200;
        $response = response($content, $status);

        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                $value = trim(substr($header, strlen('Content-Type:')));
                $response->headers->set('Content-Type', $value);
                break;
            }
        }

        return $response;
    }
}
