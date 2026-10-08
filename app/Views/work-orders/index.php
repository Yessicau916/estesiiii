<?php
declare(strict_types=1);
$h = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$priorities = ['ALTA', 'MEDIA', 'BAJA'];
$statuses = ['ACTIVO', 'PENDIENTE', 'EJECUTADO', 'SUSPENDIDO', 'CANCELADO', 'REASIGNADO', 'INACTIVO'];
$canAssign = $currentRole?->hasPermission('work_orders', 'asignar') ?? false;
$canCreate = ($currentRole?->hasPermission('work_orders', 'crear') ?? false) && $canAssign;
$canEdit = $currentRole?->hasPermission('work_orders', 'editar') ?? false;
$canDelete = $currentRole?->hasPermission('work_orders', 'eliminar') ?? false;
$canSelect = $currentRole?->hasPermission('work_orders', 'seleccionar') ?? false;
$canBulkEdit = ($currentRole?->hasPermission('work_orders', 'editar_masivo') ?? false) && $canEdit;
$canBulkDelete = ($currentRole?->hasPermission('work_orders', 'eliminar_masivo') ?? false) && $canDelete;
$canImport = $currentRole?->hasPermission('work_orders', 'importar') ?? false;
$canExport = $currentRole?->hasPermission('work_orders', 'exportar') ?? false;
$canViewExecution = $currentRole?->hasPermission('work_orders', 'ver_ejecucion') ?? false;
$canView = $currentRole?->hasPermission('work_orders', 'ver') ?? false;
$completedCount = count(array_filter($orders, static fn (array $order): bool => $order['status'] === 'EJECUTADO'));
$completionPercent = count($orders) ? (int) round($completedCount / count($orders) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Órdenes de trabajo | AppSig</title>
    <link rel="stylesheet" href="/css/role.css" />
    <link rel="stylesheet" href="/css/menu.css" />
    <link rel="stylesheet" href="/css/work-orders.css?v=category-order-modal-1" />
    <link rel="stylesheet" href="/css/categories.css" />
</head>
<body>
    <?php require __DIR__ . '/../partials/navigation.php'; ?>
    <section class="work-orders-page categories-screen" id="workOrdersApp" data-can-view="<?= $canView ? '1' : '0' ?>" data-can-create="<?= $canCreate ? '1' : '0' ?>" data-can-edit="<?= $canEdit ? '1' : '0' ?>" data-can-delete="<?= $canDelete ? '1' : '0' ?>" data-can-assign="<?= $canAssign ? '1' : '0' ?>" data-can-select="<?= $canSelect ? '1' : '0' ?>" data-can-bulk-edit="<?= $canBulkEdit ? '1' : '0' ?>" data-can-bulk-delete="<?= $canBulkDelete ? '1' : '0' ?>" data-can-import="<?= $canImport ? '1' : '0' ?>" data-can-export="<?= $canExport ? '1' : '0' ?>" data-can-view-execution="<?= $canViewExecution ? '1' : '0' ?>">
        <?php if ($feedback): ?><div class="wo-feedback category-feedback <?= $feedback['type'] === 'error' ? 'is-error' : '' ?>" role="status"><?= $h($feedback['message']) ?></div><?php endif; ?>
        <?php if (!$canCreate): ?><div class="wo-permission-notice" role="status">Para crear órdenes necesitas los privilegios <strong>Órdenes de trabajo → Crear</strong> y <strong>Asignar Personal</strong>. El botón está bloqueado mientras falte alguno.</div><?php endif; ?>
        <header class="categories-toolbar">
            <div class="categories-context"><span class="categories-mark" aria-hidden="true"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H10l2 2h6.5A2.5 2.5 0 0 1 21 9.5v8a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5z"/><path d="M3.5 9h17"/></svg></span><div><span class="categories-app-name">APPSIG</span><div class="categories-breadcrumb"><strong>Órdenes de trabajo</strong><span>/</span><strong>Enecon</strong></div></div></div>
            <div class="categories-actions">
                <button class="category-icon-button <?= !$canSelect ? 'is-blocked' : '' ?>" type="button" id="toggleSelection" title="<?= $canSelect ? 'Seleccionar órdenes' : 'Tu rol no tiene permiso para seleccionar órdenes.' ?>" aria-label="Seleccionar órdenes" <?= !$canSelect ? 'disabled' : '' ?>><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 12h8M12 8v8"/></svg></button>
                <button class="category-icon-button <?= !$canBulkEdit ? 'is-blocked' : '' ?>" type="button" id="bulkEdit" title="<?= $canBulkEdit ? 'Editar seleccionadas' : 'Tu rol no tiene permiso para edición masiva.' ?>" aria-label="Editar seleccionadas" hidden <?= !$canBulkEdit ? 'disabled' : '' ?>><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 5 4 4M4 20l4-.8L19 8a2.8 2.8 0 0 0-4-4L4 15z"/></svg></button>
                <button class="category-icon-button is-danger <?= !$canBulkDelete ? 'is-blocked' : '' ?>" type="button" id="bulkDelete" title="<?= $canBulkDelete ? 'Eliminar seleccionadas' : 'Tu rol no tiene permiso para eliminación masiva.' ?>" aria-label="Eliminar seleccionadas" disabled><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"/></svg></button>
                <form class="wo-upload-form" method="post" action="/work-orders/import" enctype="multipart/form-data"><label class="category-icon-button <?= !$canImport || !$canAssign ? 'is-blocked' : '' ?>" title="Elegir CSV" aria-label="Elegir CSV">↑<input type="file" name="csv_file" accept=".csv,text/csv" required <?= !$canImport || !$canAssign ? 'disabled title="Se requiere permiso para importar y asignar órdenes."' : '' ?>></label><button class="category-icon-button <?= !$canImport || !$canAssign ? 'is-blocked' : '' ?>" type="submit" title="Importar CSV" aria-label="Importar CSV" <?= !$canImport || !$canAssign ? 'disabled title="Se requiere permiso para importar y asignar órdenes."' : '' ?>>↓</button></form>
                <?php if ($canExport): ?><a class="category-icon-button" href="/work-orders?export=csv" title="Exportar CSV" aria-label="Exportar CSV">⇩</a><?php else: ?><button class="category-icon-button is-blocked" type="button" disabled title="Tu rol no tiene permiso para exportar órdenes." aria-label="Exportar CSV">⇩</button><?php endif; ?>
                <button class="category-icon-button <?= !$canViewExecution ? 'is-blocked' : '' ?>" type="button" id="executionPercent" title="Porcentaje de ejecución" aria-label="Porcentaje de ejecución" <?= !$canViewExecution ? 'disabled title="Tu rol no tiene permiso para ver el porcentaje de ejecución."' : '' ?>>%</button>
                <button class="category-primary-btn category-add-button" type="button" data-open-order="create" <?= !$canCreate ? 'disabled title="No tienes permiso para crear órdenes de trabajo."' : '' ?>><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg><span>Añadir orden</span></button>
            </div>
        </header>
        <section class="wo-panel category-table-panel">
            <header class="category-table-heading"><div><h1>Listado de órdenes de trabajo</h1><p>Consulta y administra las órdenes, responsables y estados.</p></div><form class="category-search" method="get" action="/work-orders"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg><input type="search" name="q" value="<?= $h($search) ?>" placeholder="Buscar orden, actividad o responsable..." aria-label="Buscar orden de trabajo"><?php if ($search !== ''): ?><a href="/work-orders">Limpiar</a><?php endif; ?></form></header>
            <div class="wo-table-wrap category-table-scroll">
                <table class="wo-table category-table" id="workOrdersTable">
                    <thead><tr><th class="wo-select-column">Seleccionar</th><th>Prioridad</th><th>Número OT</th><th>Actividad</th><th>Dirección</th><th>Estado</th><th>Responsable</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php if ($orders === []): ?>
                        <tr><td colspan="8" class="category-table-message"><?= $search !== '' ? 'No se encontraron órdenes de trabajo.' : 'No hay órdenes de trabajo. Usa “Añadir Orden de Trabajo” para crear la primera.' ?></td></tr>
                    <?php else: foreach ($orders as $order): $jsonOrder = $h(json_encode($order, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)); ?>
                        <tr data-order-row>
                            <td class="wo-select-column"><input type="checkbox" class="wo-row-check" value="<?= (int) $order['id'] ?>" aria-label="Seleccionar <?= $h($order['ot_number']) ?>" <?= !$canSelect ? 'disabled title="Tu rol no tiene permiso para seleccionar órdenes."' : '' ?>></td>
                            <td><span class="wo-priority priority-<?= strtolower($h($order['priority'])) ?>"><?= $h($order['priority']) ?></span></td>
                            <td class="wo-number"><?= $h($order['ot_number']) ?></td>
                            <td><?= $h($order['activity']) ?></td>
                            <td><?= $h($order['address']) ?></td>
                            <td><span class="wo-status status-<?= strtolower($h($order['status'])) ?>"><i></i><?= $h($order['status']) ?></span></td>
                            <td><?= $h($order['responsible']) ?></td>
                            <td><div class="category-row-actions">
                                <button type="button" class="category-icon-button <?= !$canView ? 'is-blocked' : '' ?>" data-order-action="view" data-order="<?= $jsonOrder ?>" <?= !$canView ? 'disabled' : '' ?> aria-label="Ver orden" title="<?= $canView ? 'Ver orden' : 'Tu rol no tiene permiso para ver órdenes.' ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button>
                                <button type="button" class="category-icon-button <?= !$canEdit ? 'is-blocked' : '' ?>" data-order-action="edit" data-order="<?= $jsonOrder ?>" <?= !$canEdit ? 'disabled title="Tu rol no tiene permiso para editar órdenes."' : '' ?> aria-label="Editar orden" title="Editar orden"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 5 4 4M4 20l4-.8L19 8a2.8 2.8 0 0 0-4-4L4 15z"/></svg></button>
                                <button type="button" class="category-icon-button is-danger <?= !$canDelete ? 'is-blocked' : '' ?>" data-order-action="delete" data-id="<?= (int) $order['id'] ?>" <?= !$canDelete ? 'disabled title="Tu rol no tiene permiso para eliminar órdenes."' : '' ?> aria-label="Eliminar orden" title="Eliminar orden"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"/></svg></button>
                            </div></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <footer class="category-pagination"><div class="category-pagination-summary"><span><?= count($orders) ?> <?= count($orders) === 1 ? 'orden' : 'órdenes' ?><?= $search !== '' ? ' encontradas' : ' registradas' ?></span><?php if ($canViewExecution): ?><span><?= $completionPercent ?>% ejecutadas</span><?php endif; ?></div><div class="category-pagination-controls"><label>Filas por página<select id="pageSize" aria-label="Filas por página"><option>10</option><option>20</option><option>50</option></select></label><span class="category-page-range" id="pageSummary"></span><div class="category-page-buttons"><button type="button" id="prevPage" class="category-page-button" aria-label="Página anterior">‹</button><button type="button" id="nextPage" class="category-page-button" aria-label="Página siguiente">›</button></div></div></footer>
        </section>
        <dialog class="wo-dialog category-dialog category-form-dialog" id="orderDialog">
            <form method="post" id="orderForm">
                <header class="category-dialog-header"><div><h2 id="orderDialogTitle">Crear Orden de Trabajo</h2><p id="orderDialogDescription">Completa la información de la orden.</p></div><button type="button" class="category-icon-button wo-close" data-close-dialog aria-label="Cerrar">×</button></header>
                <section class="wo-view-card" id="orderReadView" hidden aria-label="Detalle de la orden">
                    <div class="wo-view-document"><div><span>Documento</span><strong data-view-field="ot_number"></strong></div><span class="wo-view-status" data-view-field="status"></span></div>
                    <div class="wo-view-grid">
                        <div class="wo-view-field"><span>Prioridad</span><strong data-view-field="priority"></strong></div>
                        <div class="wo-view-field"><span>Actividad</span><strong data-view-field="activity"></strong></div>
                        <div class="wo-view-field is-wide"><span>Dirección</span><strong data-view-field="address"></strong></div>
                        <div class="wo-view-field"><span>Responsable</span><strong data-view-field="responsible"></strong></div>
                        <div class="wo-view-field"><span>Gestor SST</span><strong data-view-field="sst_manager"></strong></div>
                        <div class="wo-view-field is-wide"><span>Observaciones</span><p data-view-field="observations"></p></div>
                    </div>
                </section>
                <input type="hidden" name="id" id="orderId">
                <div class="wo-form-grid category-order-form-grid">
                    <label>Prioridad <b>*</b><select name="priority" required><?php foreach ($priorities as $priority): ?><option value="<?= $priority ?>"><?= $priority ?></option><?php endforeach; ?></select></label>
                    <label>Número OT <b>*</b><input name="ot_number" maxlength="40" required></label>
                    <label>Actividad <b>*</b><input name="activity" maxlength="180" required></label>
                    <label>Dirección <b>*</b><input name="address" maxlength="255" required></label>
                    <label>Estado <b>*</b><select name="status" required><?php foreach ($statuses as $status): ?><option value="<?= $status ?>"><?= $status ?></option><?php endforeach; ?></select></label>
                    <label>Contrato <b>*</b><select name="contract_id" required><option value="">Selecciona un contrato</option><?php foreach ($contracts as $contract): ?><option value="<?= (int) $contract['id'] ?>"><?= $h($contract['nombre']) ?></option><?php endforeach; ?></select></label>
                    <label>Región <b>*</b><select name="region_id" required><option value="">Selecciona una región</option><?php foreach ($regions as $region): ?><option value="<?= (int) $region['id'] ?>"><?= $h($region['nombre']) ?></option><?php endforeach; ?></select></label>
                    <label>Responsable <b>*</b><select name="responsible_id" required><option value="">Selecciona responsable</option><?php foreach ($employees as $employee): ?><option value="<?= (int) $employee['id'] ?>"><?= $h($employee['nombre']) ?></option><?php endforeach; ?></select></label>
                    <label>Gestor SST <b>*</b><select name="sst_manager_id" required><option value="">Selecciona gestor SST</option><?php foreach ($employees as $employee): ?><option value="<?= (int) $employee['id'] ?>"><?= $h($employee['nombre']) ?></option><?php endforeach; ?></select></label>
                    <label class="wo-observations">Observaciones<textarea name="observations" rows="4"></textarea></label>
                </div>
                <footer class="category-form-actions"><button type="button" class="category-secondary-btn wo-secondary-button" data-close-dialog>Cancelar</button><button type="submit" class="category-primary-btn wo-primary-button" id="saveOrder">Crear Orden de Trabajo</button></footer>
            </form>
        </dialog>
        <form method="post" id="bulkDeleteForm" action="/work-orders/bulk-delete" hidden></form>
        <form method="post" id="singleDeleteForm" hidden></form>
        <div class="wo-toast" id="workOrderToast" role="status" hidden></div>
    </section>
    </main>
    </div>
    <script src="/js/menu.js"></script>
    <script src="/js/work-orders.js?v=category-order-modal-1"></script>
</body>
</html>
