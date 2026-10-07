<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Role.php';
require_once __DIR__ . '/../app/Models/Category.php';
require_once __DIR__ . '/../app/Controllers/RoleController.php';
require_once __DIR__ . '/../app/Controllers/CategoryController.php';
require_once __DIR__ . '/../app/Controllers/ModuleController.php';

$controller = new RoleController();
$categoryController = new CategoryController();
$moduleController = new ModuleController();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Ruta principal que muestra la interfaz
if ($uri === '/' || $uri === '') {
    $controller->index();
    exit;
}

if ($uri === '/roles' && $method === 'GET') {
    $controller->index();
    exit;
}

if ($uri === '/categories' && $method === 'GET') {
    $categoryController->index();
    exit;
}

if (preg_match('#^/api/roles/?$#', $uri)) {
    if ($method === 'GET') {
        $controller->apiList();
    }
    if ($method === 'POST') {
        $controller->apiCreate();
    }
}

if (preg_match('#^/api/roles/(\d+)$#', $uri, $matches)) {
    $id = (int) $matches[1];

    if ($method === 'GET') {
        $controller->apiGet($id);
    }
    if ($method === 'PUT') {
        $controller->apiUpdate($id);
    }
    if ($method === 'DELETE') {
        $controller->apiDelete($id);
    }
}

if (preg_match('#^/api/categories/?$#', $uri)) {
    if ($method === 'GET') $categoryController->apiList();
    if ($method === 'POST') $categoryController->apiCreate();
}

if (preg_match('#^/api/categories/(\d+)$#', $uri, $matches)) {
    $id = (int) $matches[1];
    if ($method === 'GET') $categoryController->apiGet($id);
    if ($method === 'PUT') $categoryController->apiUpdate($id);
    if ($method === 'DELETE') $categoryController->apiDelete($id);
}

if (preg_match('#^/(dashboard|employees|categories|work-orders|forms|contracts)/?$#', $uri, $matches) && $method === 'GET') {
    $moduleController->show($matches[1]);
    exit;
}

$controller->index();