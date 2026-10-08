<?php
declare(strict_types=1);

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Role.php';
require_once __DIR__ . '/../app/Models/Category.php';
require_once __DIR__ . '/../app/Models/Employee.php';
require_once __DIR__ . '/../app/Controllers/RoleController.php';
require_once __DIR__ . '/../app/Controllers/CategoryController.php';
require_once __DIR__ . '/../app/Controllers/EmployeeController.php';
require_once __DIR__ . '/../app/Controllers/ModuleController.php';
require_once __DIR__ . '/../app/Controllers/WorkOrderController.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

$controller = new RoleController();
$categoryController = new CategoryController();
$employeeController = new EmployeeController();
$moduleController = new ModuleController();
$workOrderController = new WorkOrderController();
$authController = new AuthController();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (($uri === '/login' || $uri === '/login/') && $method === 'GET') {
    $authController->showLogin();
    exit;
}

if (($uri === '/login' || $uri === '/login/') && $method === 'POST') {
    $authController->login();
    exit;
}

if ($uri === '/logout' && $method === 'POST') {
    $authController->logout();
    exit;
}

if (empty($_SESSION['employee_id'])) {
    if (str_starts_with($uri, '/api/')) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => 'Inicia sesión para continuar.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: /login');
    exit;
}

if (empty($_SESSION['role_id']) && !empty($_SESSION['employee_id'])) {
    $sessionEmployee = Employee::findById((int) $_SESSION['employee_id']);
    if ($sessionEmployee) $_SESSION['role_id'] = (int) $sessionEmployee['rol_id'];
}
$currentRole = Role::findById((int) ($_SESSION['role_id'] ?? 0));
if (!$currentRole) {
    http_response_code(403);
    echo 'Tu usuario no tiene un rol válido asignado. Contacta al administrador.';
    exit;
}
$rolePermissions = $currentRole->getPermissions();

$requiredPermission = null;
if (preg_match('#^/api/employees(?:/\d+)?/?$#', $uri)) {
    $action = $method === 'GET' ? 'ver' : ($method === 'POST' ? 'crear' : ($method === 'PUT' ? 'editar' : ($method === 'DELETE' ? 'eliminar' : null)));
    if ($action) $requiredPermission = ['employees', $action];
} elseif (preg_match('#^/api/categories(?:/\d+)?/?$#', $uri)) {
    $action = $method === 'GET' ? 'ver' : ($method === 'POST' ? 'crear' : ($method === 'PUT' ? 'editar' : ($method === 'DELETE' ? 'eliminar' : null)));
    if ($action) $requiredPermission = ['categories', $action];
} elseif (preg_match('#^/api/roles(?:/\d+)?/?$#', $uri)) {
    $action = $method === 'GET' ? 'ver' : ($method === 'POST' ? 'crear' : ($method === 'PUT' ? 'editar' : ($method === 'DELETE' ? 'eliminar' : null)));
    if ($action) $requiredPermission = ['roles', $action];
} elseif ($uri === '/api/directory-users') {
    $requiredPermission = ['employees', 'crear'];
} elseif ($uri === '/work-orders' && $method === 'GET' && ($_GET['export'] ?? '') === 'csv') {
    $requiredPermission = ['work_orders', 'exportar'];
} elseif (preg_match('#^/work-orders/(create|update|delete|bulk-delete|import)(?:/\d+)?$#', $uri, $workOrderActionMatch)) {
    $actionMap = ['create' => 'crear', 'update' => 'editar', 'delete' => 'eliminar', 'bulk-delete' => 'eliminar_masivo', 'import' => 'importar'];
    $requiredPermission = ['work_orders', $actionMap[$workOrderActionMatch[1]]];
} elseif (preg_match('#^/(dashboard|employees|categories|work-orders|forms|contracts|assignments|regions)/?$#', $uri, $moduleMatches)) {
    $moduleId = ['work-orders' => 'work_orders', 'assignments' => 'contracts'][$moduleMatches[1]] ?? $moduleMatches[1];
    $requiredPermission = [$moduleId, 'ver'];
} elseif ($uri === '/roles') {
    $requiredPermission = ['roles', 'ver'];
}

$hasRequiredPermission = !$requiredPermission || $currentRole->hasPermission($requiredPermission[0], $requiredPermission[1]);
if (!$hasRequiredPermission && $requiredPermission[1] === 'ver') {
    // A role with any action in a module may open its list; row-level Ver stays an independent permission.
    $hasRequiredPermission = !empty($rolePermissions[$requiredPermission[0]]);
}
if ($requiredPermission && $uri !== '/dashboard' && !$hasRequiredPermission) {
    http_response_code(403);
    if (str_starts_with($uri, '/api/')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => 'Tu rol no tiene permiso para realizar esta acción.'], JSON_UNESCAPED_UNICODE);
    } else {
        echo 'No tienes permiso para acceder a este módulo.';
    }
    exit;
}

if ($uri === '/' || $uri === '' || $uri === '/index.php') {
    header('Location: /login');
    exit;
    http_response_code(403);
    echo 'Tu rol no tiene permisos de consulta para ningún módulo.';
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

if ($uri === '/employees' && $method === 'GET') {
    $employeeController->index();
    exit;
}

if ($uri === '/work-orders' && $method === 'GET') {
    if (isset($_GET['export']) && $_GET['export'] === 'csv') $workOrderController->export();
    $workOrderController->index();
    exit;
}
if ($uri === '/work-orders/create' && $method === 'POST') { $workOrderController->create(); exit; }
if (preg_match('#^/work-orders/update/(\d+)$#', $uri, $workOrderMatches) && $method === 'POST') { $workOrderController->update((int) $workOrderMatches[1]); exit; }
if (preg_match('#^/work-orders/delete/(\d+)$#', $uri, $workOrderMatches) && $method === 'POST') { $workOrderController->delete((int) $workOrderMatches[1]); exit; }
if ($uri === '/work-orders/bulk-delete' && $method === 'POST') { $workOrderController->deleteMany(); exit; }
if ($uri === '/work-orders/import' && $method === 'POST') { $workOrderController->import(); exit; }

if ($uri === '/api/directory-users' && $method === 'GET') {
    $employeeController->apiDirectorySearch();
}

if (preg_match('#^/api/employees/?$#', $uri)) {
    if ($method === 'GET') $employeeController->apiList();
    if ($method === 'POST') $employeeController->apiCreate();
}

if (preg_match('#^/api/employees/(\d+)$#', $uri, $matches)) {
    $id = (int) $matches[1];
    if ($method === 'GET') $employeeController->apiGet($id);
    if ($method === 'PUT') $employeeController->apiUpdate($id);
    if ($method === 'DELETE') $employeeController->apiDelete($id);
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

if (preg_match('#^/(dashboard|employees|categories|work-orders|forms|contracts|assignments|regions)/?$#', $uri, $matches) && $method === 'GET') {
    $moduleController->show($matches[1]);
    exit;
}

$controller->index();
