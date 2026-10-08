document.addEventListener('DOMContentLoaded', () => {
    const menu = document.querySelector('[data-active-module]');
    if (!menu) return;

    const items = [
        { slug: 'dashboard', label: 'Dashboard', url: '/dashboard', icon: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>' },
        { slug: 'employees', label: 'Empleados', url: '/employees', icon: '<circle cx="9" cy="8" r="3.5"/><path d="M3 20v-1.5a6 6 0 0 1 12 0V20M16 5.5a3.5 3.5 0 0 1 0 6.8M18 14a5 5 0 0 1 3 4.5V20"/>' },
        { slug: 'categories', label: 'Categorías', url: '/categories', icon: '<path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H10l2 2h6.5A2.5 2.5 0 0 1 21 9.5v8a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5z"/><path d="M3.5 9h17"/>' },
        { slug: 'roles', label: 'Roles', url: '/roles', icon: '<path d="m12 3 7 3v5c0 4.5-2.8 7.8-7 10-4.2-2.2-7-5.5-7-10V6z"/><path d="m9 12 2 2 4-4"/>' },
        { slug: 'work-orders', label: 'Orden de trabajo', url: '/work-orders', icon: '<rect x="3" y="8" width="18" height="12" rx="2"/><path d="M8 8V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13h18M10 13v2h4v-2"/>' },
        { slug: 'forms', label: 'Formulario', url: '/forms', icon: '<path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>' },
    ];

    const contractItems = [
        { slug: 'contracts', label: 'Contrato', url: '/contracts', icon: '<path d="M7 3h7l5 5v4M14 3v5h5M7 21h5"/><path d="M5 5a2 2 0 0 1 2-2M5 5v14a2 2 0 0 0 2 2"/><path d="m15 19 4.5-4.5a1.8 1.8 0 0 1 2.5 2.5L17.5 22H15z"/>', permission: 'contracts' },
        { slug: 'assignments', label: 'Asignación', url: '/assignments', icon: '<path d="M8 6h13M8 12h13M8 18h13"/><path d="m3 6 1 1 2-2M3 12l1 1 2-2M3 18l1 1 2-2"/>', permission: 'contracts' },
        { slug: 'regions', label: 'Regiones', url: '/regions', icon: '<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>', permission: 'regions' }
    ];

    const permissionBySlug = { dashboard: 'dashboard', employees: 'employees', categories: 'categories', roles: 'roles', 'work-orders': 'work_orders', forms: 'forms' };
    const permissions = window.APP_PERMISSIONS || {};
    const visibleItems = items.filter(item => item.slug === 'dashboard' || (permissions[permissionBySlug[item.slug]] || []).length > 0);
    const visibleContractItems = contractItems.filter(item => (permissions[item.permission] || []).length > 0);
    const contractGroupActive = visibleContractItems.some(item => item.slug === menu.dataset.activeModule);

    const links = visibleItems.map(item => {
        const active = item.slug === menu.dataset.activeModule;
        return `<a class="nav-item${active ? ' active' : ''}" href="${item.url}"${active ? ' aria-current="page"' : ''}>
            <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">${item.icon}</svg></span>
            <span>${item.label}</span>
        </a>`;
    }).join('');
    const contractMenu = visibleContractItems.length ? `<details class="nav-group${contractGroupActive ? ' active' : ''}"${contractGroupActive ? ' open' : ''}>
        <summary class="nav-item${contractGroupActive ? ' active' : ''}"><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">${contractItems[0].icon}</svg></span><span>Contrato</span><svg class="nav-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg></summary>
        <div class="nav-submenu">${visibleContractItems.map(item => {
            const active = item.slug === menu.dataset.activeModule;
            return `<a class="nav-subitem${active ? ' active' : ''}" href="${item.url}"${active ? ' aria-current="page"' : ''}><span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">${item.icon}</svg></span><span>${item.label}</span></a>`;
        }).join('')}</div>
    </details>` : '';
    menu.innerHTML = links + contractMenu;
});
