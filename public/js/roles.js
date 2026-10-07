document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('roleApp');
    if (!app) return;

    const modules = [
        { id: 'dashboard', name: 'Dashboard', description: 'Visualización de métricas, reportes y estadísticas generales', icon: 'dashboard', actions: [['ver', 'Ver Dashboard'], ['exportar', 'Exportar Reportes']] },
        { id: 'users', name: 'Usuarios', description: 'Administración de cuentas de acceso al sistema', icon: 'users', actions: [['ver', 'Ver'], ['crear', 'Crear'], ['editar', 'Editar'], ['eliminar', 'Eliminar']] },
        { id: 'roles', name: 'Roles y Permisos', description: 'Configuración de perfiles y niveles de autorización', icon: 'shield', actions: [['ver', 'Ver'], ['crear', 'Crear'], ['editar', 'Editar'], ['eliminar', 'Eliminar']] },
        { id: 'employees', name: 'Empleados', description: 'Directorio de personal técnico y administrativo', icon: 'briefcase', actions: [['ver', 'Ver'], ['crear', 'Crear'], ['editar', 'Editar'], ['eliminar', 'Eliminar']] },
        { id: 'categories', name: 'Categorías', description: 'Clasificación de actividades, especialidades y servicios', icon: 'folder', actions: [['ver', 'Ver'], ['crear', 'Crear'], ['editar', 'Editar'], ['eliminar', 'Eliminar']] },
        { id: 'work_orders', name: 'Órdenes de Trabajo', description: 'Seguimiento, ejecución y asignación de órdenes de campo', icon: 'clipboard', actions: [['ver', 'Ver'], ['crear', 'Crear'], ['editar', 'Editar'], ['eliminar', 'Eliminar'], ['asignar', 'Asignar Personal']] },
        { id: 'forms', name: 'Formularios', description: 'Plantillas de inspección técnica y recolección de datos', icon: 'file', actions: [['ver', 'Ver'], ['crear', 'Crear'], ['editar', 'Editar'], ['eliminar', 'Eliminar'], ['responder', 'Diligenciar']] },
        { id: 'contracts', name: 'Contratos', description: 'Gestión contractual y asignaciones vinculadas', icon: 'signature', actions: [['ver', 'Ver'], ['crear', 'Crear'], ['editar', 'Editar'], ['eliminar', 'Eliminar']] },
        { id: 'regions', name: 'Regiones', description: 'Zonas geográficas y sedes operativas de cobertura', icon: 'pin', actions: [['ver', 'Ver'], ['crear', 'Crear'], ['editar', 'Editar'], ['eliminar', 'Eliminar']] }
    ];

    const paths = {
        dashboard: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        users: '<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        shield: '<path d="m12 3 7 3v5c0 4.5-2.8 7.8-7 10-4.2-2.2-7-5.5-7-10V6z"/><path d="m9 12 2 2 4-4"/>',
        briefcase: '<rect x="3" y="8" width="18" height="12" rx="2"/><path d="M8 8V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13h18M10 13v2h4v-2"/>',
        folder: '<path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H10l2 2h6.5A2.5 2.5 0 0 1 21 9.5v8a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5z"/><path d="M3.5 9h17"/>',
        clipboard: '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4.5V3h6v1.5M9 10h6M9 14h6M9 18h3"/>',
        file: '<path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
        signature: '<path d="M7 3h7l5 5v4M14 3v5h5M7 21h5"/><path d="M5 5a2 2 0 0 1 2-2M5 5v14a2 2 0 0 0 2 2"/><path d="m15 19 4.5-4.5a1.8 1.8 0 0 1 2.5 2.5L17.5 22H15z"/>',
        pin: '<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        arrowLeft: '<path d="m12 19-7-7 7-7M5 12h14"/>',
        arrowRight: '<path d="M5 12h14m-7-7 7 7-7 7"/>',
        refresh: '<path d="M20 7v5h-5M4 17v-5h5"/><path d="M5.6 9a7 7 0 0 1 11.6-2L20 12M4 12l2.8 5a7 7 0 0 0 11.6-2"/>',
        checkSquare: '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="m8 12 2.5 2.5L16 9"/>',
        square: '<rect x="3" y="3" width="18" height="18" rx="3"/>',
        trash: '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"/>',
        eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        edit: '<path d="m15 5 4 4M4 20l4-.8L19 8a2.8 2.8 0 0 0-4-4L4 15z"/>',
        plus: '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
        search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        close: '<path d="m6 6 12 12M18 6 6 18"/>',
        check: '<path d="m5 12 4 4L19 6"/>',
        layers: '<path d="m12 3 9 5-9 5-9-5zM3 12l9 5 9-5M3 16l9 5 9-5"/>',
        alert: '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>'
    };

    const state = {
        roles: [],
        loading: true,
        currentView: 'list',
        searchTerm: '',
        currentPage: 1,
        pageSize: 5,
        selectionMode: false,
        selectedIds: new Set(),
        selectedRole: null,
        formData: { name: '', description: '', status: 'ACTIVO', permissions: {} },
        formError: '',
        submitting: false,
        showDashboard: false,
        roleToDelete: null
    };

    function icon(name, size = 18) {
        return `<svg aria-hidden="true" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${paths[name] || ''}</svg>`;
    }

    function escapeHTML(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[character]);
    }

    function rolePermissions(role) {
        return role.permissions && !Array.isArray(role.permissions) && typeof role.permissions === 'object'
            ? role.permissions
            : {};
    }

    function permissionModuleCount(role) {
        return Object.values(rolePermissions(role)).filter(actions => Array.isArray(actions) && actions.length > 0).length;
    }

    function isActive(role) {
        return ['1', 'ACTIVO', 'ACTIVE', 'TRUE'].includes(String(role.status).toUpperCase());
    }

    function showToast(message, type = 'success') {
        let toast = document.getElementById('roleToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'roleToast';
            toast.className = 'role-toast';
            document.body.appendChild(toast);
        }
        toast.className = `role-toast ${type === 'error' ? 'is-error' : ''}`;
        toast.textContent = message;
        toast.classList.add('is-visible');
        window.clearTimeout(showToast.timeout);
        showToast.timeout = window.setTimeout(() => toast.classList.remove('is-visible'), 3200);
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

    function filteredRoles() {
        const term = state.searchTerm.trim().toLowerCase();
        if (!term) return state.roles;
        return state.roles.filter(role =>
            String(role.id).includes(term) ||
            String(role.name || '').toLowerCase().includes(term) ||
            String(role.description || '').toLowerCase().includes(term)
        );
    }

    function currentPageRoles() {
        const start = (state.currentPage - 1) * state.pageSize;
        return filteredRoles().slice(start, start + state.pageSize);
    }

    function roleStats() {
        const active = state.roles.filter(isActive).length;
        const inactive = state.roles.length - active;
        const withPermissions = state.roles.filter(role => permissionModuleCount(role) > 0).length;
        const assignedModules = state.roles.reduce((sum, role) => sum + permissionModuleCount(role), 0);
        return {
            total: state.roles.length,
            active,
            inactive,
            withPermissions,
            assignedModules,
            activePercent: state.roles.length ? Math.round(active / state.roles.length * 100) : 0,
            inactivePercent: state.roles.length ? Math.round(inactive / state.roles.length * 100) : 0
        };
    }

    async function loadRoles(showLoading = true) {
        if (showLoading) {
            state.loading = true;
            renderList();
        }
        try {
            const data = await request('/api/roles');
            state.roles = Array.isArray(data) ? data : [];
            state.selectedIds = new Set([...state.selectedIds].filter(id => state.roles.some(role => String(role.id) === String(id))));
            state.loading = false;
            renderList();
            return true;
        } catch (error) {
            state.loading = false;
            renderList();
            showToast(error.message || 'No se pudieron cargar los roles.', 'error');
            return false;
        }
    }

    function renderDashboard() {
        const stats = roleStats();
        return `<div class="role-overlay" data-overlay="dashboard">
            <section class="role-dialog dashboard-dialog" role="dialog" aria-modal="true" aria-labelledby="role-dashboard-title">
                <header class="dialog-header"><div><h2 id="role-dashboard-title">Dashboard de roles</h2><p>Resumen de roles y asignaciones registradas.</p></div>
                    <button class="icon-action" type="button" data-action="close-dashboard" aria-label="Cerrar dashboard">${icon('close')}</button></header>
                <div class="dashboard-stats">
                    <article><span>Total de roles</span><strong>${stats.total}</strong></article>
                    <article class="is-positive"><span>Activos</span><strong>${stats.active}</strong></article>
                    <article class="is-negative"><span>Inactivos</span><strong>${stats.inactive}</strong></article>
                    <article class="is-orange"><span>Con permisos</span><strong>${stats.withPermissions}</strong><small>${stats.assignedModules} módulos asignados</small></article>
                </div>
                <div class="dashboard-progress-label"><span>Activos: ${stats.activePercent}%</span><span>Inactivos: ${stats.inactivePercent}%</span></div>
                <div class="dashboard-progress" role="img" aria-label="${stats.activePercent}% roles activos y ${stats.inactivePercent}% inactivos"><span style="width:${stats.activePercent}%"></span><span style="width:${stats.inactivePercent}%"></span></div>
            </section>
        </div>`;
    }

    function renderDeleteDialog() {
        if (!state.roleToDelete) return '';
        return `<div class="role-overlay" data-overlay="delete"><section class="role-dialog delete-dialog" role="dialog" aria-modal="true" aria-labelledby="delete-title">
            <div class="delete-icon">${icon('trash', 23)}</div><h2 id="delete-title">¿Eliminar rol?</h2>
            <p>¿Seguro que deseas eliminar permanentemente el rol <strong>“${escapeHTML(state.roleToDelete.name)}”</strong>?</p>
            <div class="dialog-actions"><button class="secondary-btn" type="button" data-action="cancel-delete">Cancelar</button><button class="danger-btn" type="button" data-action="confirm-delete" ${state.submitting ? 'disabled' : ''}>${state.submitting ? 'Eliminando…' : 'Sí, eliminar'}</button></div>
        </section></div>`;
    }

    function renderList() {
        const roles = state.roles;
        const visibleRoles = filteredRoles();
        const pageCount = Math.max(1, Math.ceil(visibleRoles.length / state.pageSize));
        state.currentPage = Math.min(state.currentPage, pageCount);
        const pageRoles = currentPageRoles();
        const visibleIds = pageRoles.map(role => String(role.id));
        const allSelected = visibleIds.length > 0 && visibleIds.every(id => state.selectedIds.has(id));
        const stats = roleStats();
        const firstPageButton = Math.max(1, Math.min(state.currentPage - 2, pageCount - 4));
        const lastPageButton = Math.min(pageCount, firstPageButton + 4);
        const pageButtons = Array.from({ length: lastPageButton - firstPageButton + 1 }, (_, index) => firstPageButton + index)
            .map(page => `<button class="page-button ${page === state.currentPage ? 'is-current' : ''}" type="button" data-action="page-number" data-page="${page}" aria-label="Página ${page}" aria-current="${page === state.currentPage ? 'page' : 'false'}">${page}</button>`)
            .join('');
        const firstItem = visibleRoles.length ? (state.currentPage - 1) * state.pageSize + 1 : 0;
        const lastItem = Math.min(state.currentPage * state.pageSize, visibleRoles.length);
        const tableRows = state.loading
            ? `<tr><td colspan="${state.selectionMode ? 7 : 6}" class="table-message"><span class="loading-spinner"></span><span>Cargando roles...</span></td></tr>`
            : roles.length === 0
                ? `<tr><td colspan="${state.selectionMode ? 7 : 6}" class="table-message">${icon('shield', 32)}<strong>No se encontraron roles</strong><span>Comienza creando tu primer rol.</span><button class="text-action" type="button" data-action="create">Añadir rol</button></td></tr>`
                : visibleRoles.length === 0
                    ? `<tr><td colspan="${state.selectionMode ? 7 : 6}" class="table-message">No hay resultados para “${escapeHTML(state.searchTerm)}”.</td></tr>`
                    : pageRoles.map(role => {
                        const modulesCount = permissionModuleCount(role);
                        const permissions = Object.keys(rolePermissions(role)).filter(key => Array.isArray(rolePermissions(role)[key]) && rolePermissions(role)[key].length);
                        const active = isActive(role);
                        return `<tr data-role-row>
                            ${state.selectionMode ? `<td class="selection-cell"><input type="checkbox" data-action="select-role" data-id="${escapeHTML(role.id)}" aria-label="Seleccionar rol ${escapeHTML(role.name)}" ${state.selectedIds.has(String(role.id)) ? 'checked' : ''}></td>` : ''}
                            <td class="id-cell">${escapeHTML(role.id)}</td>
                            <td><span class="role-name">${escapeHTML(role.name)}</span></td>
                            <td class="description-cell">${escapeHTML(role.description || 'Sin descripción')}</td>
                            <td><div class="module-tags">${modulesCount ? `<span class="module-count">${modulesCount} ${modulesCount === 1 ? 'módulo' : 'módulos'}</span>${permissions.slice(0, 3).map(id => `<span class="module-tag">${escapeHTML(id.replaceAll('_', ' '))}</span>`).join('')}${modulesCount > 3 ? `<span class="more-modules">+${modulesCount - 3} más</span>` : ''}` : '<span class="no-modules">Sin módulos asignados</span>'}</div></td>
                            <td><span class="status-badge ${active ? 'is-active' : 'is-inactive'}"><span></span>${active ? 'Activo' : 'Inactivo'}</span></td>
                            <td><div class="row-actions"><button class="icon-action" type="button" data-action="view" data-id="${escapeHTML(role.id)}" aria-label="Ver rol" title="Ver permisos">${icon('eye')}</button><button class="icon-action" type="button" data-action="edit" data-id="${escapeHTML(role.id)}" aria-label="Editar rol" title="Editar">${icon('edit')}</button><button class="icon-action is-danger" type="button" data-action="delete" data-id="${escapeHTML(role.id)}" aria-label="Eliminar rol" title="Eliminar">${icon('trash')}</button></div></td>
                        </tr>`;
                    }).join('');

        app.innerHTML = `<section class="roles-screen">
            <header class="roles-toolbar">
                <div class="toolbar-context"><span class="toolbar-mark">${icon('shield', 20)}</span><div><span class="toolbar-app">APPSIG</span><div class="toolbar-breadcrumb"><strong>Administración</strong><span>/</span><strong>Roles del Sistema</strong></div></div></div>
                <div class="toolbar-actions">
                    <button class="icon-action" type="button" data-action="refresh" aria-label="Recargar roles" title="Actualizar lista" ${state.loading ? 'disabled' : ''}>${icon('refresh')}</button>
                    <button class="icon-action ${state.selectionMode ? 'is-selected' : ''}" type="button" data-action="selection-mode" aria-label="${state.selectionMode ? 'Cancelar selección' : 'Seleccionar roles'}" title="${state.selectionMode ? 'Cancelar selección' : 'Modo selección'}">${icon(state.selectionMode ? 'square' : 'checkSquare')}</button>
                    ${state.selectedIds.size ? `<button class="icon-action is-danger" type="button" data-action="bulk-delete" aria-label="Eliminar ${state.selectedIds.size} roles seleccionados" title="Eliminar seleccionados (${state.selectedIds.size})">${icon('trash')}<span>${state.selectedIds.size}</span></button>` : ''}
                    <button class="icon-action" type="button" data-action="dashboard" aria-label="Abrir dashboard de roles" title="Dashboard de roles">${icon('dashboard')}</button>
                    <button class="primary-btn add-role-btn" type="button" data-action="create">${icon('plus', 18)}<span>Añadir rol</span></button>
                </div>
            </header>

            <section class="role-table-panel">
                <div class="table-panel-header"><div><h1>Listado de roles</h1><p>Consulta y administra los perfiles de acceso al sistema.</p></div>
                    <label class="role-search">${icon('search', 18)}<input type="search" id="roleSearch" placeholder="Buscar por rol o descripción..." value="${escapeHTML(state.searchTerm)}" aria-label="Buscar rol"></label></div>
                <div class="role-table-scroll"><table class="role-table enhanced-role-table"><thead><tr>
                    ${state.selectionMode ? `<th class="selection-cell"><input type="checkbox" data-action="select-visible" aria-label="Seleccionar todos los roles de esta página" ${allSelected ? 'checked' : ''}></th>` : ''}
                    <th>ID</th><th>Rol</th><th>Descripción</th><th>Módulos asignados</th><th>Estado</th><th>Acciones</th>
                </tr></thead><tbody>${tableRows}</tbody></table></div>
                <footer class="table-footer">
                    <div class="table-footer-summary"><span data-role-count>${visibleRoles.length} ${visibleRoles.length === 1 ? 'rol' : 'roles'} ${state.searchTerm ? 'encontrados' : 'registrados'}</span><span>${stats.active} activos · ${stats.inactive} inactivos</span></div>
                    <div class="pagination-controls">
                        <label class="page-size-control">Filas por página<select id="rolePageSize" aria-label="Filas por página">${[5, 10, 20, 50].map(size => `<option value="${size}" ${state.pageSize === size ? 'selected' : ''}>${size}</option>`).join('')}</select></label>
                        <span class="page-range">${firstItem}–${lastItem} de ${visibleRoles.length}</span>
                        <nav class="page-buttons" aria-label="Paginación">
                            <button class="page-button" type="button" data-action="page-prev" aria-label="Página anterior" ${state.currentPage === 1 ? 'disabled' : ''}>${icon('arrowLeft', 15)}</button>
                            ${pageButtons}
                            <button class="page-button" type="button" data-action="page-next" aria-label="Página siguiente" ${state.currentPage === pageCount ? 'disabled' : ''}>${icon('arrowRight', 15)}</button>
                        </nav>
                    </div>
                </footer>
            </section>
            ${state.showDashboard ? renderDashboard() : ''}${renderDeleteDialog()}
        </section>`;

        app.querySelector('#roleSearch')?.addEventListener('input', event => {
            const cursorPosition = event.target.selectionStart ?? event.target.value.length;
            state.searchTerm = event.target.value;
            state.currentPage = 1;
            renderList();
            const searchInput = app.querySelector('#roleSearch');
            searchInput?.focus();
            searchInput?.setSelectionRange(cursorPosition, cursorPosition);
        });

        app.querySelector('#rolePageSize')?.addEventListener('change', event => {
            state.pageSize = Number(event.target.value);
            state.currentPage = 1;
            renderList();
        });

        app.querySelectorAll('button[data-action], input[type="checkbox"][data-action]').forEach(element => {
            element.addEventListener(element.type === 'checkbox' ? 'change' : 'click', handleListAction);
        });
        app.querySelector('[data-overlay="dashboard"]')?.addEventListener('click', event => {
            if (event.target === event.currentTarget) { state.showDashboard = false; renderList(); }
        });
        app.querySelector('[data-overlay="delete"]')?.addEventListener('click', event => {
            if (event.target === event.currentTarget) { state.roleToDelete = null; renderList(); }
        });
    }

    async function handleListAction(event) {
        const control = event.currentTarget;
        const action = control.dataset.action;
        const id = control.dataset.id;

        if (action === 'refresh') {
            if (await loadRoles()) showToast('Lista de roles actualizada.');
        } else if (action === 'selection-mode') {
            state.selectionMode = !state.selectionMode;
            state.selectedIds.clear();
            renderList();
        } else if (action === 'select-role') {
            if (control.checked) state.selectedIds.add(String(id));
            else state.selectedIds.delete(String(id));
            renderList();
        } else if (action === 'select-visible') {
            if (control.checked) currentPageRoles().forEach(role => state.selectedIds.add(String(role.id)));
            else currentPageRoles().forEach(role => state.selectedIds.delete(String(role.id)));
            renderList();
        } else if (action === 'page-prev') {
            state.currentPage = Math.max(1, state.currentPage - 1);
            renderList();
        } else if (action === 'page-next') {
            state.currentPage = Math.min(Math.ceil(filteredRoles().length / state.pageSize), state.currentPage + 1);
            renderList();
        } else if (action === 'page-number') {
            state.currentPage = Number(control.dataset.page);
            renderList();
        } else if (action === 'bulk-delete') {
            await deleteSelectedRoles();
        } else if (action === 'dashboard') {
            state.showDashboard = true;
            renderList();
        } else if (action === 'close-dashboard') {
            state.showDashboard = false;
            renderList();
        } else if (action === 'create') {
            openForm('create');
        } else if (action === 'view' || action === 'edit') {
            const role = state.roles.find(item => String(item.id) === String(id));
            if (role) openForm(action, role);
        } else if (action === 'delete') {
            state.roleToDelete = state.roles.find(item => String(item.id) === String(id)) || null;
            renderList();
        } else if (action === 'cancel-delete') {
            state.roleToDelete = null;
            renderList();
        } else if (action === 'confirm-delete') {
            await deleteRole(state.roleToDelete);
        }
    }

    async function deleteRole(role) {
        if (!role) return;
        state.submitting = true;
        renderList();
        try {
            await request(`/api/roles/${encodeURIComponent(role.id)}`, { method: 'DELETE' });
            state.submitting = false;
            state.selectedIds.delete(String(role.id));
            state.roleToDelete = null;
            showToast(`Rol “${role.name}” eliminado correctamente.`);
            await loadRoles(false);
        } catch (error) {
            state.submitting = false;
            renderList();
            showToast(error.message, 'error');
        }
    }

    async function deleteSelectedRoles() {
        const ids = [...state.selectedIds];
        if (!ids.length || !window.confirm(`¿Eliminar los ${ids.length} roles seleccionados? Esta acción no se puede deshacer.`)) return;
        state.submitting = true;
        renderList();
        const results = await Promise.allSettled(ids.map(id => request(`/api/roles/${encodeURIComponent(id)}`, { method: 'DELETE' })));
        const failed = ids.filter((_, index) => results[index].status === 'rejected');
        state.selectedIds = new Set(failed);
        state.submitting = false;
        state.selectionMode = failed.length > 0;
        await loadRoles(false);
        const deleted = ids.length - failed.length;
        showToast(failed.length ? `Se eliminaron ${deleted}; ${failed.length} no se pudieron eliminar.` : `${deleted} roles eliminados correctamente.`, failed.length ? 'error' : 'success');
    }

    function openForm(view, role = null) {
        state.currentView = view;
        state.selectedRole = role;
        state.formError = '';
        state.formData = role ? {
            name: role.name || '',
            description: role.description || '',
            status: isActive(role) ? 'ACTIVO' : 'INACTIVO',
            permissions: JSON.parse(JSON.stringify(rolePermissions(role)))
        } : { name: '', description: '', status: 'ACTIVO', permissions: {} };
        renderForm();
    }

    function toggleAction(moduleId, actionId) {
        const actions = [...(state.formData.permissions[moduleId] || [])];
        const next = actions.includes(actionId) ? actions.filter(id => id !== actionId) : [...actions, actionId];
        const permissions = { ...state.formData.permissions };
        if (next.length) permissions[moduleId] = next;
        else delete permissions[moduleId];
        state.formData.permissions = permissions;
        renderForm();
    }

    function toggleModule(module) {
        const current = state.formData.permissions[module.id] || [];
        const all = module.actions.map(action => action[0]);
        const permissions = { ...state.formData.permissions };
        if (all.every(action => current.includes(action))) delete permissions[module.id];
        else permissions[module.id] = all;
        state.formData.permissions = permissions;
        renderForm();
    }

    function toggleAllPermissions(selectAll) {
        if (!selectAll) {
            state.formData.permissions = {};
        } else {
            state.formData.permissions = Object.fromEntries(modules.map(module => [module.id, module.actions.map(action => action[0])]));
        }
        renderForm();
    }

    function renderForm() {
        const viewing = state.currentView === 'view';
        const creating = state.currentView === 'create';
        const data = state.formData;
        const assignedModules = Object.keys(data.permissions).filter(id => data.permissions[id]?.length).length;
        const assignedActions = Object.values(data.permissions).reduce((sum, actions) => sum + (Array.isArray(actions) ? actions.length : 0), 0);
        const title = viewing ? `Permisos de: ${data.name}` : creating ? 'Crear nuevo rol' : `Editar rol: ${state.selectedRole?.name || ''}`;

        app.innerHTML = `<section class="role-form-page">
            <header class="form-toolbar"><div class="form-title-group"><button class="back-btn" type="button" data-action="back" aria-label="Volver al listado">${icon('arrowLeft')}</button><div><span class="toolbar-app">${viewing ? 'DETALLE DEL ROL' : creating ? 'NUEVO ROL' : 'ACTUALIZAR ROL'}</span><h1>${escapeHTML(title)}</h1></div></div>
                <div class="form-toolbar-actions"><button type="button" class="secondary-btn" data-action="back">${viewing ? 'Cerrar vista' : 'Cancelar'}</button>${!viewing ? `<button type="submit" form="roleEditor" class="primary-btn" ${state.submitting ? 'disabled' : ''}>${state.submitting ? 'Guardando…' : creating ? 'Crear rol' : 'Guardar cambios'}</button>` : ''}</div></header>
            ${state.formError ? `<div class="form-error">${icon('alert')}<span>${escapeHTML(state.formError)}</span></div>` : ''}
            <form id="roleEditor" class="role-form-layout">
                <div class="role-form-sidebar"><section class="role-card role-info-card"><header class="role-card-heading"><div class="module-symbol">${icon('shield')}</div><div><h2>Información del Rol</h2><p>Datos identificadores del rol en la plataforma.</p></div></header>
                    <label class="role-field"><span>Nombre del Rol <b>*</b></span><input name="name" type="text" required maxlength="100" value="${escapeHTML(data.name)}" placeholder="Ej. Supervisor de Operaciones" ${viewing ? 'disabled' : ''}></label>
                    ${!creating ? `<div class="role-field"><span>Estado del Rol <b>*</b></span>${viewing ? `<span class="status-badge ${data.status === 'ACTIVO' ? 'is-active' : 'is-inactive'}"><span></span>${data.status}</span>` : `<div class="status-toggle"><button type="button" data-status="ACTIVO" class="${data.status === 'ACTIVO' ? 'is-current' : ''}"><span></span>ACTIVO</button><button type="button" data-status="INACTIVO" class="${data.status === 'INACTIVO' ? 'is-current is-off' : ''}"><span></span>INACTIVO</button></div>`}<small>${data.status === 'ACTIVO' ? 'Los usuarios con este rol podrán iniciar sesión y ejecutar acciones.' : 'Los usuarios con este rol tendrán su acceso temporalmente suspendido.'}</small></div>` : ''}
                    <label class="role-field"><span>Descripción</span><textarea name="description" rows="4" maxlength="255" placeholder="Describe el alcance y responsabilidades asignadas a este rol..." ${viewing ? 'disabled' : ''}>${escapeHTML(data.description)}</textarea></label>
                </section>
                <section class="assignment-summary"><h2>${icon('layers', 17)}Resumen de Asignación</h2><div><article><span>Módulos Activos</span><strong>${assignedModules}<small> / ${modules.length}</small></strong></article><article><span>Total Acciones</span><strong>${assignedActions}</strong></article></div></section></div>
                <div class="permissions-column"><header class="permissions-toolbar"><div><h2>Permisos por Módulo</h2><p>Selecciona las acciones autorizadas para este rol en cada módulo.</p></div>${!viewing ? `<div class="permission-bulk-actions"><button type="button" class="secondary-btn" data-action="select-all">${icon('checkSquare', 15)}Seleccionar todo</button><button type="button" class="secondary-btn subtle-danger" data-action="clear-all">${icon('square', 15)}Desmarcar todo</button></div>` : ''}</header>
                    <div class="permission-modules">${modules.map(module => {
                        const current = data.permissions[module.id] || [];
                        const all = module.actions.every(action => current.includes(action[0]));
                        return `<section class="permission-module ${current.length ? 'has-permissions' : ''}"><header><div class="permission-module-title"><span class="module-symbol ${current.length ? 'is-active' : ''}">${icon(module.icon, 18)}</span><div><h3>${escapeHTML(module.name)}</h3><p>${escapeHTML(module.description)}</p></div></div>${!viewing ? `<button type="button" class="module-toggle ${all ? 'is-on' : ''}" data-action="toggle-module" data-module="${module.id}">${all ? 'Todo el módulo activo' : 'Activar todo'}</button>` : ''}</header>
                            <div class="permission-options">${module.actions.map(([id, label]) => { const checked = current.includes(id); return `<label class="permission-option ${checked ? 'is-checked' : ''} ${viewing ? 'is-readonly' : ''}"><input type="checkbox" data-module="${module.id}" data-permission="${id}" ${checked ? 'checked' : ''} ${viewing ? 'disabled' : ''}><span>${escapeHTML(label)}</span></label>`; }).join('')}</div>
                        </section>`;
                    }).join('')}</div>
                </div>
            </form>
        </section>`;

        app.querySelector('[name="name"]')?.addEventListener('input', event => { state.formData.name = event.target.value; });
        app.querySelector('[name="description"]')?.addEventListener('input', event => { state.formData.description = event.target.value; });
        app.querySelectorAll('[data-permission]').forEach(input => input.addEventListener('change', () => toggleAction(input.dataset.module, input.dataset.permission)));
        app.querySelectorAll('[data-status]').forEach(button => button.addEventListener('click', () => { state.formData.status = button.dataset.status; renderForm(); }));
        app.querySelectorAll('[data-action="toggle-module"]').forEach(button => button.addEventListener('click', () => toggleModule(modules.find(module => module.id === button.dataset.module))));
        app.querySelector('[data-action="select-all"]')?.addEventListener('click', () => toggleAllPermissions(true));
        app.querySelector('[data-action="clear-all"]')?.addEventListener('click', () => toggleAllPermissions(false));
        app.querySelectorAll('[data-action="back"]').forEach(button => button.addEventListener('click', () => {
            state.currentView = 'list'; state.selectedRole = null; state.formError = ''; renderList();
        }));
        app.querySelector('#roleEditor')?.addEventListener('submit', saveRole);
    }

    async function saveRole(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const name = form.elements.name.value.trim();
        const description = form.elements.description.value.trim();
        if (!name) {
            state.formError = 'El nombre del rol es obligatorio.';
            renderForm();
            app.querySelector('[name="name"]')?.focus();
            return;
        }

        state.formData.name = name;
        state.formData.description = description;
        state.submitting = true;
        state.formError = '';
        renderForm();
        const payload = {
            name,
            description,
            status: creatingStatus(),
            permissions: state.formData.permissions
        };
        try {
            if (state.currentView === 'create') await request('/api/roles', { method: 'POST', body: JSON.stringify(payload) });
            else await request(`/api/roles/${encodeURIComponent(state.selectedRole.id)}`, { method: 'PUT', body: JSON.stringify(payload) });
            const wasCreate = state.currentView === 'create';
            state.submitting = false;
            state.currentView = 'list';
            state.selectedRole = null;
            await loadRoles(false);
            showToast(wasCreate ? '¡Rol creado exitosamente!' : '¡Rol actualizado exitosamente!');
        } catch (error) {
            state.submitting = false;
            state.formError = error.message;
            renderForm();
            showToast(error.message, 'error');
        }
    }

    function creatingStatus() {
        return state.currentView === 'create' ? 'ACTIVO' : state.formData.status;
    }

    renderList();
    loadRoles(false);
});