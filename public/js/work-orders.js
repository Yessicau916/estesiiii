document.addEventListener('DOMContentLoaded', () => {
    const app = document.querySelector('#workOrdersApp');
    if (!app) return;
    const dialog = document.querySelector('#orderDialog');
    const form = document.querySelector('#orderForm');
    const readView = document.querySelector('#orderReadView');
    const formGrid = form.querySelector('.wo-form-grid');
    const fieldOrder = [
        'select[name="priority"]', 'input[name="ot_number"]',
        'input[name="activity"]', 'input[name="address"]',
        'select[name="status"]', 'select[name="responsible_id"]',
        'select[name="sst_manager_id"]', 'select[name="contract_id"]',
        'select[name="region_id"]', 'textarea[name="observations"]'
    ];
    fieldOrder.forEach(selector => {
        const field = formGrid.querySelector(selector);
        const label = field?.closest('label');
        if (label) {
            label.classList.add('category-field');
            formGrid.appendChild(label);
        }
    });
    const fields = ['priority', 'ot_number', 'activity', 'address', 'status', 'contract_id', 'region_id', 'responsible_id', 'sst_manager_id', 'observations'];
    const canCreate = app.dataset.canCreate === '1';
    const canEdit = app.dataset.canEdit === '1';
    const canDelete = app.dataset.canDelete === '1';
    const canAssign = app.dataset.canAssign === '1';
    const canSelect = app.dataset.canSelect === '1';
    const canBulkEdit = app.dataset.canBulkEdit === '1';
    const canBulkDelete = app.dataset.canBulkDelete === '1';
    const canImport = app.dataset.canImport === '1';
    const canExport = app.dataset.canExport === '1';
    const canViewExecution = app.dataset.canViewExecution === '1';
    const denied = action => window.alert(`Tu rol no tiene permiso para ${action} órdenes de trabajo.`);

    function openDialog(mode, order = {}) {
        if (mode === 'view' && !app.dataset.canView) return denied('ver');
        if (mode === 'create' && !canCreate) return denied('crear');
        if (mode === 'edit' && !canEdit) return denied('editar');
        form.action = mode === 'edit' ? `/work-orders/update/${encodeURIComponent(order.id)}` : '/work-orders/create';
        const viewing = mode === 'view';
        dialog.classList.toggle('is-viewing', viewing);
        dialog.classList.toggle('is-editing', mode === 'edit');
        dialog.classList.toggle('is-creating', mode === 'create');
        dialog.classList.toggle('category-form-dialog', !viewing);
        readView.hidden = !viewing;
        formGrid.hidden = viewing;
        if (viewing) {
            readView.querySelectorAll('[data-view-field]').forEach(element => {
                const value = order[element.dataset.viewField];
                element.textContent = value == null || String(value).trim() === '' ? 'Sin información' : String(value);
            });
            const status = readView.querySelector('[data-view-field="status"]');
            status.className = `wo-view-status status-${String(order.status || '').toLowerCase()}`;
        }
        form.querySelectorAll('.wo-preserved-value').forEach(input => input.remove());
        document.querySelector('#orderDialogTitle').textContent = mode === 'view' ? 'Ver Orden de Trabajo' : mode === 'edit' ? 'Editar Orden de Trabajo' : 'Crear Orden de Trabajo';
        document.querySelector('#orderDialogDescription').textContent = mode === 'view' ? 'Consulta los datos de la orden.' : mode === 'edit' ? 'Actualiza los datos de la orden.' : 'Completa la información de la orden.';
        fields.forEach(name => {
            const input = form.elements.namedItem(name);
            input.value = order[name] || '';
            const assignmentField = ['contract_id', 'region_id', 'responsible_id', 'sst_manager_id'].includes(name);
            input.disabled = mode === 'view' || (mode === 'edit' && assignmentField && !canAssign);
            if (mode === 'edit' && assignmentField && !canAssign) {
                input.title = 'Tu rol no tiene permiso para asignar personal.';
                const preserved = document.createElement('input');
                preserved.type = 'hidden';
                preserved.name = name;
                preserved.value = order[name] || '';
                preserved.className = 'wo-preserved-value';
                form.appendChild(preserved);
            }
        });
        document.querySelector('#saveOrder').hidden = mode === 'view';
        document.querySelector('#saveOrder').textContent = mode === 'edit' ? 'Guardar cambios' : 'Crear Orden de Trabajo';
        dialog.showModal();
    }

    document.querySelectorAll('[data-open-order]').forEach(button => button.addEventListener('click', () => openDialog(button.dataset.openOrder)));
    document.querySelectorAll('[data-close-dialog]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    document.querySelectorAll('[data-order-action="view"], [data-order-action="edit"]').forEach(button => button.addEventListener('click', () => openDialog(button.dataset.orderAction, JSON.parse(button.dataset.order || '{}'))));
    document.querySelectorAll('[data-order-action="delete"]').forEach(button => button.addEventListener('click', () => {
        if (!canDelete) return denied('eliminar');
        if (!window.confirm(`¿Eliminar la orden ${button.dataset.id}? Esta acción no se puede deshacer.`)) return;
        const deleteForm = document.querySelector('#singleDeleteForm');
        deleteForm.action = `/work-orders/delete/${encodeURIComponent(button.dataset.id)}`;
        deleteForm.submit();
    }));

    const table = document.querySelector('#workOrdersTable');
    const selectionButton = document.querySelector('#toggleSelection');
    const bulkDeleteButton = document.querySelector('#bulkDelete');
    const bulkEditButton = document.querySelector('#bulkEdit');
    selectionButton.addEventListener('click', () => {
        if (!canSelect) return denied('seleccionar');
        table.classList.toggle('wo-selection-mode');
        selectionButton.title = table.classList.contains('wo-selection-mode') ? 'Cancelar selección' : 'Seleccionar órdenes';
    });
    const checkboxes = [...document.querySelectorAll('.wo-row-check')];
    const selectedIds = () => checkboxes.filter(box => box.checked).map(box => box.value);
    checkboxes.forEach(box => box.addEventListener('change', () => {
        bulkDeleteButton.disabled = selectedIds().length === 0 || !canBulkDelete;
        bulkEditButton.hidden = selectedIds().length === 0;
        bulkEditButton.disabled = !canBulkEdit;
    }));
    bulkEditButton.addEventListener('click', () => {
        if (!canBulkEdit) return denied('editar de forma masiva');
        if (!canEdit) return denied('editar');
        const first = checkboxes.find(box => box.checked);
        const action = first?.closest('tr')?.querySelector('[data-order-action="edit"]');
        if (action && !action.disabled) action.click();
        else if (action?.disabled) denied('editar');
    });
    bulkDeleteButton.addEventListener('click', () => {
        const ids = selectedIds();
        if (!canBulkDelete) return denied('eliminar de forma masiva');
        if (!ids.length || !window.confirm(`¿Eliminar las ${ids.length} órdenes seleccionadas?`)) return;
        const bulkForm = document.querySelector('#bulkDeleteForm');
        ids.forEach(id => { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'ids[]'; input.value = id; bulkForm.appendChild(input); });
        bulkForm.submit();
    });

    const rows = [...document.querySelectorAll('[data-order-row]')];
    const pageSize = document.querySelector('#pageSize');
    const summary = document.querySelector('#pageSummary');
    const prev = document.querySelector('#prevPage');
    const next = document.querySelector('#nextPage');
    let page = 1;
    function renderPage() {
        const size = Number(pageSize.value);
        const pages = Math.max(1, Math.ceil(rows.length / size));
        page = Math.min(page, pages);
        rows.forEach((row, index) => { row.hidden = index < (page - 1) * size || index >= page * size; });
        summary.textContent = `${page} de ${pages}`;
        prev.disabled = page <= 1;
        next.disabled = page >= pages;
    }
    pageSize.addEventListener('change', () => { page = 1; renderPage(); });
    prev.addEventListener('click', () => { page--; renderPage(); });
    next.addEventListener('click', () => { page++; renderPage(); });
    renderPage();

    document.querySelector('#executionPercent').addEventListener('click', () => {
        if (!canViewExecution) return denied('ver el porcentaje de ejecución de');
        const done = rows.filter(row => row.querySelector('.wo-status')?.textContent.trim().toUpperCase() === 'EJECUTADO').length;
        const percent = rows.length ? Math.round(done / rows.length * 100) : 0;
        window.alert(`Ejecución: ${percent}% de las órdenes mostradas están ejecutadas.`);
    });
    if (!canImport) document.querySelector('.wo-upload-form')?.addEventListener('submit', event => { event.preventDefault(); denied('importar'); });
    if (!canExport) document.querySelector('a[href*="export=csv"]')?.addEventListener('click', event => { event.preventDefault(); denied('exportar'); });
});
