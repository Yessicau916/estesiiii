<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/Role.php';

class RoleController {
    public function index(): void {
        $roles = Role::findAll();
        require __DIR__ . '/../Views/roles/index.php';
    }

    public function apiList(): void {
        $roles = Role::findAll();

        $payload = [];
        foreach ($roles as $role) {
            $payload[] = [
                'id' => $role->getId(),
                'name' => $role->getName(),
                'description' => $role->getDescription(),
                'status' => $role->getStatus(),
                'permissions' => $role->getPermissions()
            ];
        }

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function apiGet(int $id): void {
        $role = Role::findById($id);

        if (!$role) {
            $this->jsonResponse(404, ['message' => 'Rol no encontrado']);
            return;
        }

        $this->jsonResponse(200, [
            'id' => $role->getId(),
            'name' => $role->getName(),
            'description' => $role->getDescription(),
            'status' => $role->getStatus(),
            'permissions' => $role->getPermissions()
        ]);
    }

    public function apiCreate(): void {
        $payload = $this->readInput();

        $name = trim((string) ($this->getValue($payload, ['name', 'nombre'], '')));
        $description = trim((string) ($this->getValue($payload, ['description', 'descripcion'], '')));
        $status = $this->normalizeStatus($this->getValue($payload, ['status', 'estado'], 'ACTIVO'));

        if ($name === '') {
            $this->jsonResponse(422, ['message' => 'El nombre del rol es obligatorio']);
            return;
        }

        $role = new Role($name, $description, $status, $payload['permissions'] ?? []);

        $ok = $role->save();

        if (!$ok) {
            $this->jsonResponse(500, ['message' => 'No se pudo crear el rol']);
            return;
        }

        $this->jsonResponse(201, ['message' => 'Rol creado correctamente']);
    }

    public function apiUpdate(int $id): void {
        $payload = $this->readInput();
        $role = Role::findById($id);

        if (!$role) {
            $this->jsonResponse(404, ['message' => 'Rol no encontrado']);
            return;
        }

        $name = trim((string) ($this->getValue($payload, ['name', 'nombre'], '')));
        $description = trim((string) ($this->getValue($payload, ['description', 'descripcion'], '')));
        $status = $this->normalizeStatus($this->getValue($payload, ['status', 'estado'], 'ACTIVO'));

        if ($name === '') {
            $this->jsonResponse(422, ['message' => 'El nombre del rol es obligatorio']);
            return;
        }

        $role->setName($name);
        $role->setDescription($description);
        $role->setStatus($status);
        $role->setPermissions($payload['permissions'] ?? []);

        $ok = $role->save();

        if (!$ok) {
            $this->jsonResponse(500, ['message' => 'No se pudo actualizar el rol']);
            return;
        }

        $this->jsonResponse(200, ['message' => 'Rol actualizado correctamente']);
    }

    public function apiDelete(int $id): void {
        $role = Role::findById($id);

        if (!$role) {
            $this->jsonResponse(404, ['message' => 'Rol no encontrado']);
            return;
        }

        $ok = $role->delete();

        if (!$ok) {
            $this->jsonResponse(500, ['message' => 'No se pudo eliminar el rol']);
            return;
        }

        $this->jsonResponse(200, ['message' => 'Rol eliminado correctamente']);
    }

    private function getValue(array $payload, array $keys, mixed $default = null): mixed {
        foreach ($keys as $key) {
            if (array_key_exists($key, $payload)) {
                return $payload[$key];
            }
        }

        return $default;
    }

    private function normalizeStatus(mixed $status): string {
        $status = strtoupper(trim((string) $status));
        return in_array($status, ['1', 'ACTIVO', 'ACTIVE', 'TRUE'], true) ? '1' : '0';
    }

    private function readInput(): array {
        $raw = file_get_contents('php://input');

        if ($raw === false || $raw === '') {
            return $_POST;
        }

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