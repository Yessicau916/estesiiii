<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/Employee.php';

class AuthController {
    public function showLogin(string $error = ''): void {
        require __DIR__ . '/../Views/auth/login.php';
    }

    public function login(): void {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $employee = $email !== '' ? Employee::findForLogin($email) : null;

        if (!$employee || (string) $employee['estado'] !== '1' || !password_verify($password, $employee['password_hash'])) {
            http_response_code(401);
            $this->showLogin('Correo o contraseña incorrectos.');
            return;
        }

        session_regenerate_id(true);
        $_SESSION['employee_id'] = (int) $employee['id'];
        $_SESSION['employee_name'] = trim($employee['nombres'] . ' ' . $employee['apellidos']);
        $_SESSION['role_id'] = (int) $employee['rol_id'];
        header('Location: /dashboard');
        exit;
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        header('Location: /login');
        exit;
    }
}
