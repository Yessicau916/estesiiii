<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class Category {
    private ?int $id = null;
    private string $name;
    private string $description;
    private string $status;
    private ?string $createdAt = null;

    public function __construct(string $name, string $description = '', string $status = '1') {
        $this->name = $name;
        $this->description = $description;
        $this->status = $status;
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }

    public static function findAll(): array {
        $stmt = Database::getConnection()->query('SELECT * FROM categorias ORDER BY id DESC');
        return array_map([self::class, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public static function findById(int $id): ?self {
        $stmt = Database::getConnection()->prepare('SELECT * FROM categorias WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::mapRow($row) : null;
    }

    private static function mapRow(array $row): self {
        $category = new self(
            (string) ($row['nombre'] ?? $row['name'] ?? ''),
            (string) ($row['descripcion'] ?? $row['description'] ?? ''),
            (string) ($row['estado'] ?? $row['status'] ?? '0')
        );
        if (isset($row['id'])) $category->setId((int) $row['id']);
        $category->setCreatedAt(isset($row['created_at']) ? (string) $row['created_at'] : null);
        return $category;
    }

    public function save(): bool {
        $db = Database::getConnection();
        if ($this->id !== null) {
            $stmt = $db->prepare('UPDATE categorias SET nombre = :name, descripcion = :description, estado = :status WHERE id = :id');
            return $stmt->execute([
                'id' => $this->id,
                'name' => $this->name,
                'description' => $this->description,
                'status' => $this->status,
            ]);
        }

        $stmt = $db->prepare('INSERT INTO categorias (nombre, descripcion, estado) VALUES (:name, :description, :status)');
        $saved = $stmt->execute([
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
        ]);
        if ($saved) $this->id = (int) $db->lastInsertId();
        return $saved;
    }

    public function delete(): bool {
        if ($this->id === null) return false;
        $stmt = Database::getConnection()->prepare('DELETE FROM categorias WHERE id = :id');
        return $stmt->execute(['id' => $this->id]);
    }
}