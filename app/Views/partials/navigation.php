<?php

declare(strict_types=1);

$currentRole = $currentRole ?? null;
if (!$currentRole && !empty($_SESSION['role_id'])) {
    require_once __DIR__ . '/../../Models/Role.php';
    $currentRole = Role::findById((int) $_SESSION['role_id']);
}
$rolePermissions = $currentRole?->getPermissions() ?? [];
$currentRoleName = $currentRole?->getName() ?? 'Sin rol asignado';

?>
<header class="global-header">
    <div class="header-brand">
        <a class="brand-mark" href="/dashboard" aria-label="ENECON">
            <img src="/image/logonaranja.png" alt="ENECON" />
        </a>
        <div class="header-role">
            <span>ROL</span>
            <strong><?= htmlspecialchars($currentRoleName, ENT_QUOTES, 'UTF-8') ?></strong>
            <?php
            $moduleLabels = ['dashboard' => 'Dashboard', 'users' => 'Usuarios', 'roles' => 'Roles', 'employees' => 'Empleados', 'categories' => 'Categorías', 'work_orders' => 'Órdenes de trabajo', 'forms' => 'Formularios', 'contracts' => 'Contratos', 'regions' => 'Regiones'];
            $actionLabels = ['ver' => 'Consultar', 'exportar' => 'Exportar', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar', 'asignar' => 'Asignar', 'responder' => 'Diligenciar', 'importar' => 'Importar', 'seleccionar' => 'Seleccionar órdenes', 'editar_masivo' => 'Edición masiva', 'eliminar_masivo' => 'Eliminación masiva', 'ver_ejecucion' => 'Ver ejecución'];
            ?>
            <details class="role-permissions">
                <summary>Ver permisos</summary>
                <div class="role-permissions-popover"><strong>Permisos de <?= htmlspecialchars($currentRoleName, ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php if ($rolePermissions === []): ?><small>Este rol no tiene permisos asignados.</small><?php else: ?>
                        <ul><?php foreach ($rolePermissions as $moduleId => $actions): ?><li><strong><?= htmlspecialchars($moduleLabels[$moduleId] ?? $moduleId, ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars(implode(' · ', array_map(static fn (string $action): string => $actionLabels[$action] ?? $action, $actions)), ENT_QUOTES, 'UTF-8') ?></span></li><?php endforeach; ?></ul>
                    <?php endif; ?>
                </div>
            </details>
        </div>
    </div>
    <div class="user-profile">
        <span class="user-initial"><?= htmlspecialchars(strtoupper(substr($_SESSION['employee_name'] ?? 'U', 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
        <div>
            <strong><?= htmlspecialchars($_SESSION['employee_name'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?></strong>
            <small>Sesión activa</small>
        </div>
        <form method="post" action="/logout"><button class="logout-button" type="submit">Cerrar sesión</button></form>
    </div>
</header>

<div class="app-shell">
    <script>window.APP_PERMISSIONS = <?= json_encode($rolePermissions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;</script>
    <aside class="sidebar">
        <nav class="main-menu" aria-label="Módulos principales" data-active-module="<?= htmlspecialchars($activeModule ?? '', ENT_QUOTES, 'UTF-8') ?>"></nav>

        <div class="sidebar-footer">
            <span class="status-dot"></span>
            <span>Plataforma ENECON</span>
        </div>
    </aside>
    <main class="main-content">
