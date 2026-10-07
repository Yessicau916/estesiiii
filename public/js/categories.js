document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('categoryApp');
    if (!app) return;

    const iconPaths = {
        folder: '<path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H10l2 2h6.5A2.5 2.5 0 0 1 21 9.5v8a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5z"/><path d="M3.5 9h17"/>',
        refresh: '<path d="M20 7v5h-5M4 17v-5h5"/><path d="M5.6 9a7 7 0 0 1 11.6-2L20 12M4 12l2.8 5a7 7 0 0 0 11.6-2"/>',
        selection: '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 12h8M12 8v8"/>',
        trash: '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"/>',
        dashboard: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        plus: '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
        search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
        edit: '<path d="m15 5 4 4M4 20l4-.8L19 8a2.8 2.8 0 0 0-4-4L4 15z"/>',
        close: '<path d="m6 6 12 12M18 6 6 18"/>',
        alert: '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>',
        check: '<path d="m5 12 4 4L19 6"/>',
        arrowLeft: '<path d="m12 19-7-7 7-7M5 12h14"/>',
        arrowRight: '<path d="M5 12h14m-7-7 7 7-7 7"/>'
    };

    const state = {
        categories: [],
        loading: true,
        submitting: false,
        error: '',
        feedback: null,
        searchTerm: '',
        selectedIds: new Set(),
        selectionMode: false,
        showModal: false,
        modalMode: 'create',
        currentCategory: null,
        showDashboard: false,
        categoryToDelete: null,
        formData: { name: '', status: 'ACTIVO', description: '' },
        formErrors: {},
        currentPage: 1,
        pageSize: 10
    };

    function icon(name, size = 18) {
        return `<svg aria-hidden="true" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${iconPaths[name] || ''}</svg>`;
    }

    function escapeHTML(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[character]);
    }

    function isActive(category) {
        return ['1', 'ACTIVO', 'ACTIVE', 'TRUE'].includes(String(category.status).toUpperCase());
    }

    function filteredCategories() {
        const term = state.searchTerm.trim().toLowerCase();
        if (!term) return state.categories;
        return state.categories.filter(category =>
            String(category.id).includes(term) ||
            String(category.name || '').toLowerCase().includes(term) ||
            String(category.description || '').toLowerCase().includes(term)
        );
    }

    function paginatedCategories() {
        const start = (state.currentPage - 1) * state.pageSize;
        return filteredCategories().slice(start, start + state.pageSize);
    }

    function categoryStats() {
        const active = state.categories.filter(isActive).length;
        const total = state.categories.length;
        const inactive = total - active;
        return {
            total,
            active,
            inactive,
            activePercent: total ? Math.round(active / total * 100) : 0,
            inactivePercent: total ? Math.round(inactive / total * 100) : 0
        };
    }

    function showFeedbackMessage(message, type = 'success') {
        state.feedback = { message, type };
        renderList();
        window.clearTimeout(showFeedbackMessage.timeout);
        showFeedbackMessage.timeout = window.setTimeout(() => {
            state.feedback = null;
            renderList();
        }, 4000);
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
        if (!response.ok) {
            const error = new Error(payload.message || 'No se pudo completar la solicitud.');
            error.data = payload;
            throw error;
        }
        return payload;
    }

    async function loadCategories(showLoading = true) {
        if (showLoading) {
            state.loading = true;
            state.error = '';
            renderList();
        }
        try {
            const data = await request('/api/categories');
            state.categories = Array.isArray(data) ? data : [];
            const knownIds = new Set(state.categories.map(category => String(category.id)));
            state.selectedIds = new Set([...state.selectedIds].filter(id => knownIds.has(id)));
            state.loading = false;
            state.error = '';
            renderList();
            return true;
        } catch (error) {
            state.loading = false;
            state.error = error.message || 'No se pudieron cargar las categorías.';
            renderList();
            return false;
        }
    }

    function renderDashboard() {
        const stats = categoryStats();
        return `<div class="category-overlay" data-overlay="dashboard"><section class="category-dialog category-dashboard" role="dialog" aria-modal="true" aria-labelledby="category-dashboard-title">
            <header class="category-dialog-header"><div><h2 id="category-dashboard-title">Dashboard de categorías</h2><p>Resumen actualizado desde la base de datos.</p></div><button class="category-icon-button" type="button" data-action="close-dashboard" aria-label="Cerrar dashboard">${icon('close')}</button></header>
            <div class="category-dashboard-stats"><article><span>Total categorías</span><strong>${stats.total}</strong><small>100% del registro</small></article><article class="is-active"><span>Activas</span><strong>${stats.active}</strong><small>${stats.activePercent}% del total</small></article><article class="is-inactive"><span>Inactivas</span><strong>${stats.inactive}</strong><small>${stats.inactivePercent}% del total</small></article></div>
            <div class="category-distribution"><div><span>Activas (${stats.active})</span><span>Inactivas (${stats.inactive})</span></div><div class="category-progress"><span style="width:${stats.activePercent}%"></span><span style="width:${stats.inactivePercent}%"></span></div></div>
        </section></div>`;
    }

    function renderDeleteDialog() {
        if (!state.categoryToDelete) return '';
        return `<div class="category-overlay" data-overlay="delete"><section class="category-dialog category-delete-dialog" role="dialog" aria-modal="true" aria-labelledby="category-delete-title">
            <div class="category-delete-mark">${icon('trash', 22)}</div><h2 id="category-delete-title">¿Eliminar categoría?</h2>
            <p>¿Estás segura de que deseas eliminar permanentemente la categoría <strong>“${escapeHTML(state.categoryToDelete.name)}”</strong>? Esta acción no se puede deshacer.</p>
            <div class="category-dialog-actions"><button class="category-secondary-btn" type="button" data-action="cancel-delete">Cancelar</button><button class="category-danger-btn" type="button" data-action="confirm-delete" ${state.submitting ? 'disabled' : ''}>${state.submitting ? 'Eliminando…' : 'Sí, eliminar'}</button></div>
        </section></div>`;
    }

    function renderCategoryModal() {
        if (!state.showModal) return '';
        const isViewing = state.modalMode === 'view';
        const heading = isViewing ? 'Ver categoría' : state.modalMode === 'edit' ? 'Editar categoría' : 'Crear categoría';
        return `<div class="category-overlay" data-overlay="category-modal"><section class="category-dialog category-form-dialog" role="dialog" aria-modal="true" aria-labelledby="category-modal-title">
            <header class="category-dialog-header"><div><h2 id="category-modal-title">${heading}</h2><p>${isViewing ? 'Consulta los datos guardados de esta categoría.' : state.modalMode === 'edit' ? 'Actualiza los datos de la categoría.' : 'Completa la información de la nueva categoría.'}</p></div><button class="category-icon-button" type="button" data-action="close-modal" aria-label="Cerrar ventana">${icon('close')}</button></header>
            ${state.formErrors.general ? `<div class="category-form-error">${icon('alert')}<span>${escapeHTML(state.formErrors.general)}</span></div>` : ''}
            <form id="categoryForm" novalidate>
                ${isViewing ? `<div class="category-view-summary"><div><span>Nombre</span><strong>${escapeHTML(state.formData.name)}</strong></div><div><span>Estado</span><span class="category-status ${state.formData.status === 'ACTIVO' ? 'is-active' : 'is-inactive'}">${escapeHTML(state.formData.status)}</span></div></div>` : ''}
                ${!isViewing ? `<label class="category-field"><span>Nombre <b>*</b></span><input name="name" type="text" required minlength="2" maxlength="100" value="${escapeHTML(state.formData.name)}" placeholder="Ej. Redes e Infraestructura"><small class="field-error">${escapeHTML(state.formErrors.name || '')}</small></label>
                <label class="category-field"><span>Estado <b>*</b></span><select name="status"><option value="ACTIVO" ${state.formData.status === 'ACTIVO' ? 'selected' : ''}>ACTIVO</option><option value="INACTIVO" ${state.formData.status === 'INACTIVO' ? 'selected' : ''}>INACTIVO</option></select></label>` : ''}
                <label class="category-field"><span>Descripción</span><textarea name="description" rows="4" maxlength="255" placeholder="Detalles y alcance de la categoría..." ${isViewing ? 'disabled' : ''}>${escapeHTML(state.formData.description)}</textarea><small class="field-error">${escapeHTML(state.formErrors.description || '')}</small></label>
                ${isViewing && state.currentCategory?.created_at ? `<p class="category-created-at">Registrada: ${escapeHTML(state.currentCategory.created_at)}</p>` : ''}
                <footer class="category-form-actions"><button class="category-secondary-btn" type="button" data-action="close-modal">${isViewing ? 'Cerrar' : 'Cancelar'}</button>${!isViewing ? `<button class="category-primary-btn" type="submit" ${state.submitting ? 'disabled' : ''}>${state.submitting ? 'Guardando…' : state.modalMode === 'edit' ? 'Guardar cambios' : 'Crear categoría'}</button>` : ''}</footer>
            </form>
        </section></div>`;
    }

    function renderList() {
        const filtered = filteredCategories();
        const totalPages = Math.max(1, Math.ceil(filtered.length / state.pageSize));
        state.currentPage = Math.min(state.currentPage, totalPages);
        const pageItems = paginatedCategories();
        const filteredIds = filtered.map(category => String(category.id));
        const allFilteredSelected = filteredIds.length > 0 && filteredIds.every(id => state.selectedIds.has(id));
        const stats = categoryStats();
        const start = filtered.length ? (state.currentPage - 1) * state.pageSize + 1 : 0;
        const end = Math.min(state.currentPage * state.pageSize, filtered.length);
        const tableBody = state.loading
            ? `<tr><td colspan="${state.selectionMode ? 6 : 5}" class="category-table-message"><span class="category-spinner"></span><span>Cargando categorías…</span></td></tr>`
            : state.error
                ? `<tr><td colspan="${state.selectionMode ? 6 : 5}" class="category-table-message is-error"><span>${escapeHTML(state.error)}</span><button class="category-link-button" type="button" data-action="retry">Reintentar</button></td></tr>`
                : pageItems.length === 0
                    ? `<tr><td colspan="${state.selectionMode ? 6 : 5}" class="category-table-message">${icon('folder', 32)}<strong>No se encontraron categorías</strong><span>${state.searchTerm ? 'Intenta con otro término de búsqueda.' : 'Comienza creando tu primera categoría.'}</span>${!state.searchTerm ? '<button class="category-link-button" type="button" data-action="create">Añadir categoría</button>' : ''}</td></tr>`
                    : pageItems.map(category => {
                        const active = isActive(category);
                        return `<tr data-category-row>
                            ${state.selectionMode ? `<td class="category-selection-cell"><input type="checkbox" data-action="select-category" data-id="${escapeHTML(category.id)}" aria-label="Seleccionar categoría ${escapeHTML(category.name)}" ${state.selectedIds.has(String(category.id)) ? 'checked' : ''}></td>` : ''}
                            <td class="category-id-cell">${escapeHTML(category.id)}</td><td><strong class="category-name">${escapeHTML(category.name)}</strong></td>
                            <td class="category-description">${escapeHTML(category.description || 'Sin descripción')}</td>
                            <td><span class="category-status ${active ? 'is-active' : 'is-inactive'}"><span></span>${active ? 'Activo' : 'Inactivo'}</span></td>
                            <td><div class="category-row-actions"><button class="category-icon-button" type="button" data-action="view" data-id="${escapeHTML(category.id)}" aria-label="Ver categoría" title="Ver detalles">${icon('eye')}</button><button class="category-icon-button" type="button" data-action="edit" data-id="${escapeHTML(category.id)}" aria-label="Editar categoría" title="Editar">${icon('edit')}</button><button class="category-icon-button is-danger" type="button" data-action="delete" data-id="${escapeHTML(category.id)}" aria-label="Eliminar categoría" title="Eliminar">${icon('trash')}</button></div></td>
                        </tr>`;
                    }).join('');
        const feedback = state.feedback ? `<div class="category-feedback ${state.feedback.type === 'error' ? 'is-error' : ''}" role="status">${icon(state.feedback.type === 'error' ? 'alert' : 'check')}<span>${escapeHTML(state.feedback.message)}</span></div>` : '';

        app.innerHTML = `<section class="categories-screen">
            ${feedback}
            <header class="categories-toolbar"><div class="categories-context"><span class="categories-mark">${icon('folder', 19)}</span><div><span class="categories-app-name">APPSIG</span><div class="categories-breadcrumb"><strong>Categorías</strong><span>/</span><strong>Enecon</strong></div></div></div>
                <div class="categories-actions"><button class="category-icon-button" type="button" data-action="refresh" aria-label="Recargar datos" title="Actualizar lista" ${state.loading ? 'disabled' : ''}>${icon('refresh')}</button><button class="category-icon-button ${state.selectionMode ? 'is-selected' : ''}" type="button" data-action="selection-mode" aria-label="${state.selectionMode ? 'Cancelar selección' : 'Seleccionar categorías'}" title="${state.selectionMode ? 'Cancelar selección' : 'Modo selección'}">${icon(state.selectionMode ? 'check' : 'selection')}</button>${state.selectedIds.size ? `<button class="category-icon-button is-danger" type="button" data-action="bulk-delete" aria-label="Eliminar seleccionados" title="Eliminar seleccionados (${state.selectedIds.size})">${icon('trash')}<span>${state.selectedIds.size}</span></button>` : ''}<button class="category-icon-button" type="button" data-action="dashboard" aria-label="Abrir dashboard" title="Dashboard de categorías">${icon('dashboard')}</button><button class="category-primary-btn category-add-button" type="button" data-action="create">${icon('plus')}<span>Añadir categoría</span></button></div>
            </header>

            <section class="category-table-panel"><header class="category-table-heading"><div><h1>Listado de categorías</h1><p>Administra nombres, descripciones y estados.</p></div><label class="category-search">${icon('search')}<input id="categorySearch" type="search" value="${escapeHTML(state.searchTerm)}" placeholder="Buscar por nombre, ID o descripción…" aria-label="Buscar categoría"></label></header>
                <div class="category-table-scroll"><table class="category-table"><thead><tr>${state.selectionMode ? `<th class="category-selection-cell"><input type="checkbox" data-action="select-all" aria-label="Seleccionar todas las categorías filtradas" ${allFilteredSelected ? 'checked' : ''}></th>` : ''}<th>ID</th><th>Nombre</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>${tableBody}</tbody></table></div>
                <footer class="category-pagination"><div class="category-pagination-summary"><span>${filtered.length} ${filtered.length === 1 ? 'registro' : 'registros'}${state.searchTerm ? ' encontrados' : ''}</span><span>${stats.active} activas · ${stats.inactive} inactivas</span></div><div class="category-pagination-controls"><label>Filas por página<select id="categoryPageSize" aria-label="Filas por página">${[5, 10, 20, 50].map(size => `<option value="${size}" ${state.pageSize === size ? 'selected' : ''}>${size}</option>`).join('')}</select></label><span class="category-page-range">${start}–${end} de ${filtered.length}</span><span class="category-page-counter">Página ${state.currentPage} de ${totalPages}</span><div class="category-page-buttons"><button class="category-page-button" type="button" data-action="page-prev" aria-label="Página anterior" ${state.currentPage <= 1 ? 'disabled' : ''}>${icon('arrowLeft', 15)}</button><button class="category-page-button" type="button" data-action="page-next" aria-label="Página siguiente" ${state.currentPage >= totalPages ? 'disabled' : ''}>${icon('arrowRight', 15)}</button></div></div></footer>
            </section>
            ${state.showDashboard ? renderDashboard() : ''}${renderCategoryModal()}${renderDeleteDialog()}
        </section>`;

        app.querySelector('#categorySearch')?.addEventListener('input', event => {
            const cursorPosition = event.target.selectionStart ?? event.target.value.length;
            state.searchTerm = event.target.value;
            state.currentPage = 1;
            renderList();
            const input = app.querySelector('#categorySearch');
            input?.focus();
            input?.setSelectionRange(cursorPosition, cursorPosition);
        });
        app.querySelector('#categoryPageSize')?.addEventListener('change', event => {
            state.pageSize = Number(event.target.value);
            state.currentPage = 1;
            renderList();
        });
        app.querySelectorAll('button[data-action], input[type="checkbox"][data-action]').forEach(control => {
            control.addEventListener(control.type === 'checkbox' ? 'change' : 'click', handleListAction);
        });
        app.querySelector('[data-overlay="dashboard"]')?.addEventListener('click', event => {
            if (event.target === event.currentTarget) { state.showDashboard = false; renderList(); }
        });
        app.querySelector('[data-overlay="delete"]')?.addEventListener('click', event => {
            if (event.target === event.currentTarget) { state.categoryToDelete = null; renderList(); }
        });
        app.querySelector('[data-overlay="category-modal"]')?.addEventListener('click', event => {
            if (event.target === event.currentTarget) closeModal();
        });
        bindModalActions();
    }

    function handleListAction(event) {
        const control = event.currentTarget;
        const action = control.dataset.action;
        const id = control.dataset.id;
        if (action === 'refresh' || action === 'retry') {
            loadCategories();
        } else if (action === 'selection-mode') {
            state.selectionMode = !state.selectionMode;
            state.selectedIds.clear();
            renderList();
        } else if (action === 'select-category') {
            if (control.checked) state.selectedIds.add(String(id));
            else state.selectedIds.delete(String(id));
            renderList();
        } else if (action === 'select-all') {
            if (control.checked) filteredCategories().forEach(category => state.selectedIds.add(String(category.id)));
            else filteredCategories().forEach(category => state.selectedIds.delete(String(category.id)));
            renderList();
        } else if (action === 'bulk-delete') {
            deleteSelected();
        } else if (action === 'dashboard') {
            state.showDashboard = true;
            renderList();
        } else if (action === 'close-dashboard') {
            state.showDashboard = false;
            renderList();
        } else if (action === 'create') {
            openModal('create');
        } else if (action === 'view' || action === 'edit') {
            const category = state.categories.find(item => String(item.id) === String(id));
            if (category) openModal(action, category);
        } else if (action === 'delete') {
            state.categoryToDelete = state.categories.find(item => String(item.id) === String(id)) || null;
            renderList();
        } else if (action === 'confirm-delete') {
            deleteOne();
        } else if (action === 'cancel-delete') {
            state.categoryToDelete = null;
            renderList();
        } else if (action === 'close-modal') {
            closeModal();
        } else if (action === 'page-prev') {
            state.currentPage = Math.max(1, state.currentPage - 1);
            renderList();
        } else if (action === 'page-next') {
            state.currentPage = Math.min(Math.ceil(filteredCategories().length / state.pageSize), state.currentPage + 1);
            renderList();
        }
    }

    function openModal(mode, category = null) {
        state.modalMode = mode;
        state.currentCategory = category;
        state.formErrors = {};
        state.formData = category
            ? { name: category.name || '', status: isActive(category) ? 'ACTIVO' : 'INACTIVO', description: category.description || '' }
            : { name: '', status: 'ACTIVO', description: '' };
        state.showModal = true;
        renderList();
    }

    function closeModal() {
        state.showModal = false;
        state.currentCategory = null;
        state.formErrors = {};
        renderList();
    }

    function bindModalActions() {
        const form = app.querySelector('#categoryForm');
        if (!form) return;
        form.querySelector('[name="name"]')?.addEventListener('input', event => { state.formData.name = event.target.value; });
        form.querySelector('[name="description"]')?.addEventListener('input', event => { state.formData.description = event.target.value; });
        form.querySelector('[name="status"]')?.addEventListener('change', event => { state.formData.status = event.target.value; });
        form.addEventListener('submit', saveCategory);
    }

    async function saveCategory(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const name = form.elements.name.value.trim();
        const description = form.elements.description.value.trim();
        const errors = {};
        if (name.length < 2) errors.name = name ? 'Debe tener al menos 2 caracteres.' : 'El nombre es obligatorio.';
        if (name.length > 100) errors.name = 'El nombre no puede superar los 100 caracteres.';
        if (description.length > 255) errors.description = 'La descripción no puede superar los 255 caracteres.';
        if (Object.keys(errors).length) {
            state.formErrors = errors;
            state.formData.name = name;
            state.formData.description = description;
            renderList();
            return;
        }

        state.formData.name = name;
        state.formData.description = description;
        state.submitting = true;
        state.formErrors = {};
        renderList();
        const payload = {
            name,
            description,
            status: state.formData.status
        };
        try {
            if (state.modalMode === 'create') {
                await request('/api/categories', { method: 'POST', body: JSON.stringify(payload) });
            } else {
                await request(`/api/categories/${encodeURIComponent(state.currentCategory.id)}`, { method: 'PUT', body: JSON.stringify(payload) });
            }
            const wasCreate = state.modalMode === 'create';
            state.submitting = false;
            state.showModal = false;
            state.currentCategory = null;
            state.currentPage = 1;
            await loadCategories(false);
            showFeedbackMessage(wasCreate ? '¡Categoría creada exitosamente!' : '¡Categoría actualizada exitosamente!');
        } catch (error) {
            state.submitting = false;
            state.formErrors = error.data?.errors || { general: error.message };
            renderList();
        }
    }

    async function deleteOne() {
        if (!state.categoryToDelete) return;
        const category = state.categoryToDelete;
        state.submitting = true;
        renderList();
        try {
            await request(`/api/categories/${encodeURIComponent(category.id)}`, { method: 'DELETE' });
            state.selectedIds.delete(String(category.id));
            state.categoryToDelete = null;
            state.submitting = false;
            await loadCategories(false);
            showFeedbackMessage(`Categoría “${category.name}” eliminada.`);
        } catch (error) {
            state.submitting = false;
            state.categoryToDelete = null;
            renderList();
            showFeedbackMessage(error.message || 'No se pudo eliminar la categoría.', 'error');
        }
    }

    async function deleteSelected() {
        const ids = [...state.selectedIds];
        if (!ids.length || !window.confirm(`¿Estás segura de eliminar las ${ids.length} categorías seleccionadas?`)) return;
        state.loading = true;
        renderList();
        const results = await Promise.allSettled(ids.map(id => request(`/api/categories/${encodeURIComponent(id)}`, { method: 'DELETE' })));
        const failedIds = ids.filter((_, index) => results[index].status === 'rejected');
        state.selectedIds = new Set(failedIds);
        state.selectionMode = failedIds.length > 0;
        await loadCategories(false);
        const deletedCount = ids.length - failedIds.length;
        showFeedbackMessage(failedIds.length ? `${deletedCount} categorías eliminadas; ${failedIds.length} no se pudieron eliminar.` : `${deletedCount} categorías eliminadas.`, failedIds.length ? 'error' : 'success');
    }

    renderList();
    loadCategories(false);
});
