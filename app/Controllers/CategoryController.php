<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/Category.php';

class CategoryController {
    public function index(): void {
        $activeModule = 'categories';
        require __DIR__ . '/../Views/categories/index.php';
    }

    public function apiList(): void {
        $categories = array_map([$this, 'serializeCategory'], Category::findAll());
        $this->jsonResponse(200, $categories);
    }

    public function apiGet(int $id): void {
        $category = Category::findById($id);
        if (!$category) {
            $this->jsonResponse(404, ['message' => 'Categoría no encontrada']);
            return;
        }
        $this->jsonResponse(200, $this->serializeCategory($category));
    }

    public function apiCreate(): void {
        $payload = $this->readInput();
        $validation = $this->validate($payload);
        if ($validation !== []) {
            $this->jsonResponse(422, ['message' => reset($validation), 'errors' => $validation]);
            return;
        }

        $category = new Category(
            trim((string) $payload['name']),
            trim((string) ($payload['description'] ?? '')),
            $this->normalizeStatus($payload['status'] ?? 'ACTIVO')
        );
        if (!$category->save()) {
            $this->jsonResponse(500, ['message' => 'No se pudo crear la categoría']);
            return;
        }
        $this->jsonResponse(201, ['message' => 'Categoría creada correctamente', 'id' => $category->getId()]);
    }

    public function apiUpdate(int $id): void {
        $category = Category::findById($id);
        if (!$category) {
            $this->jsonResponse(404, ['message' => 'Categoría no encontrada']);
            return;
        }

        $payload = $this->readInput();
        $validation = $this->validate($payload);
        if ($validation !== []) {
            $this->jsonResponse(422, ['message' => reset($validation), 'errors' => $validation]);
            return;
        }

        $category->setName(trim((string) $payload['name']));
        $category->setDescription(trim((string) ($payload['description'] ?? '')));
        $category->setStatus($this->normalizeStatus($payload['status'] ?? 'ACTIVO'));
        if (!$category->save()) {
            $this->jsonResponse(500, ['message' => 'No se pudo actualizar la categoría']);
            return;
        }
        $this->jsonResponse(200, ['message' => 'Categoría actualizada correctamente']);
    }

    public function apiDelete(int $id): void {
        $category = Category::findById($id);
        if (!$category) {
            $this->jsonResponse(404, ['message' => 'Categoría no encontrada']);
            return;
        }
        if (!$category->delete()) {
            $this->jsonResponse(500, ['message' => 'No se pudo eliminar la categoría']);
            return;
        }
        $this->jsonResponse(200, ['message' => 'Categoría eliminada correctamente']);
    }

    private function serializeCategory(Category $category): array {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
            'status' => $category->getStatus(),
            'created_at' => $category->getCreatedAt(),
        ];
    }

    private function validate(array $payload): array {
        $errors = [];
        $name = trim((string) ($payload['name'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        if (mb_strlen($name) < 2) $errors['name'] = 'El nombre debe tener al menos 2 caracteres.';
        if (mb_strlen($name) > 100) $errors['name'] = 'El nombre no puede superar los 100 caracteres.';
        if (mb_strlen($description) > 255) $errors['description'] = 'La descripción no puede superar los 255 caracteres.';
        return $errors;
    }

    private function normalizeStatus(mixed $status): string {
        return in_array(strtoupper(trim((string) $status)), ['1', 'ACTIVO', 'ACTIVE', 'TRUE'], true) ? '1' : '0';
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