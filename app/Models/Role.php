<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class Role {
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

    public static function findAll(): array {
        $stmt = Database::getConnection()->query("SELECT * FROM roles ORDER BY id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $roles = [];
        foreach ($rows as $row) {
            $roles[] = self::mapRow($row);
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

        return self::mapRow($row);
    }

    private static function mapRow(array $row): self {
        $name = $row['name'] ?? $row['nombre'] ?? '';
        $description = $row['description'] ?? $row['descripcion'] ?? '';
        $status = $row['status'] ?? $row['estado'] ?? 'ACTIVO';

        $permissions = [];
        if (isset($row['permissions'])) {
            $decoded = json_decode((string) $row['permissions'], true);
            if (is_array($decoded)) {
                $permissions = $decoded;
            }
        }

        $role = new self((string) $name, (string) $description, (string) $status, $permissions);

        if (isset($row['id'])) {
            $role->setId((int) $row['id']);
        }

        return $role;
    }

    public function save(): bool {
        $db = Database::getConnection();
        $permissions = json_encode($this->permissions, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if ($this->id !== null) {
            $stmt = $db->prepare("
                UPDATE roles
                SET nombre = :name,
                    descripcion = :description,
                    estado = :status,
                    permissions = :permissions
                WHERE id = :id
            ");

            return $stmt->execute([
                'id' => $this->id,
                'name' => $this->name,
                'description' => $this->description,
                'status' => $this->status,
                'permissions' => $permissions
            ]);
        }

        $stmt = $db->prepare("
            INSERT INTO roles (nombre, descripcion, estado, permissions)
            VALUES (:name, :description, :status, :permissions)
        ");

        $ok = $stmt->execute([
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'permissions' => $permissions
        ]);

        if ($ok) {
            $this->id = (int) $db->lastInsertId();
        }

        return $ok;
    }

    public function delete(): bool {
        if ($this->id === null) {
            return false;
        }

        $stmt = Database::getConnection()->prepare("DELETE FROM roles WHERE id = :id");
        return $stmt->execute(['id' => $this->id]);
    }
}