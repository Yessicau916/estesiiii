<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class Role {
    private const MODULES = [
        'dashboard' => ['permission' => 'dashboard', 'name' => 'Dashboard', 'description' => 'Visualización de métricas, reportes y estadísticas generales', 'actions' => ['ver' => 'Ver Dashboard', 'exportar' => 'Exportar Reportes']],
        'users' => ['permission' => 'usuarios', 'name' => 'Usuarios', 'description' => 'Administración de cuentas de acceso al sistema', 'actions' => ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar']],
        'roles' => ['permission' => 'roles', 'name' => 'Roles y Permisos', 'description' => 'Configuración de perfiles y niveles de autorización', 'actions' => ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar']],
        'employees' => ['permission' => 'empleados', 'name' => 'Empleados', 'description' => 'Directorio de personal técnico y administrativo', 'actions' => ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar']],
        'categories' => ['permission' => 'categorias', 'name' => 'Categorías', 'description' => 'Clasificación de actividades, especialidades y servicios', 'actions' => ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar']],
        'work_orders' => ['permission' => 'ordenes', 'name' => 'Órdenes de Trabajo', 'description' => 'Seguimiento, ejecución y asignación de órdenes de campo', 'actions' => ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar', 'asignar' => 'Asignar Personal', 'seleccionar' => 'Seleccionar órdenes', 'importar' => 'Importar CSV', 'exportar' => 'Exportar CSV', 'editar_masivo' => 'Edición masiva', 'eliminar_masivo' => 'Eliminación masiva', 'ver_ejecucion' => 'Ver porcentaje de ejecución']],
        'forms' => ['permission' => 'formularios', 'name' => 'Formularios', 'description' => 'Plantillas de inspección técnica y recolección de datos', 'actions' => ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar', 'responder' => 'Diligenciar']],
        'contracts' => ['permission' => 'contratos', 'name' => 'Contratos', 'description' => 'Gestión contractual y asignaciones vinculadas', 'actions' => ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar']],
        'regions' => ['permission' => 'regiones', 'name' => 'Regiones', 'description' => 'Zonas geográficas y sedes operativas de cobertura', 'actions' => ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar']],
    ];
    private const ACTION_NAMES = ['ver' => 'leer', 'exportar' => 'exportar', 'crear' => 'crear', 'editar' => 'editar', 'eliminar' => 'eliminar', 'asignar' => 'asignar', 'responder' => 'responder', 'importar' => 'importar', 'seleccionar' => 'seleccionar', 'editar_masivo' => 'editar_masivo', 'eliminar_masivo' => 'eliminar_masivo', 'ver_ejecucion' => 'ver_ejecucion'];
    private const READ_ACTIONS = ['ver', 'exportar', 'ver_ejecucion', 'seleccionar'];

    private ?int $id = null;
    private string $name;
    private string $description;
    private string $status;
    private array $permissions;

    public function __construct(string $name, string $description = '', string $status = 'ACTIVO', array $permissions = []) {
        $this->name = $name;
        $this->description = $description;
        $this->status = $status;
        $this->permissions = $permissions;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): void { $this->description = $description; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function getPermissions(): array { return $this->permissions; }
    public function setPermissions(array $permissions): void { $this->permissions = $permissions; }
    public function hasPermission(string $module, string $action): bool {
        return in_array($action, $this->permissions[$module] ?? [], true);
    }

    public static function findAll(): array {
        $stmt = Database::getConnection()->query("SELECT * FROM roles ORDER BY id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $permissionsByRole = self::loadPermissions(array_column($rows, 'id'));

        $roles = [];
        foreach ($rows as $row) {
            $roles[] = self::mapRow($row, $permissionsByRole[(int) $row['id']] ?? []);
        }

        return $roles;
    }

    public static function findById(int $id): ?self {
        $stmt = Database::getConnection()->prepare("SELECT * FROM roles WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $permissionsByRole = self::loadPermissions([$id]);
        return self::mapRow($row, $permissionsByRole[$id] ?? []);
    }

    private static function mapRow(array $row, array $permissions = []): self {
        $name = $row['name'] ?? $row['nombre'] ?? '';
        $description = $row['description'] ?? $row['descripcion'] ?? '';
        $status = $row['status'] ?? $row['estado'] ?? 'ACTIVO';

        $role = new self((string) $name, (string) $description, (string) $status, $permissions);

        if (isset($row['id'])) {
            $role->setId((int) $row['id']);
        }

        return $role;
    }

    private static function loadPermissions(array $roleIds): array {
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds))));
        if ($roleIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
        $stmt = Database::getConnection()->prepare("
            SELECT rp.rol_id, p.nombre AS permission_name
            FROM roles_permisos rp
            INNER JOIN permisos p ON p.id = rp.permiso_id
            WHERE rp.rol_id IN ({$placeholders})
            ORDER BY p.id
        ");
        $stmt->execute($roleIds);

        $moduleIdsByPermission = [];
        foreach (self::MODULES as $moduleId => $module) {
            $moduleIdsByPermission[$module['permission']] = $moduleId;
        }

        $permissionsByRole = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $parts = explode('.', $row['permission_name'], 2);
            if (count($parts) !== 2 || !isset($moduleIdsByPermission[$parts[0]])) {
                continue;
            }

            $moduleId = $moduleIdsByPermission[$parts[0]];
            $actionId = array_search($parts[1], self::ACTION_NAMES, true);
            if (isset(self::MODULES[$moduleId]['actions'][$actionId])) {
                $permissionsByRole[(int) $row['rol_id']][$moduleId][] = $actionId;
            }
        }

        return $permissionsByRole;
    }

    private function normalizePermissions(): array {
        $normalized = [];
        foreach ($this->permissions as $moduleId => $actions) {
            if (!isset(self::MODULES[$moduleId]) || !is_array($actions)) {
                continue;
            }

            $validActions = array_values(array_unique(array_filter(
                $actions,
                static fn ($actionId): bool => is_string($actionId) && isset(self::MODULES[$moduleId]['actions'][$actionId])
            )));
            if ($validActions !== []) {
                $normalized[$moduleId] = $validActions;
            }
        }

        return $normalized;
    }

    private function savePermissions(PDO $db): void {
        $delete = $db->prepare('DELETE FROM roles_permisos WHERE rol_id = :role_id');
        $delete->execute(['role_id' => $this->id]);

        $findPrivilege = $db->prepare('SELECT id FROM privilegios WHERE nombre = :name LIMIT 1');
        $insertPrivilege = $db->prepare('INSERT INTO privilegios (nombre, descripcion) VALUES (:name, :description)');
        $findPermission = $db->prepare('SELECT id FROM permisos WHERE nombre = :name LIMIT 1');
        $insertPermission = $db->prepare('INSERT INTO permisos (nombre, descripcion) VALUES (:name, :description)');
        $linkPermission = $db->prepare('INSERT IGNORE INTO privilegios_permisos (permiso_id, privilegio_id) VALUES (:permission_id, :privilege_id)');
        $assignPermission = $db->prepare('INSERT IGNORE INTO roles_permisos (permiso_id, rol_id) VALUES (:permission_id, :role_id)');

        foreach ($this->normalizePermissions() as $moduleId => $actionIds) {
            $module = self::MODULES[$moduleId];
            foreach ($actionIds as $actionId) {
                $privilegeName = in_array($actionId, self::READ_ACTIONS, true) ? 'Lectura' : 'Escritura';
                $findPrivilege->execute(['name' => $privilegeName]);
                $privilegeId = $findPrivilege->fetchColumn();
                if ($privilegeId === false) {
                    $description = $privilegeName === 'Lectura' ? 'Acceso de solo lectura a los módulos' : 'Acceso para crear y modificar registros';
                    $insertPrivilege->execute(['name' => $privilegeName, 'description' => $description]);
                    $privilegeId = $db->lastInsertId();
                }

                $permissionName = $module['permission'] . '.' . self::ACTION_NAMES[$actionId];
                $findPermission->execute(['name' => $permissionName]);
                $permissionId = $findPermission->fetchColumn();
                if ($permissionId === false) {
                    $insertPermission->execute([
                        'name' => $permissionName,
                        'description' => 'Permite ' . $module['actions'][$actionId] . ' en ' . $module['name'],
                    ]);
                    $permissionId = $db->lastInsertId();
                }

                $linkPermission->execute(['permission_id' => $permissionId, 'privilege_id' => $privilegeId]);
                $assignPermission->execute(['permission_id' => $permissionId, 'role_id' => $this->id]);
            }
        }
    }

    public function save(): bool {
        $db = Database::getConnection();
        try {
            $db->beginTransaction();

            if ($this->id !== null) {
                $stmt = $db->prepare("
                    UPDATE roles
                    SET nombre = :name, descripcion = :description, estado = :status
                    WHERE id = :id
                ");
                $stmt->execute([
                    'id' => $this->id,
                    'name' => $this->name,
                    'description' => $this->description,
                    'status' => $this->status,
                ]);
            } else {
                $stmt = $db->prepare('INSERT INTO roles (nombre, descripcion, estado) VALUES (:name, :description, :status)');
                $stmt->execute([
                    'name' => $this->name,
                    'description' => $this->description,
                    'status' => $this->status,
                ]);
                $this->id = (int) $db->lastInsertId();
            }

            $this->savePermissions($db);
            $db->commit();
            return true;
        } catch (Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return false;
        }
    }

    public function delete(): bool {
        if ($this->id === null) {
            return false;
        }

        $stmt = Database::getConnection()->prepare("DELETE FROM roles WHERE id = :id");
        return $stmt->execute(['id' => $this->id]);
    }
}
