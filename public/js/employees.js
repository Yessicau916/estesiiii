document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('employeeApp');
    if (!app) return;

    const roles = JSON.parse(app.dataset.roles || '[]');
    const directoryTestMode = app.dataset.directoryMode === 'mock';
    const permissions = window.APP_PERMISSIONS || {};
    const hasPermission = action => (permissions.employees || []).includes(action);
    const iconPaths = {
        people: '<circle cx="9" cy="8" r="3.5"/><path d="M3 20v-1.5a6 6 0 0 1 12 0V20M16 5.5a3.5 3.5 0 0 1 0 6.8M18 14a5 5 0 0 1 3 4.5V20"/>',
        refresh: '<path d="M20 7v5h-5M4 17v-5h5"/><path d="M5.6 9a7 7 0 0 1 11.6-2L20 12M4 12l2.8 5a7 7 0 0 0 11.6-2"/>',
        selection: '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="m8 12 2.5 2.5L16 9"/>',
        square: '<rect x="3" y="3" width="18" height="18" rx="3"/>',
        trash: '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"/>',
        dashboard: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        plus: '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
        search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        edit: '<path d="m15 5 4 4M4 20l4-.8L19 8a2.8 2.8 0 0 0-4-4L4 15z"/>',
        close: '<path d="m6 6 12 12M18 6 6 18"/>',
        arrowLeft: '<path d="m12 19-7-7 7-7M5 12h14"/>',
        arrowRight: '<path d="M5 12h14m-7-7 7 7-7 7"/>',
        alert: '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>',
        check: '<path d="m5 12 4 4L19 6"/>',
        signature: '<path d="M3 19c3-1 4-8 6-8s0 7 3 7 3-8 5-8 0 7 4 8"/><path d="M3 22h18"/>'
    };

    const state = {
        employees: [],
        loading: true,
        submitting: false,
        error: '',
        feedback: null,
        searchTerm: '',
        selectedIds: new Set(),
        selectionMode: false,
        showDashboard: false,
        modalMode: null,
        currentEmployee: null,
        employeeToDelete: null,
        currentPage: 1,
        pageSize: 10,
        signatureData: '',
        removeSignature: false,
        directoryQuery: '',
        directoryResults: [],
        directoryLoading: false,
        directoryError: '',
        directorySearchTimer: null,
        selectedDirectoryUser: null,
        selectedRoleId: ''
    };

    function icon(name, size = 18) {
        return `<svg aria-hidden="true" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${iconPaths[name] || ''}</svg>`;
    }

    function escapeHTML(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[character]);
    }

    function fullName(employee) {
        return `${employee.nombres || ''} ${employee.apellidos || ''}`.trim();
    }

    function isActive(employee) {
        return ['1', 'ACTIVO', 'ACTIVE', 'TRUE'].includes(String(employee.estado).toUpperCase());
    }

    async function request(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...options.headers
            }
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || 'No se pudo completar la solicitud.');
        return payload;
    }

    function filteredEmployees() {
        const term = state.searchTerm.trim().toLowerCase();
        if (!term) return state.employees;
        return state.employees.filter(employee => [
            employee.id, employee.rol_nombre, employee.cargo, fullName(employee),
            employee.tipo_documento, employee.documento, employee.email
        ].some(value => String(value || '').toLowerCase().includes(term)));
    }

    function visibleEmployees() {
        const start = (state.currentPage - 1) * state.pageSize;
        return filteredEmployees().slice(start, start + state.pageSize);
    }

    function stats() {
        const active = state.employees.filter(isActive).length;
        const connected = state.employees.filter(employee => String(employee.estadoDA) === '1').length;
        return { total: state.employees.length, active, inactive: state.employees.length - active, connected };
    }

    function showFeedback(message, type = 'success') {
        state.feedback = { message, type };
        render();
        window.clearTimeout(showFeedback.timeout);
        showFeedback.timeout = window.setTimeout(() => {
            state.feedback = null;
            render();
        }, 3500);
    }

    async function loadEmployees(showLoading = true) {
        if (showLoading) {
            state.loading = true;
            state.error = '';
            render();
        }
        try {
            const data = await request('/api/employees');
            state.employees = Array.isArray(data) ? data : [];
            const knownIds = new Set(state.employees.map(employee => String(employee.id)));
            state.selectedIds = new Set([...state.selectedIds].filter(id => knownIds.has(id)));
            state.loading = false;
            state.error = '';
            render();
            return true;
        } catch (error) {
            state.loading = false;
            state.error = error.message || 'No se pudieron cargar los empleados.';
            render();
            return false;
        }
    }

    function updateDirectoryResults() {
        const results = app.querySelector('#directorySearchResults');
        if (!results) return;
        results.innerHTML = renderDirectoryResults();
        results.querySelectorAll('[data-action]').forEach(control => control.addEventListener('click', handleAction));
    }

    async function searchDirectoryUsers(query) {
        if (query !== state.directoryQuery || (query.trim().length < 2 && !(directoryTestMode && query.trim().length === 0))) return;
        state.directoryLoading = true;
        state.directoryError = '';
        updateDirectoryResults();
        try {
            const users = await request(`/api/directory-users?q=${encodeURIComponent(query.trim())}`);
            if (query !== state.directoryQuery) return;
            state.directoryResults = Array.isArray(users) ? users : [];
        } catch (error) {
            if (query !== state.directoryQuery) return;
            state.directoryResults = [];
            state.directoryError = error.message || 'No se pudo consultar Active Directory.';
        } finally {
            if (query === state.directoryQuery) {
                state.directoryLoading = false;
                updateDirectoryResults();
            }
        }
    }

    function renderDashboard() {
        const summary = stats();
        const activePercent = summary.total ? Math.round(summary.active / summary.total * 100) : 0;
        const inactivePercent = 100 - activePercent;
        return `<div class="employee-overlay" data-overlay="dashboard"><section class="employee-dialog employee-dashboard" role="dialog" aria-modal="true" aria-labelledby="employee-dashboard-title">
            <header class="employee-dialog-header"><div><h2 id="employee-dashboard-title">Dashboard de empleados</h2><p>Resumen del personal registrado.</p></div><button class="employee-icon-button" type="button" data-action="close-dashboard" aria-label="Cerrar dashboard">${icon('close')}</button></header>
            <div class="employee-dashboard-stats"><article><span>Total de empleados</span><strong>${summary.total}</strong><small>Registros en el sistema</small></article><article class="is-active"><span>Activos</span><strong>${summary.active}</strong><small>${activePercent}% del total</small></article><article class="is-inactive"><span>Inactivos</span><strong>${summary.inactive}</strong><small>${inactivePercent}% del total</small></article><article class="is-directory"><span>Directorio activo</span><strong>${summary.connected}</strong><small>Cuentas vinculadas</small></article></div>
            <div class="employee-distribution"><div><span>Activos (${summary.active})</span><span>Inactivos (${summary.inactive})</span></div><div class="employee-progress"><span style="width:${activePercent}%"></span><span style="width:${inactivePercent}%"></span></div></div>
        </section></div>`;
    }

    function renderDirectoryResults() {
        if (state.directoryLoading) return '<p class="employee-directory-message">Buscando en Active Directory…</p>';
        if (state.directoryError) return `<p class="employee-directory-message is-error">${escapeHTML(state.directoryError)}</p>`;
        if (!directoryTestMode && state.directoryQuery.trim().length < 2) return '<p class="employee-directory-message">Escribe al menos 2 caracteres para buscar.</p>';
        if (directoryTestMode && state.directoryQuery.trim().length === 0 && state.directoryResults.length === 0) return '<p class="employee-directory-message">Cargando usuarios de prueba…</p>';
        if (state.directoryResults.length === 0) return '<p class="employee-directory-message">No se encontraron usuarios.</p>';

        return state.directoryResults.map(user => `<button class="employee-directory-result" type="button" data-action="select-directory-user" data-id="${escapeHTML(user.id)}">
            <span class="employee-directory-avatar">${escapeHTML((user.display_name || user.account || '?').slice(0, 1).toUpperCase())}</span>
            <span class="employee-directory-result-copy"><strong>${escapeHTML(user.display_name)}</strong><small>${escapeHTML(user.email || user.account)}</small></span>
            <span class="employee-directory-account">${escapeHTML(user.account)}</span>
        </button>`).join('');
    }

    function renderDirectoryPicker() {
        return `<div class="employee-overlay" data-overlay="employee-form"><section class="employee-dialog employee-form-dialog employee-directory-dialog" role="dialog" aria-modal="true" aria-labelledby="employee-form-title">
            <header class="employee-dialog-header"><div><h2 id="employee-form-title">Añadir empleado</h2><p>Primero busca y selecciona una cuenta de Active Directory.</p></div><button class="employee-icon-button" type="button" data-action="close-form" aria-label="Cerrar">${icon('close')}</button></header>
            <div class="employee-directory-step">${directoryTestMode ? '<p class="employee-directory-test-mode" role="status">Modo de prueba: estas cuentas son ejemplos locales, no vienen de Active Directory.</p>' : ''}<label class="employee-field"><span>Buscar usuario del directorio</span><span class="employee-directory-search">${icon('search')}<input id="directorySearch" type="search" value="${escapeHTML(state.directoryQuery)}" placeholder="Nombre, correo o cuenta…" autocomplete="off" aria-label="Buscar usuario de Active Directory"></span></label><div id="directorySearchResults" class="employee-directory-results" role="listbox" aria-label="Resultados de Active Directory">${renderDirectoryResults()}</div></div>
            <footer class="employee-form-actions"><button class="employee-secondary-button" type="button" data-action="close-form">Cancelar</button></footer>
        </section></div>`;
    }

    function renderRoleStep() {
        const user = state.selectedDirectoryUser;
        const roleOptions = roles.map(role => `<option value="${escapeHTML(role.id)}">${escapeHTML(role.nombre)}</option>`).join('');
        return `<div class="employee-overlay" data-overlay="employee-form"><section class="employee-dialog employee-form-dialog employee-directory-dialog" role="dialog" aria-modal="true" aria-labelledby="employee-form-title">
            <header class="employee-dialog-header"><div><h2 id="employee-form-title">Asignar rol</h2><p>Confirma el usuario y selecciona su rol para continuar.</p></div><button class="employee-icon-button" type="button" data-action="close-form" aria-label="Cerrar">${icon('close')}</button></header>
            <div class="employee-directory-step"><article class="employee-selected-user"><span class="employee-directory-avatar">${escapeHTML((user.display_name || '?').slice(0, 1).toUpperCase())}</span><div><strong>${escapeHTML(user.display_name)}</strong><small>${escapeHTML(user.email || user.account)} · ${escapeHTML(user.account)}</small></div><button class="employee-text-button" type="button" data-action="change-directory-user">Cambiar</button></article>
                <label class="employee-field"><span>Rol del sistema <b>*</b></span><select id="employeeRoleSelect" required><option value="">Selecciona un rol</option>${roleOptions}</select></label>
            </div>
            <footer class="employee-form-actions"><button class="employee-secondary-button" type="button" data-action="close-form">Cancelar</button></footer>
        </section></div>`;
    }

    function renderForm() {
        if (!state.modalMode) return '';
        const viewing = state.modalMode === 'view';
        const creating = state.modalMode === 'create';
        if (creating && !state.selectedDirectoryUser) return renderDirectoryPicker();
        if (creating && !state.selectedRoleId) return renderRoleStep();
        const employee = state.currentEmployee || {};
        const heading = viewing ? 'Ver empleado' : state.modalMode === 'edit' ? 'Editar empleado' : 'Añadir empleado';
        const roleOptions = roles.map(role => `<option value="${escapeHTML(role.id)}" ${String(employee.rol_id || '') === String(role.id) ? 'selected' : ''}>${escapeHTML(role.nombre)}</option>`).join('');
        const selectedRole = roles.find(role => String(role.id) === String(state.selectedRoleId));
        const status = employee.estado === undefined ? '1' : String(employee.estado);
        const directoryStatus = String(employee.estadoDA || '0') === '1';
        const displayValue = value => escapeHTML(value || 'Sin información');
        const viewCard = viewing ? `<section class="employee-view-card" aria-label="Detalle del empleado">
            <div class="employee-view-document"><div><span>Empleado</span><strong>${escapeHTML(fullName(employee) || 'Sin nombre')}</strong><small>${displayValue(employee.tipo_documento)} · ${displayValue(employee.documento)}</small></div><div class="employee-view-role"><strong>${displayValue(employee.rol_nombre)}</strong><span>${displayValue(employee.cargo)}</span></div></div>
            <div class="employee-view-grid">
                <div class="employee-view-field is-wide">
                <span>Correo electrónico</span>
                <strong>${displayValue(employee.email)}</strong>
            </div>
                <div class="employee-view-field">
                <span>Fecha de ingreso</span>
                <strong>${displayValue(employee.fecha_ingreso)}</strong>
            </div>
                <div class="employee-view-field">
                <span>Estado</span>
                <span class="employee-view-status ${isActive(employee) ? 'is-active' : 'is-inactive'}">${isActive(employee) ? 'Activo' : 'Inactivo'}
                </span>
            </div>
                <div class="employee-view-field is-wide"><span>Firma</span>${employee.firma_ruta ? `<img class="employee-view-signature" src="${escapeHTML(employee.firma_ruta)}" alt="Firma de ${escapeHTML(fullName(employee))}">` : '<p>Sin firma registrada</p>'}</div>
            </div>
        </section>` : '';

        return `<div class="employee-overlay" data-overlay="employee-form"><section class="employee-dialog employee-form-dialog ${viewing ? 'is-viewing' : state.modalMode === 'edit' ? 'is-editing' : ''}" role="dialog" aria-modal="true" aria-labelledby="employee-form-title">
            <header class="employee-dialog-header"><div><h2 id="employee-form-title">${heading}</h2><p>${viewing ? 'Consulta la información registrada.' : state.modalMode === 'edit' ? 'Actualiza la información del empleado.' : 'Completa los datos del nuevo empleado.'}</p></div><button class="employee-icon-button" type="button" data-action="close-form" aria-label="Cerrar">${icon('close')}</button></header>
            <form id="employeeForm" novalidate>
                ${viewCard}
                ${creating ? `<article class="employee-selected-user"><span class="employee-directory-avatar">${escapeHTML((state.selectedDirectoryUser.display_name || '?').slice(0, 1).toUpperCase())}</span><div><strong>${escapeHTML(state.selectedDirectoryUser.display_name)}</strong><small>${escapeHTML(state.selectedDirectoryUser.email || state.selectedDirectoryUser.account)} · ${escapeHTML(state.selectedDirectoryUser.account)}</small></div><button class="employee-text-button" type="button" data-action="change-directory-user">Cambiar usuario</button></article>` : ''}
                <div class="employee-form-grid">
                    ${creating ? `<div class="employee-field"><span>Rol seleccionado</span><strong class="employee-selected-role">${escapeHTML(selectedRole?.nombre || '')}</strong><button class="employee-text-button" type="button" data-action="change-role">Cambiar rol</button></div>` : `<label class="employee-field"><span>Rol</span><select name="rol_id" ${viewing ? 'disabled' : ''}><option value="">Sin rol asignado</option>${roleOptions}</select></label>`}
                    <label class="employee-field"><span>Cargo <b>*</b></span><input name="cargo" required maxlength="100" value="${escapeHTML(employee.cargo)}" placeholder="Ej. Técnico de campo" ${viewing ? 'disabled' : ''}></label>
                    <label class="employee-field"><span>Nombres <b>*</b></span><input name="nombres" required maxlength="100" value="${escapeHTML(employee.nombres)}" ${viewing ? 'disabled' : ''}></label>
                    <label class="employee-field"><span>Apellidos <b>*</b></span><input name="apellidos" required maxlength="100" value="${escapeHTML(employee.apellidos)}" ${viewing ? 'disabled' : ''}></label>
                    <label class="employee-field"><span>Tipo de documento <b>*</b></span><select name="tipo_documento" required ${viewing ? 'disabled' : ''}>${['CC', 'CE', 'TI', 'PASAPORTE', 'NIT'].map(type => `<option value="${type}" ${employee.tipo_documento === type ? 'selected' : ''}>${type}</option>`).join('')}</select></label>
                    <label class="employee-field"><span>Número de documento <b>*</b></span><input name="documento" required maxlength="30" value="${escapeHTML(employee.documento)}" ${viewing ? 'disabled' : ''}></label>
                    <label class="employee-field"><span>Correo electrónico <b>*</b></span><input name="email" type="email" required maxlength="150" value="${escapeHTML(employee.email)}" ${viewing ? 'disabled' : ''}></label>
                    <label class="employee-field"><span>Fecha de ingreso <b>*</b></span><input name="fecha_ingreso" type="date" required value="${escapeHTML(employee.fecha_ingreso)}" ${viewing ? 'disabled' : ''}></label>
                    ${!viewing ? `<label class="employee-field"><span>Contraseña inicial ${state.modalMode === 'create' ? '<b>*</b>' : ''}</span><input name="password" type="password" autocomplete="new-password" minlength="8" ${state.modalMode === 'create' ? 'required' : ''} placeholder="${state.modalMode === 'edit' ? 'Deja vacío para conservarla' : 'Mínimo 8 caracteres'}"></label>` : ''}
                    <label class="employee-field"><span>Estado</span><select name="estado" ${viewing ? 'disabled' : ''}><option value="1" ${status === '1' ? 'selected' : ''}>Activo</option><option value="0" ${status === '0' ? 'selected' : ''}>Inactivo</option></select></label>
                    ${creating ? `<div class="employee-field"><span>Origen de cuenta</span><strong class="employee-directory-status is-connected">Vinculada a Active Directory</strong></div>` : ''}
                </div>
                <section class="employee-signature"><div class="employee-signature-heading"><div><h3>Firma</h3><p>${viewing ? 'Firma almacenada del empleado.' : 'Dibuja la firma o conserva la existente.'}</p></div>${!viewing ? `<button class="employee-text-button" type="button" data-action="clear-signature">Limpiar</button>` : ''}</div>
                    <div class="employee-signature-pad ${viewing ? 'is-readonly' : ''}">${employee.firma_ruta && !state.removeSignature ? `<img src="${escapeHTML(employee.firma_ruta)}" alt="Firma de ${escapeHTML(fullName(employee))}">` : ''}${!viewing ? `<canvas id="signatureCanvas" width="720" height="180" aria-label="Área para dibujar la firma"></canvas>` : !employee.firma_ruta ? '<span class="employee-no-signature">Sin firma registrada</span>' : ''}</div>
                    ${!viewing && employee.firma_ruta ? `<label class="employee-remove-signature"><input type="checkbox" name="remove_signature" ${state.removeSignature ? 'checked' : ''}><span>Eliminar firma guardada</span></label>` : ''}
                </section>
                <footer class="employee-form-actions"><button class="employee-secondary-button" type="button" data-action="close-form">Cancelar</button>${!viewing ? `<button class="employee-primary-button" type="submit" ${state.submitting ? 'disabled' : ''}>${state.submitting ? 'Guardando…' : state.modalMode === 'edit' ? 'Guardar cambios' : 'Crear empleado'}</button>` : ''}</footer>
            </form>
        </section></div>`;
    }

    function renderDeleteDialog() {
        if (!state.employeeToDelete) return '';
        return `<div class="employee-overlay" data-overlay="delete"><section class="employee-dialog employee-delete-dialog" role="dialog" aria-modal="true" aria-labelledby="employee-delete-title"><div class="employee-delete-mark">${icon('trash', 22)}</div><h2 id="employee-delete-title">¿Eliminar empleado?</h2><p>Se eliminará el registro de <strong>${escapeHTML(fullName(state.employeeToDelete))}</strong>. Esta acción no se puede deshacer.</p><footer class="employee-form-actions"><button class="employee-secondary-button" type="button" data-action="cancel-delete">Cancelar</button><button class="employee-danger-button" type="button" data-action="confirm-delete" ${state.submitting ? 'disabled' : ''}>${state.submitting ? 'Eliminando…' : 'Eliminar'}</button></footer></section></div>`;
    }

    function render() {
        const filtered = filteredEmployees();
        const pageCount = Math.max(1, Math.ceil(filtered.length / state.pageSize));
        state.currentPage = Math.min(state.currentPage, pageCount);
        const pageRows = visibleEmployees();
        const summary = stats();
        const start = filtered.length ? (state.currentPage - 1) * state.pageSize + 1 : 0;
        const end = Math.min(state.currentPage * state.pageSize, filtered.length);
        const allVisibleSelected = pageRows.length > 0 && pageRows.every(employee => state.selectedIds.has(String(employee.id)));
        const feedback = state.feedback ? `<div class="employee-feedback ${state.feedback.type === 'error' ? 'is-error' : ''}" role="status">${icon(state.feedback.type === 'error' ? 'alert' : 'check')}<span>${escapeHTML(state.feedback.message)}</span></div>` : '';
        let body;
        if (state.loading) {
            body = `<tr><td colspan="${state.selectionMode ? 9 : 8}" class="employee-table-message"><span class="employee-spinner"></span><span>Cargando empleados…</span></td></tr>`;
        } else if (state.error) {
            body = `<tr><td colspan="${state.selectionMode ? 9 : 8}" class="employee-table-message is-error">${escapeHTML(state.error)}<button class="employee-link-button" type="button" data-action="refresh">Reintentar</button></td></tr>`;
        } else if (pageRows.length === 0) {
            body = `<tr><td colspan="${state.selectionMode ? 9 : 8}" class="employee-table-message"><strong>${state.searchTerm ? 'No se encontraron coincidencias' : 'Aún no hay empleados'}</strong><span>${state.searchTerm ? 'Prueba con otro nombre, correo o documento.' : 'Agrega el primer registro para comenzar.'}</span>${!state.searchTerm ? '<button class="employee-link-button" type="button" data-action="create">Añadir empleado</button>' : ''}</td></tr>`;
        } else {
            body = pageRows.map(employee => {
                const active = isActive(employee);
                return `<tr data-employee-row>
                    ${state.selectionMode ? `<td class="employee-selection-cell"><input type="checkbox" data-action="select-employee" data-id="${escapeHTML(employee.id)}" aria-label="Seleccionar ${escapeHTML(fullName(employee))}" ${state.selectedIds.has(String(employee.id)) ? 'checked' : ''}></td>` : ''}
                    <td class="employee-id-cell">${escapeHTML(employee.id)}</td><td><strong class="employee-name">${escapeHTML(employee.rol_nombre || 'Sin rol')}</strong></td>
                    <td><strong class="employee-name">${escapeHTML(fullName(employee))}</strong><small class="employee-subline">${escapeHTML(employee.tipo_documento)} ${escapeHTML(employee.documento)}</small></td>
                    <td>${escapeHTML(employee.cargo)}</td><td class="employee-email">${escapeHTML(employee.email)}</td>
                    <td><span class="employee-status ${active ? 'is-active' : 'is-inactive'}"><span></span>${active ? 'Activo' : 'Inactivo'}</span></td>
                    <td>${employee.firma_ruta ? `<a class="employee-signature-link" href="${escapeHTML(employee.firma_ruta)}" target="_blank" rel="noopener">${icon('signature', 16)}Ver firma</a>` : '<span class="employee-no-signature">Sin firma</span>'}</td>
                    <td><div class="employee-row-actions"><button class="employee-icon-button" type="button" data-action="view" data-id="${escapeHTML(employee.id)}" aria-label="Ver empleado" title="Ver empleado">${icon('eye')}</button><button class="employee-icon-button" type="button" data-action="edit" data-id="${escapeHTML(employee.id)}" aria-label="Editar empleado" title="Editar empleado">${icon('edit')}</button><button class="employee-icon-button is-danger" type="button" data-action="delete" data-id="${escapeHTML(employee.id)}" aria-label="Eliminar empleado" title="Eliminar empleado">${icon('trash')}</button></div></td>
                </tr>`;
            }).join('');
        }

        app.innerHTML = `<section class="employees-screen">
            ${feedback}
            ${!hasPermission('crear') ? '<div class="employee-permission-notice" role="status">Tu rol no tiene permiso para crear empleados. Solicita que te asignen el privilegio <strong>Empleados → Crear</strong>.</div>' : ''}
            <header class="employees-toolbar"><div class="employees-context"><span class="employees-mark">${icon('people', 19)}</span><div><span class="employees-app-name">APPSIG</span><div class="employees-breadcrumb"><strong>Empleados</strong><span>/</span><strong>Enecon</strong></div></div></div>
                <div class="employees-actions"><button class="employee-icon-button" type="button" data-action="refresh" aria-label="Recargar empleados" title="Actualizar lista" ${state.loading ? 'disabled' : ''}>${icon('refresh')}</button><button class="employee-icon-button ${state.selectionMode ? 'is-selected' : ''}" type="button" data-action="selection-mode" aria-label="${state.selectionMode ? 'Cancelar selección' : 'Seleccionar empleados'}" title="${state.selectionMode ? 'Cancelar selección' : 'Modo selección'}">${icon(state.selectionMode ? 'square' : 'selection')}</button>${state.selectedIds.size ? `<button class="employee-icon-button is-danger" type="button" data-action="bulk-delete" aria-label="Eliminar seleccionados" title="Eliminar seleccionados (${state.selectedIds.size})">${icon('trash')}<span>${state.selectedIds.size}</span></button>` : ''}<button class="employee-icon-button" type="button" data-action="dashboard" aria-label="Abrir dashboard" title="Dashboard de empleados">${icon('dashboard')}</button><button class="employee-primary-button employee-add-button" type="button" data-action="create">${icon('plus')}<span>Añadir empleado</span></button></div>
            </header>
            <section class="employee-table-panel"><header class="employee-table-heading"><div><h1>Listado de empleados</h1><p>Gestiona la información y el estado del personal.</p></div><label class="employee-search">${icon('search')}<input id="employeeSearch" type="search" value="${escapeHTML(state.searchTerm)}" placeholder="Buscar por nombre, documento, cargo o correo…" aria-label="Buscar empleado"></label></header>
                <div class="employee-table-scroll"><table class="employee-table"><thead><tr>${state.selectionMode ? `<th class="employee-selection-cell"><input type="checkbox" data-action="select-visible" aria-label="Seleccionar empleados de esta página" ${allVisibleSelected ? 'checked' : ''}></th>` : ''}<th>ID</th><th>Rol</th><th>Empleado</th><th>Cargo</th><th>Correo</th><th>Estado</th><th>Firma</th><th>Acciones</th></tr></thead><tbody>${body}</tbody></table></div>
                <footer class="employee-pagination"><div class="employee-pagination-summary"><span>${filtered.length} ${filtered.length === 1 ? 'empleado' : 'empleados'}${state.searchTerm ? ' encontrados' : ' registrados'}</span><span>${summary.active} activos · ${summary.inactive} inactivos</span></div><div class="employee-pagination-controls"><label>Filas por página<select id="employeePageSize" aria-label="Filas por página">${[5, 10, 20, 50].map(size => `<option value="${size}" ${state.pageSize === size ? 'selected' : ''}>${size}</option>`).join('')}</select></label><span class="employee-page-range">${start}–${end} de ${filtered.length}</span><nav class="employee-page-buttons" aria-label="Paginación"><button class="employee-page-button" type="button" data-action="page-prev" aria-label="Página anterior" ${state.currentPage <= 1 ? 'disabled' : ''}>${icon('arrowLeft', 15)}</button><span>Página ${state.currentPage} de ${pageCount}</span><button class="employee-page-button" type="button" data-action="page-next" aria-label="Página siguiente" ${state.currentPage >= pageCount ? 'disabled' : ''}>${icon('arrowRight', 15)}</button></nav></div></footer>
            </section>
            ${state.showDashboard ? renderDashboard() : ''}${renderForm()}${renderDeleteDialog()}
        </section>`;

        const employeeActionPermission = { create: 'crear', view: 'ver', edit: 'editar', delete: 'eliminar', 'confirm-delete': 'eliminar', 'bulk-delete': 'eliminar', 'selection-mode': 'eliminar', 'select-visible': 'eliminar', 'select-employee': 'eliminar', refresh: 'ver', dashboard: 'ver' };
        app.querySelectorAll('[data-action]').forEach(control => {
            const needed = employeeActionPermission[control.dataset.action];
            if (needed && !hasPermission(needed)) {
                control.disabled = true;
                control.classList.add('is-permission-blocked');
                control.setAttribute('aria-disabled', 'true');
                control.title = `Acción bloqueada: tu rol no tiene permiso para ${needed} empleados.`;
            }
        });
        app.querySelector('#employeeSearch')?.addEventListener('input', event => {
            state.searchTerm = event.target.value;
            state.currentPage = 1;
            render();
            const input = app.querySelector('#employeeSearch');
            input?.focus();
            input?.setSelectionRange(state.searchTerm.length, state.searchTerm.length);
        });
        app.querySelector('#employeePageSize')?.addEventListener('change', event => {
            state.pageSize = Number(event.target.value);
            state.currentPage = 1;
            render();
        });
        app.querySelector('#directorySearch')?.addEventListener('input', event => {
            state.directoryQuery = event.target.value;
            state.directoryResults = [];
            state.directoryError = '';
            state.directoryLoading = false;
            window.clearTimeout(state.directorySearchTimer);
            updateDirectoryResults();
            if (state.directoryQuery.trim().length >= 2 || (directoryTestMode && state.directoryQuery.trim().length === 0)) {
                const query = state.directoryQuery;
                state.directorySearchTimer = window.setTimeout(() => searchDirectoryUsers(query), 300);
            }
        });
        app.querySelector('#employeeRoleSelect')?.addEventListener('change', event => {
            if (!event.target.value) return;
            state.selectedRoleId = event.target.value;
            state.currentEmployee.rol_id = event.target.value;
            render();
        });
        app.querySelectorAll('[data-action]').forEach(control => control.addEventListener(control.type === 'checkbox' ? 'change' : 'click', handleAction));
        app.querySelector('[data-overlay="dashboard"]')?.addEventListener('click', event => {
            if (event.target === event.currentTarget) { state.showDashboard = false; render(); }
        });
        app.querySelector('[data-overlay="employee-form"]')?.addEventListener('click', event => {
            if (event.target === event.currentTarget) closeForm();
        });
        app.querySelector('[data-overlay="delete"]')?.addEventListener('click', event => {
            if (event.target === event.currentTarget) { state.employeeToDelete = null; render(); }
        });
        app.querySelector('[name="remove_signature"]')?.addEventListener('change', event => {
            state.removeSignature = event.target.checked;
        });
        app.querySelector('#employeeForm')?.addEventListener('submit', saveEmployee);
        bindSignatureCanvas();
    }

    function bindSignatureCanvas() {
        const canvas = app.querySelector('#signatureCanvas');
        if (!canvas) return;
        const context = canvas.getContext('2d');
        let drawing = false;
        if (state.signatureData) {
            const savedSignature = new Image();
            savedSignature.onload = () => context.drawImage(savedSignature, 0, 0, canvas.width, canvas.height);
            savedSignature.src = state.signatureData;
        }
        const point = event => {
            const rect = canvas.getBoundingClientRect();
            return { x: (event.clientX - rect.left) * canvas.width / rect.width, y: (event.clientY - rect.top) * canvas.height / rect.height };
        };
        canvas.addEventListener('pointerdown', event => {
            drawing = true;
            canvas.setPointerCapture(event.pointerId);
            const current = point(event);
            context.beginPath();
            context.moveTo(current.x, current.y);
        });
        canvas.addEventListener('pointermove', event => {
            if (!drawing) return;
            const current = point(event);
            context.lineWidth = 3;
            context.lineCap = 'round';
            context.strokeStyle = '#303a45';
            context.lineTo(current.x, current.y);
            context.stroke();
        });
        const finish = () => {
            if (!drawing) return;
            drawing = false;
            state.signatureData = canvas.toDataURL('image/png');
            state.removeSignature = false;
            const removeCheckbox = app.querySelector('[name="remove_signature"]');
            if (removeCheckbox) removeCheckbox.checked = false;
            const previousSignature = app.querySelector('.employee-signature-pad img');
            if (previousSignature) previousSignature.hidden = true;
        };
        canvas.addEventListener('pointerup', finish);
        canvas.addEventListener('pointerleave', finish);
        canvas.addEventListener('pointercancel', finish);
    }

    function openForm(mode, employee = null) {
        state.modalMode = mode;
        state.selectedDirectoryUser = null;
        state.selectedRoleId = '';
        state.directoryQuery = '';
        state.directoryResults = [];
        state.directoryError = '';
        state.currentEmployee = employee ? { ...employee } : {
            rol_id: '', cargo: '', nombres: '', apellidos: '', fecha_ingreso: '',
            tipo_documento: 'CC', documento: '', email: '', estadoDA: 0, estado: 1, firma_ruta: null
        };
        state.signatureData = '';
        state.removeSignature = false;
        render();
        if (mode === 'create' && directoryTestMode) searchDirectoryUsers('');
        app.querySelector(mode === 'create' ? '#directorySearch' : '#employeeForm [name="nombres"]')?.focus();
    }

    function closeForm() {
        window.clearTimeout(state.directorySearchTimer);
        state.modalMode = null;
        state.currentEmployee = null;
        state.selectedDirectoryUser = null;
        state.selectedRoleId = '';
        state.directoryQuery = '';
        state.directoryResults = [];
        state.directoryError = '';
        state.signatureData = '';
        state.removeSignature = false;
        state.submitting = false;
        render();
    }

    async function saveEmployee(event) {
        event.preventDefault();
        const permission = state.modalMode === 'edit' ? 'editar' : 'crear';
        if (!hasPermission(permission)) {
            window.alert(`Tu rol no tiene permiso para ${permission} empleados.`);
            return;
        }
        const form = event.currentTarget;
        if (!form.reportValidity()) return;
        const creating = state.modalMode === 'create';
        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());
        payload.estadoDA = creating ? '1' : formData.has('estadoDA') ? (formData.get('estadoDA') ? '1' : '0') : String(state.currentEmployee.estadoDA ?? '0');
        payload.remove_signature = formData.has('remove_signature');
        payload.signature = state.signatureData;
        if (creating) {
            payload.rol_id = state.selectedRoleId;
            payload.directorio_id = state.selectedDirectoryUser.id;
        }
        for (const field of ['rol_id', 'cargo', 'nombres', 'apellidos', 'fecha_ingreso', 'tipo_documento', 'documento', 'email', 'estado', 'estadoDA']) {
            state.currentEmployee[field] = payload[field];
        }
        state.removeSignature = payload.remove_signature;
        state.submitting = true;
        render();
        try {
            const url = creating ? '/api/employees' : `/api/employees/${encodeURIComponent(state.currentEmployee.id)}`;
            await request(url, { method: creating ? 'POST' : 'PUT', body: JSON.stringify(payload) });
            closeForm();
            await loadEmployees(false);
            showFeedback(creating ? 'Empleado creado correctamente.' : 'Empleado actualizado correctamente.');
        } catch (error) {
            state.submitting = false;
            showFeedback(error.message, 'error');
        }
    }

    async function deleteEmployee(employee) {
        if (!employee) return;
        state.submitting = true;
        render();
        try {
            await request(`/api/employees/${encodeURIComponent(employee.id)}`, { method: 'DELETE' });
            state.employeeToDelete = null;
            state.submitting = false;
            state.selectedIds.delete(String(employee.id));
            await loadEmployees(false);
            showFeedback('Empleado eliminado correctamente.');
        } catch (error) {
            state.submitting = false;
            state.employeeToDelete = null;
            showFeedback(error.message, 'error');
        }
    }

    async function deleteSelected() {
        const ids = [...state.selectedIds];
        if (!ids.length || !window.confirm(`¿Eliminar los ${ids.length} empleados seleccionados? Esta acción no se puede deshacer.`)) return;
        const results = await Promise.allSettled(ids.map(id => request(`/api/employees/${encodeURIComponent(id)}`, { method: 'DELETE' })));
        const failed = ids.filter((_, index) => results[index].status === 'rejected');
        state.selectedIds = new Set(failed);
        state.selectionMode = failed.length > 0;
        await loadEmployees(false);
        showFeedback(failed.length ? `Se eliminaron ${ids.length - failed.length}; ${failed.length} no se pudieron eliminar.` : `${ids.length} empleados eliminados correctamente.`, failed.length ? 'error' : 'success');
    }

    async function handleAction(event) {
        const control = event.currentTarget;
        const action = control.dataset.action;
        const id = control.dataset.id;
        const actionPermissions = { create: 'crear', view: 'ver', edit: 'editar', delete: 'eliminar', 'confirm-delete': 'eliminar', 'bulk-delete': 'eliminar', 'selection-mode': 'eliminar', 'select-visible': 'eliminar', 'select-employee': 'eliminar', refresh: 'ver', dashboard: 'ver' };
        const requiredPermission = actionPermissions[action];
        if (requiredPermission && !hasPermission(requiredPermission)) {
            window.alert(`Tu rol no tiene permiso para ${requiredPermission} empleados.`);
            return;
        }
        if (action === 'refresh') {
            await loadEmployees();
        } else if (action === 'select-directory-user') {
            const user = state.directoryResults.find(item => String(item.id) === String(id));
            if (!user) return;
            const nameParts = String(user.display_name || '').trim().split(/\s+/);
            const givenName = user.given_name || nameParts[0] || '';
            const surname = user.surname || nameParts.slice(1).join(' ');
            state.selectedDirectoryUser = user;
            state.selectedRoleId = '';
            state.currentEmployee = {
                rol_id: '', directorio_id: user.id, cargo: user.title || '',
                nombres: givenName,
                apellidos: surname,
                fecha_ingreso: '', tipo_documento: 'CC', documento: '',
                email: user.email || '', estadoDA: 1, estado: 1, firma_ruta: null
            };
            render();
        } else if (action === 'change-directory-user') {
            state.selectedDirectoryUser = null;
            state.selectedRoleId = '';
            state.currentEmployee = null;
            state.directoryQuery = '';
            state.directoryResults = [];
            state.directoryError = '';
            render();
        } else if (action === 'change-role') {
            state.selectedRoleId = '';
            state.currentEmployee.rol_id = '';
            render();
        } else if (action === 'selection-mode') {
            state.selectionMode = !state.selectionMode;
            state.selectedIds.clear();
            render();
        } else if (action === 'select-employee') {
            if (control.checked) state.selectedIds.add(String(id));
            else state.selectedIds.delete(String(id));
            render();
        } else if (action === 'select-visible') {
            visibleEmployees().forEach(employee => control.checked ? state.selectedIds.add(String(employee.id)) : state.selectedIds.delete(String(employee.id)));
            render();
        } else if (action === 'page-prev') {
            state.currentPage = Math.max(1, state.currentPage - 1);
            render();
        } else if (action === 'page-next') {
            state.currentPage = Math.min(Math.ceil(filteredEmployees().length / state.pageSize), state.currentPage + 1);
            render();
        } else if (action === 'dashboard') {
            state.showDashboard = true;
            render();
        } else if (action === 'close-dashboard') {
            state.showDashboard = false;
            render();
        } else if (action === 'create') {
            openForm('create');
        } else if (action === 'view' || action === 'edit') {
            const employee = state.employees.find(item => String(item.id) === String(id));
            if (employee) openForm(action, employee);
        } else if (action === 'close-form') {
            closeForm();
        } else if (action === 'clear-signature') {
            const canvas = app.querySelector('#signatureCanvas');
            if (canvas) canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            state.signatureData = '';
            state.removeSignature = true;
            const previousSignature = app.querySelector('.employee-signature-pad img');
            if (previousSignature) previousSignature.hidden = true;
            const removeCheckbox = app.querySelector('[name="remove_signature"]');
            if (removeCheckbox) removeCheckbox.checked = true;
        } else if (action === 'delete') {
            state.employeeToDelete = state.employees.find(item => String(item.id) === String(id)) || null;
            render();
        } else if (action === 'cancel-delete') {
            state.employeeToDelete = null;
            render();
        } else if (action === 'confirm-delete') {
            await deleteEmployee(state.employeeToDelete);
        } else if (action === 'bulk-delete') {
            await deleteSelected();
        }
    }

    render();
    loadEmployees(false);
});
