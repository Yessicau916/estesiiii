<?php

declare(strict_types=1);

$navigationItems = [
    ['slug' => 'dashboard', 'label' => 'Dashboard', 'url' => '/dashboard', 'icon' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>'],
    ['slug' => 'employees', 'label' => 'Empleados', 'url' => '/employees', 'icon' => '<circle cx="9" cy="8" r="3.5"/><path d="M3 20v-1.5a6 6 0 0 1 12 0V20M16 5.5a3.5 3.5 0 0 1 0 6.8M18 14a5 5 0 0 1 3 4.5V20"/>'],
    ['slug' => 'categories', 'label' => 'Categorías', 'url' => '/categories', 'icon' => '<path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H10l2 2h6.5A2.5 2.5 0 0 1 21 9.5v8a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5z"/><path d="M3.5 9h17"/>'],
    ['slug' => 'roles', 'label' => 'Roles', 'url' => '/roles', 'icon' => '<path d="m12 3 7 3v5c0 4.5-2.8 7.8-7 10-4.2-2.2-7-5.5-7-10V6z"/><path d="m9 12 2 2 4-4"/>'],
    ['slug' => 'work-orders', 'label' => 'Orden de trabajo', 'url' => '/work-orders', 'icon' => '<rect x="3" y="8" width="18" height="12" rx="2"/><path d="M8 8V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13h18M10 13v2h4v-2"/>'],
    ['slug' => 'forms', 'label' => 'Formulario', 'url' => '/forms', 'icon' => '<path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>'],
    ['slug' => 'contracts', 'label' => 'Contrato', 'url' => '/contracts', 'icon' => '<path d="M7 3h7l5 5v4M14 3v5h5M7 21h5"/><path d="M5 5a2 2 0 0 1 2-2M5 5v14a2 2 0 0 0 2 2"/><path d="m15 19 4.5-4.5a1.8 1.8 0 0 1 2.5 2.5L17.5 22H15z"/>'],
];
?>
<header class="global-header">
    <div class="header-brand">
        <a class="brand-mark" href="/dashboard" aria-label="ENECON">
            <img src="/image/logonaranja.png" alt="ENECON" />
        </a>
        <div class="header-role">
            <span>ROL</span>
            <strong>Super Administradora</strong>
        </div>
    </div>
    <div class="user-profile">
        <span class="user-initial">T</span>
        <div>
            <strong>Yessica Zulay Urrego</strong>
            <small>Desarrolladora Senior</small>
        </div>
    </div>
</header>

<div class="app-shell">
    <aside class="sidebar">
        <nav class="main-menu" aria-label="Módulos principales">
            <?php foreach ($navigationItems as $item): ?>
                <?php $isActive = $activeModule === $item['slug']; ?>
                <a class="nav-item<?= $isActive ? ' active' : '' ?>" href="<?= $item['url'] ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                    <span class="nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><?= $item['icon'] ?></svg></span>
                    <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <span class="status-dot"></span>
            <span>Plataforma ENECON</span>
        </div>
    </aside>
    <main class="main-content">