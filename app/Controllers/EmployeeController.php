<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Services/ActiveDirectory.php';

class EmployeeController {
    private const DOCUMENT_TYPES = ['CC', 'CE', 'TI', 'PASAPORTE', 'NIT'];

    public function index(): void {
        $activeModule = 'employees';
        $roles = Employee::findRoles();
        require __DIR__ . '/../Views/employees/index.php';
    }

    public function apiList(): void {
        $this->jsonResponse(200, Employee::findAll());
    }

    public function apiDirectorySearch(): void {
        $query = trim((string) ($_GET['q'] ?? ''));
        if (mb_strlen($query) < 2 && !ActiveDirectory::isMockMode()) {
            $this->jsonResponse(422, ['message' => 'Escribe al menos 2 caracteres para buscar.']);
            return;
        }

        try {
            $users = array_values(array_filter(
                ActiveDirectory::search($query),
                static fn (array $user): bool => !Employee::directoryAccountAssigned($user['id'])
            ));
            $this->jsonResponse(200, $users);
        } catch (RuntimeException $error) {
            $this->jsonResponse(503, ['message' => $error->getMessage()]);
        } catch (Throwable) {
            $this->jsonResponse(503, ['message' => 'No se pudo consultar Active Directory.']);
        }
    }

    public function apiGet(int $id): void {
        $employee = Employee::findById($id);
        if (!$employee) {
            $this->jsonResponse(404, ['message' => 'Empleado no encontrado']);
            return;
        }
        $this->jsonResponse(200, $employee);
    }

    public function apiCreate(): void {
        $payload = $this->readInput();
        $errors = $this->validate($payload, true);
        if ($errors !== []) {
            $this->jsonResponse(422, ['message' => reset($errors), 'errors' => $errors]);
            return;
        }

        try {
            if (!ActiveDirectory::accountExists((string) $payload['directorio_id'])) {
                $this->jsonResponse(422, ['message' => 'La cuenta seleccionada no existe o no está activa en Active Directory.']);
                return;
            }
        } catch (RuntimeException $error) {
            $this->jsonResponse(503, ['message' => $error->getMessage()]);
            return;
        } catch (Throwable) {
            $this->jsonResponse(503, ['message' => 'No se pudo validar el usuario con Active Directory.']);
            return;
        }

        $signaturePath = $this->storeSignature((string) ($payload['signature'] ?? ''));
        if (($payload['signature'] ?? '') !== '' && $signaturePath === null) {
            $this->jsonResponse(422, ['message' => 'La firma debe ser una imagen PNG válida de máximo 2 MB.']);
            return;
        }

        try {
            $id = Employee::create(
                $this->normalizePayload($payload),
                password_hash((string) $payload['password'], PASSWORD_DEFAULT),
                $signaturePath
            );
            $this->jsonResponse(201, ['message' => 'Empleado creado correctamente', 'id' => $id]);
        } catch (PDOException $error) {
            $this->removeSignature($signaturePath);
            $this->databaseError($error);
        }
    }

    public function apiUpdate(int $id): void {
        $employee = Employee::findById($id);
        if (!$employee) {
            $this->jsonResponse(404, ['message' => 'Empleado no encontrado']);
            return;
        }

        $payload = $this->readInput();
        $errors = $this->validate($payload, false);
        if ($errors !== []) {
            $this->jsonResponse(422, ['message' => reset($errors), 'errors' => $errors]);
            return;
        }

        $signatureData = (string) ($payload['signature'] ?? '');
        $signaturePath = $signatureData !== '' ? $this->storeSignature($signatureData) : null;
        if ($signatureData !== '' && $signaturePath === null) {
            $this->jsonResponse(422, ['message' => 'La firma debe ser una imagen PNG válida de máximo 2 MB.']);
            return;
        }

        try {
            $removeSignature = $signaturePath === null && !empty($payload['remove_signature']);
            Employee::update(
                $id,
                $this->normalizePayload($payload),
                !empty($payload['password']) ? password_hash((string) $payload['password'], PASSWORD_DEFAULT) : null,
                $signaturePath,
                $removeSignature
            );
            if ($signaturePath !== null || $removeSignature) {
                $this->removeSignature($employee['firma_ruta']);
            }
            $this->jsonResponse(200, ['message' => 'Empleado actualizado correctamente']);
        } catch (PDOException $error) {
            $this->removeSignature($signaturePath);
            $this->databaseError($error);
        }
    }

    public function apiDelete(int $id): void {
        $employee = Employee::findById($id);
        if (!$employee) {
            $this->jsonResponse(404, ['message' => 'Empleado no encontrado']);
            return;
        }

        if (Employee::delete($id)) {
            $this->removeSignature($employee['firma_ruta']);
            $this->jsonResponse(200, ['message' => 'Empleado eliminado correctamente']);
            return;
        }
        $this->jsonResponse(500, ['message' => 'No se pudo eliminar el empleado']);
    }

    private function validate(array $payload, bool $creating): array {
        $errors = [];
        foreach (['cargo' => 100, 'nombres' => 100, 'apellidos' => 100, 'documento' => 30] as $field => $maxLength) {
            $value = trim((string) ($payload[$field] ?? ''));
            if ($value === '') $errors[$field] = 'Este campo es obligatorio.';
            elseif (mb_strlen($value) > $maxLength) $errors[$field] = 'El valor supera la longitud permitida.';
        }

        $email = trim((string) ($payload['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $errors['email'] = 'Ingresa un correo válido de máximo 150 caracteres.';
        }

        $documentType = strtoupper(trim((string) ($payload['tipo_documento'] ?? '')));
        if (!in_array($documentType, self::DOCUMENT_TYPES, true)) {
            $errors['tipo_documento'] = 'Selecciona un tipo de documento válido.';
        }

        $date = (string) ($payload['fecha_ingreso'] ?? '');
        $parsedDate = DateTime::createFromFormat('!Y-m-d', $date);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
            $errors['fecha_ingreso'] = 'Ingresa una fecha de ingreso válida.';
        }

        $roleId = filter_var($payload['rol_id'] ?? '', FILTER_VALIDATE_INT);
        if (($payload['rol_id'] ?? '') !== '' && ($roleId === false || $roleId < 1 || !Employee::roleExists((int) $roleId))) {
            $errors['rol_id'] = 'Selecciona un rol válido.';
        }
        if ($creating && ($payload['rol_id'] ?? '') === '') {
            $errors['rol_id'] = 'Selecciona un rol antes de continuar.';
        }
        $directoryId = trim((string) ($payload['directorio_id'] ?? ''));
        if ($creating && ($directoryId === '' || mb_strlen($directoryId) > 150)) {
            $errors['directorio_id'] = 'Selecciona primero un usuario de Active Directory.';
        } elseif ($creating && Employee::directoryAccountAssigned($directoryId)) {
            $errors['directorio_id'] = 'Esta cuenta de Active Directory ya está asociada a un empleado.';
        }

        $password = (string) ($payload['password'] ?? '');
        if (($creating && strlen($password) < 8) || (!$creating && $password !== '' && strlen($password) < 8)) {
            $errors['password'] = 'La contraseña debe tener al menos 8 caracteres.';
        }
        if (!in_array((string) ($payload['estado'] ?? '1'), ['0', '1'], true)) {
            $errors['estado'] = 'Selecciona un estado válido.';
        }

        return $errors;
    }

    private function normalizePayload(array $payload): array {
        return [
            'rol_id' => ($payload['rol_id'] ?? '') !== '' ? (int) $payload['rol_id'] : null,
            'directorio_id' => trim((string) ($payload['directorio_id'] ?? '')),
            'cargo' => trim((string) $payload['cargo']),
            'nombres' => trim((string) $payload['nombres']),
            'apellidos' => trim((string) $payload['apellidos']),
            'fecha_ingreso' => (string) $payload['fecha_ingreso'],
            'tipo_documento' => strtoupper(trim((string) $payload['tipo_documento'])),
            'documento' => trim((string) $payload['documento']),
            'email' => trim((string) $payload['email']),
            'estadoDA' => !empty($payload['estadoDA']) ? 1 : 0,
            'estado' => (int) $payload['estado'],
        ];
    }

    private function storeSignature(string $dataUrl): ?string {
        if ($dataUrl === '') return null;
        if (strlen($dataUrl) > 2800000 || !preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $matches)) {
            return null;
        }

        $contents = base64_decode($matches[1], true);
        if ($contents === false || strlen($contents) > 2097152 || !str_starts_with($contents, "\x89PNG\r\n\x1a\n")) {
            return null;
        }

        $directory = __DIR__ . '/../../public/uploads/firmas';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            return null;
        }

        $fileName = bin2hex(random_bytes(16)) . '.png';
        if (file_put_contents($directory . '/' . $fileName, $contents, LOCK_EX) === false) {
            return null;
        }

        return '/uploads/firmas/' . $fileName;
    }

    private function removeSignature(?string $path): void {
        if (!$path || !str_starts_with($path, '/uploads/firmas/')) return;
        $filePath = __DIR__ . '/../../public/uploads/firmas/' . basename($path);
        if (is_file($filePath)) unlink($filePath);
    }

    private function databaseError(PDOException $error): void {
        if ($error->getCode() === '23000') {
            $this->jsonResponse(409, ['message' => 'Ya existe un empleado con ese correo o documento.']);
            return;
        }
        $this->jsonResponse(500, ['message' => 'No se pudo guardar el empleado.']);
    }

    private function readInput(): array {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') return $_POST;
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function jsonResponse(int $statusCode, mixed $payload): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
