<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class Employee {
    private const SELECT_WITH_ROLE = "
        SELECT e.id, e.rol_id, e.directorio_id, r.nombre AS rol_nombre, e.cargo, e.nombres, e.apellidos,
               e.fecha_ingreso, e.tipo_documento, e.documento, e.firma_ruta, e.email,
               e.estadoDA, e.estado, e.created_at
        FROM empleados e
        LEFT JOIN roles r ON r.id = e.rol_id
    ";

    public static function findAll(): array {
        $stmt = Database::getConnection()->query(self::SELECT_WITH_ROLE . ' ORDER BY e.id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array {
        $stmt = Database::getConnection()->prepare(self::SELECT_WITH_ROLE . ' WHERE e.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        return $employee ?: null;
    }

    public static function findForLogin(string $email): ?array {
        $stmt = Database::getConnection()->prepare(
            'SELECT id, rol_id, nombres, apellidos, email, password_hash, estado FROM empleados WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        return $employee ?: null;
    }

    public static function findRoles(): array {
        return Database::getConnection()->query('SELECT id, nombre FROM roles ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function roleExists(int $id): bool {
        $stmt = Database::getConnection()->prepare('SELECT 1 FROM roles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public static function directoryAccountAssigned(string $directoryId): bool {
        $stmt = Database::getConnection()->prepare('SELECT 1 FROM empleados WHERE directorio_id = :directory_id LIMIT 1');
        $stmt->execute(['directory_id' => $directoryId]);
        return (bool) $stmt->fetchColumn();
    }

    public static function create(array $data, string $passwordHash, ?string $signaturePath): int {
        $stmt = Database::getConnection()->prepare('
            INSERT INTO empleados (
                rol_id, directorio_id, cargo, nombres, apellidos, fecha_ingreso, tipo_documento,
                documento, firma_ruta, email, password_hash, estadoDA, estado
            ) VALUES (
                :rol_id, :directorio_id, :cargo, :nombres, :apellidos, :fecha_ingreso, :tipo_documento,
                :documento, :firma_ruta, :email, :password_hash, :estadoDA, :estado
            )
        ');
        $stmt->execute([
            'rol_id' => $data['rol_id'],
            'directorio_id' => $data['directorio_id'],
            'cargo' => $data['cargo'],
            'nombres' => $data['nombres'],
            'apellidos' => $data['apellidos'],
            'fecha_ingreso' => $data['fecha_ingreso'],
            'tipo_documento' => $data['tipo_documento'],
            'documento' => $data['documento'],
            'firma_ruta' => $signaturePath,
            'email' => $data['email'],
            'password_hash' => $passwordHash,
            'estadoDA' => $data['estadoDA'],
            'estado' => $data['estado'],
        ]);

        return (int) Database::getConnection()->lastInsertId();
    }

    public static function update(int $id, array $data, ?string $passwordHash, ?string $signaturePath, bool $removeSignature): void {
        $db = Database::getConnection();
        $stmt = $db->prepare('
            UPDATE empleados
            SET rol_id = :rol_id,
                cargo = :cargo,
                nombres = :nombres,
                apellidos = :apellidos,
                fecha_ingreso = :fecha_ingreso,
                tipo_documento = :tipo_documento,
                documento = :documento,
                firma_ruta = CASE WHEN :replace_signature = 1 THEN :firma_ruta ELSE firma_ruta END,
                email = :email,
                password_hash = COALESCE(:password_hash, password_hash),
                estadoDA = :estadoDA,
                estado = :estado
            WHERE id = :id
        ');
        $stmt->execute([
            'id' => $id,
            'rol_id' => $data['rol_id'],
            'cargo' => $data['cargo'],
            'nombres' => $data['nombres'],
            'apellidos' => $data['apellidos'],
            'fecha_ingreso' => $data['fecha_ingreso'],
            'tipo_documento' => $data['tipo_documento'],
            'documento' => $data['documento'],
            'replace_signature' => $signaturePath !== null || $removeSignature ? 1 : 0,
            'firma_ruta' => $removeSignature ? null : $signaturePath,
            'email' => $data['email'],
            'password_hash' => $passwordHash,
            'estadoDA' => $data['estadoDA'],
            'estado' => $data['estado'],
        ]);
    }

    public static function delete(int $id): bool {
        $stmt = Database::getConnection()->prepare('DELETE FROM empleados WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
