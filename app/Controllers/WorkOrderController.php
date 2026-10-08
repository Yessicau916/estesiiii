<?php

declare(strict_types=1);

require_once __DIR__ . '/../Models/WorkOrder.php';

class WorkOrderController {
    private const PRIORITIES = ['ALTA', 'MEDIA', 'BAJA'];
    private const STATUSES = ['ACTIVO', 'PENDIENTE', 'EJECUTADO', 'SUSPENDIDO', 'CANCELADO', 'REASIGNADO', 'INACTIVO'];

    public function index(): void {
        $activeModule = 'work-orders';
        $currentRole = Role::findById((int) ($_SESSION['role_id'] ?? 0));
        $search = trim((string) ($_GET['q'] ?? ''));
        $orders = WorkOrder::findAll($search);
        $contracts = WorkOrder::contracts();
        $regions = WorkOrder::regions();
        $employees = WorkOrder::employees();
        $feedback = $_SESSION['work_order_feedback'] ?? null;
        unset($_SESSION['work_order_feedback']);
        require __DIR__ . '/../Views/work-orders/index.php';
    }

    public function create(): void {
        $role = Role::findById((int) ($_SESSION['role_id'] ?? 0));
        if (!$role || !$role->hasPermission('work_orders', 'crear') || !$role->hasPermission('work_orders', 'asignar')) { $this->finish('Tu rol necesita los permisos Crear y Asignar Personal para crear órdenes.', 'error'); return; }
        $data = $this->validatedInput();
        if (isset($data['error'])) { $this->finish($data['error'], 'error'); return; }
        try {
            WorkOrder::create($data);
            $this->finish('Orden de trabajo creada correctamente.');
        } catch (PDOException $error) {
            $this->finish($error->getCode() === '23000' ? 'Ya existe una orden con ese número OT.' : 'No se pudo guardar la orden.', 'error');
        }
    }

    public function update(int $id): void {
        $data = $this->validatedInput();
        if (isset($data['error'])) { $this->finish($data['error'], 'error'); return; }
        $existingOrder = WorkOrder::findById($id);
        if (!$existingOrder) { $this->finish('No se encontró la orden de trabajo.', 'error'); return; }
        $currentRole = Role::findById((int) ($_SESSION['role_id'] ?? 0));
        $canAssign = $currentRole && $currentRole->hasPermission('work_orders', 'asignar');
        if (!$canAssign && (
            (int) $data['responsible_id'] !== (int) $existingOrder['responsable_id'] ||
            (int) $data['sst_manager_id'] !== (int) $existingOrder['gestor_sst_id'] ||
            (int) $data['contract_id'] !== (int) $existingOrder['contract_id'] ||
            (int) $data['region_id'] !== (int) $existingOrder['region_id']
        )) {
            $this->finish('Tu rol no tiene permiso para asignar o cambiar el responsable.', 'error');
            return;
        }
        if ($canAssign) $data['can_assign'] = true;
        try {
            WorkOrder::update($id, $data);
            $this->finish('Orden de trabajo actualizada correctamente.');
        } catch (PDOException $error) {
            $this->finish($error->getCode() === '23000' ? 'Ya existe otra orden con ese número OT.' : 'No se pudo actualizar la orden.', 'error');
        }
    }

    public function delete(int $id): void {
        WorkOrder::delete($id);
        $this->finish('Orden de trabajo eliminada.');
    }

    public function deleteMany(): void {
        $role = Role::findById((int) ($_SESSION['role_id'] ?? 0));
        if (!$role || !$role->hasPermission('work_orders', 'eliminar') || !$role->hasPermission('work_orders', 'eliminar_masivo')) { $this->finish('Tu rol necesita los permisos Eliminar y Eliminación masiva para borrar varias órdenes.', 'error'); return; }
        $count = WorkOrder::deleteMany($_POST['ids'] ?? []);
        $this->finish($count . ' órdenes eliminadas.');
    }

    public function export(): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ordenes-trabajo.csv"');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['priority', 'ot_number', 'activity', 'address', 'status', 'contract_id', 'region_id', 'responsible_id', 'sst_manager_id', 'observations']);
        foreach (WorkOrder::findAll() as $order) fputcsv($output, [$order['priority'], $order['ot_number'], $order['activity'], $order['address'], $order['status'], $order['contract_id'], $order['region_id'], $order['responsible_id'], $order['sst_manager_id'], $order['observations']]);
        fclose($output);
        exit;
    }

    public function import(): void {
        $role = Role::findById((int) ($_SESSION['role_id'] ?? 0));
        if (!$role || !$role->hasPermission('work_orders', 'importar') || !$role->hasPermission('work_orders', 'asignar')) { $this->finish('Tu rol necesita los permisos Importar y Asignar Personal para importar órdenes.', 'error'); return; }
        if (empty($_FILES['csv_file']['tmp_name']) || !is_uploaded_file($_FILES['csv_file']['tmp_name'])) { $this->finish('Selecciona un archivo CSV para importar.', 'error'); return; }
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) { $this->finish('No se pudo leer el archivo.', 'error'); return; }
        $header = fgetcsv($handle) ?: [];
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($header[0] ?? ''));
        $map = array_map(static fn ($value): string => strtolower(trim((string) $value)), $header);
        $fields = ['priority', 'ot_number', 'activity', 'address', 'status', 'contract_id', 'region_id', 'responsible_id', 'sst_manager_id', 'observations'];
        $records = [];
        while (($line = fgetcsv($handle)) !== false) {
            $record = [];
            foreach ($fields as $field) { $position = array_search($field, $map, true); $record[$field] = $position === false ? '' : trim((string) ($line[$position] ?? '')); }
            if ($record['ot_number'] !== '' && $record['activity'] !== '') $records[] = $record;
        }
        fclose($handle);
        try { $this->finish(WorkOrder::import($records) . ' órdenes importadas o actualizadas.'); }
        catch (Throwable) { $this->finish('No se pudo importar el CSV. Usa el formato exportado por esta pantalla.', 'error'); }
    }

    private function validatedInput(): array {
        $fields = ['priority', 'ot_number', 'activity', 'address', 'status', 'contract_id', 'region_id', 'responsible_id', 'sst_manager_id', 'observations'];
        $data = [];
        foreach ($fields as $field) $data[$field] = trim((string) ($_POST[$field] ?? ''));
        if (!in_array(strtoupper($data['priority']), self::PRIORITIES, true)) return ['error' => 'Selecciona una prioridad válida.'];
        if (!in_array(strtoupper($data['status']), self::STATUSES, true)) return ['error' => 'Selecciona un estado válido.'];
        foreach (['contract_id' => 'contrato', 'region_id' => 'región', 'responsible_id' => 'responsable', 'sst_manager_id' => 'gestor SST'] as $field => $label) {
            if (filter_var($data[$field], FILTER_VALIDATE_INT) === false || (int) $data[$field] < 1) return ['error' => 'Selecciona una ' . $label . ' válida.'];
            $data[$field] = (int) $data[$field];
        }
        foreach (['ot_number' => 'El número OT', 'activity' => 'La actividad', 'address' => 'La dirección'] as $field => $label) if ($data[$field] === '') return ['error' => $label . ' es obligatorio.'];
        $data['priority'] = strtoupper($data['priority']);
        $data['status'] = strtoupper($data['status']);
        return $data;
    }

    private function finish(string $message, string $type = 'success'): void {
        $_SESSION['work_order_feedback'] = ['message' => $message, 'type' => $type];
        header('Location: /work-orders');
        exit;
    }
}
