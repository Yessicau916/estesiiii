<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

class WorkOrder {
    private const STATUSES = ['ACTIVO', 'PENDIENTE', 'EJECUTADO', 'SUSPENDIDO', 'CANCELADO', 'REASIGNADO', 'INACTIVO'];

    public static function findAll(string $query = ''): array {
        $sql = "SELECT o.id, o.asignacion_id AS assignment_id, o.numero AS ot_number, o.prioridad AS priority, o.actividad AS activity,
                       o.direccion AS address, o.estado_orden AS status, o.responsable_id AS responsible_id,
                       CONCAT_WS(' ', responsable.nombres, responsable.apellidos) AS responsible,
                       o.gestor_sst_id AS sst_manager_id, CONCAT_WS(' ', gestor.nombres, gestor.apellidos) AS sst_manager,
                       o.fecha_hora, o.observaciones AS observations, a.contrato_id AS contract_id, a.region_id,
                       c.nombre AS contract_name, r.nombre AS region_name, o.created_at, o.updated_at
                FROM ordenes_trabajo o
                INNER JOIN asignaciones_contrato a ON a.id = o.asignacion_id
                INNER JOIN contratos c ON c.id = a.contrato_id
                INNER JOIN regiones r ON r.id = a.region_id
                INNER JOIN empleados responsable ON responsable.id = o.responsable_id
                INNER JOIN empleados gestor ON gestor.id = o.gestor_sst_id";
        $params = [];
        if (trim($query) !== '') {
            $fields = ['o.prioridad', 'o.numero', 'o.actividad', 'o.direccion', 'o.estado_orden', 'responsable.nombres', 'responsable.apellidos', 'gestor.nombres', 'gestor.apellidos', 'c.nombre', 'r.nombre', 'o.observaciones'];
            $where = [];
            foreach ($fields as $index => $field) { $where[] = $field . ' LIKE :q' . $index; $params['q' . $index] = '%' . trim($query) . '%'; }
            $sql .= ' WHERE ' . implode(' OR ', $where);
        }
        $sql .= ' ORDER BY o.id DESC';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array {
        $stmt = Database::getConnection()->prepare('SELECT o.*, a.contrato_id AS contract_id, a.region_id FROM ordenes_trabajo o INNER JOIN asignaciones_contrato a ON a.id=o.asignacion_id WHERE o.id=:id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function contracts(): array { return Database::getConnection()->query('SELECT id, nombre FROM contratos WHERE estado=1 ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC); }
    public static function regions(): array { return Database::getConnection()->query('SELECT id, nombre FROM regiones ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC); }

    public static function employees(): array {
        return Database::getConnection()->query("SELECT id, CONCAT_WS(' ', nombres, apellidos) AS nombre FROM empleados WHERE estado=1 ORDER BY nombres, apellidos")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(array $data): int {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $assignmentId = self::createAssignment($db, $data);
            $stmt = $db->prepare('INSERT INTO ordenes_trabajo (asignacion_id, numero, prioridad, actividad, direccion, estado, estado_orden, responsable_id, gestor_sst_id, fecha_hora, observaciones) VALUES (:asignacion_id, :ot_number, :priority, :activity, :address, :active, :status, :responsible_id, :sst_manager_id, NOW(), :observations)');
            $stmt->execute(self::normalize($data + ['assignment_id' => $assignmentId]));
            $id = (int) $db->lastInsertId();
            $db->commit();
            return $id;
        } catch (Throwable $error) { $db->rollBack(); throw $error; }
    }

    public static function update(int $id, array $data): void {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $existing = self::findById($id);
            if (!$existing) throw new RuntimeException('Orden no encontrada.');
            $assignmentId = !empty($data['can_assign']) ? self::updateAssignment($db, (int) $existing['asignacion_id'], $id, $data) : (int) $existing['asignacion_id'];
            $stmt = $db->prepare('UPDATE ordenes_trabajo SET asignacion_id=:asignacion_id, numero=:ot_number, prioridad=:priority, actividad=:activity, direccion=:address, estado=:active, estado_orden=:status, responsable_id=:responsible_id, gestor_sst_id=:sst_manager_id, observaciones=:observations WHERE id=:id');
            $stmt->execute(self::normalize($data + ['assignment_id' => $assignmentId]) + ['id' => $id]);
            $db->commit();
        } catch (Throwable $error) { $db->rollBack(); throw $error; }
    }

    public static function delete(int $id): void { Database::getConnection()->prepare('DELETE FROM ordenes_trabajo WHERE id=:id')->execute(['id' => $id]); }

    public static function deleteMany(array $ids): int {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($ids === []) return 0;
        $stmt = Database::getConnection()->prepare('DELETE FROM ordenes_trabajo WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
        $stmt->execute($ids);
        return $stmt->rowCount();
    }

    public static function import(array $records): int {
        if ($records === []) return 0;
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $sql = 'INSERT IGNORE INTO ordenes_trabajo (asignacion_id, numero, prioridad, actividad, direccion, estado, estado_orden, responsable_id, gestor_sst_id, fecha_hora, observaciones) VALUES (:asignacion_id, :ot_number, :priority, :activity, :address, :active, :status, :responsible_id, :sst_manager_id, NOW(), :observations)';
            $insert = $db->prepare($sql);
            $findNumber = $db->prepare('SELECT 1 FROM ordenes_trabajo WHERE numero=:number LIMIT 1');
            $count = 0;
            foreach ($records as $record) {
                $findNumber->execute(['number' => trim((string) ($record['ot_number'] ?? ''))]);
                if ($findNumber->fetchColumn()) continue;
                $assignmentId = self::createAssignment($db, $record);
                $insert->execute(self::normalize($record + ['assignment_id' => $assignmentId]));
                $count += $insert->rowCount();
            }
            $db->commit();
            return $count;
        } catch (Throwable $error) { $db->rollBack(); throw $error; }
    }

    private static function createAssignment(PDO $db, array $data): int {
        if (!empty($data['assignment_id'])) return (int) $data['assignment_id'];
        $stmt = $db->prepare("INSERT INTO asignaciones_contrato (contrato_id, empleado_id, region_id, descripcion, estado) VALUES (:contract_id, :employee_id, :region_id, :description, 'asignado')");
        $stmt->execute(['contract_id' => (int) $data['contract_id'], 'employee_id' => (int) $data['responsible_id'], 'region_id' => (int) $data['region_id'], 'description' => trim((string) $data['activity'])]);
        return (int) $db->lastInsertId();
    }

    private static function updateAssignment(PDO $db, int $assignmentId, int $orderId, array $data): int {
        if (empty($data['contract_id']) || empty($data['region_id'])) return $assignmentId;
        $usage = $db->prepare('SELECT COUNT(*) FROM ordenes_trabajo WHERE asignacion_id=:assignment_id AND id<>:order_id');
        $usage->execute(['assignment_id' => $assignmentId, 'order_id' => $orderId]);
        if ((int) $usage->fetchColumn() > 0) return self::createAssignment($db, $data);
        $stmt = $db->prepare("UPDATE asignaciones_contrato SET contrato_id=:contract_id, empleado_id=:employee_id, region_id=:region_id, descripcion=:description WHERE id=:id");
        $stmt->execute(['contract_id' => (int) $data['contract_id'], 'employee_id' => (int) $data['responsible_id'], 'region_id' => (int) $data['region_id'], 'description' => trim((string) $data['activity']), 'id' => $assignmentId]);
        return $assignmentId;
    }

    private static function normalize(array $data): array {
        $status = strtoupper(trim((string) ($data['status'] ?? 'PENDIENTE')));
        if (!in_array($status, self::STATUSES, true)) $status = 'PENDIENTE';
        return [
            'asignacion_id' => (int) ($data['assignment_id'] ?? $data['asignacion_id'] ?? 0),
            'ot_number' => trim((string) ($data['ot_number'] ?? '')),
            'priority' => strtoupper(trim((string) ($data['priority'] ?? 'MEDIA'))),
            'activity' => trim((string) ($data['activity'] ?? '')),
            'address' => trim((string) ($data['address'] ?? '')),
            'active' => $status === 'INACTIVO' ? 0 : 1,
            'status' => $status,
            'responsible_id' => (int) ($data['responsible_id'] ?? 0),
            'sst_manager_id' => (int) ($data['sst_manager_id'] ?? 0),
            'observations' => trim((string) ($data['observations'] ?? '')),
        ];
    }
}
