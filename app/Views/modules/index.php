<?php

declare(strict_types=1);

$isDashboard = $activeModule === 'dashboard';
$inactiveRoles = count($roles) - $activeRoles;
$recentRoles = array_slice($roles, 0, 5);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?> | AppSig</title>
    <link rel="stylesheet" href="/css/role.css" />
    <link rel="stylesheet" href="/css/menu.css" />
    <link rel="stylesheet" href="/css/modules.css" />
</head>
<body>
    <?php require __DIR__ . '/../partials/navigation.php'; ?>

    <section class="module-page">
        <header class="module-page-heading">
            <div>
                <span class="module-eyebrow">ENECON / <?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?></span>
                <h1><?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p><?= htmlspecialchars($module['description'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <span class="workspace-label">ENTORNO DE TRABAJO</span>
        </header>

        <?php if ($isDashboard): ?>
            <section class="module-metrics" aria-label="Resumen de roles">
                <article><span>Roles registrados</span><strong><?= count($roles) ?></strong><small>Perfiles en la plataforma</small></article>
                <article class="metric-active"><span>Activos</span><strong><?= $activeRoles ?></strong><small>Con acceso habilitado</small></article>
                <article class="metric-inactive"><span>Inactivos</span><strong><?= $inactiveRoles ?></strong><small>Con acceso suspendido</small></article>
                <article class="metric-permissions"><span>Con permisos</span><strong><?= $rolesWithPermissions ?></strong><small>Con módulos asignados</small></article>
            </section>

            <section class="module-content-panel">
                <header class="module-panel-heading">
                    <div><h2>Roles recientes</h2><p>Perfiles de acceso registrados en el sistema.</p></div>
                    <a class="module-text-link" href="/roles">Ver todos los roles</a>
                </header>
                <?php if ($recentRoles === []): ?>
                    <div class="module-empty-compact"><strong>Aún no hay roles registrados</strong><span>Cuando se creen roles, aparecerán aquí.</span></div>
                <?php else: ?>
                    <div class="module-table-scroll">
                        <table class="module-table">
                            <thead><tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Estado</th></tr></thead>
                            <tbody>
                                <?php foreach ($recentRoles as $role): ?>
                                    <?php $active = $role->getStatus() === '1'; ?>
                                    <tr>
                                        <td><?= (int) $role->getId() ?></td>
                                        <td><strong><?= htmlspecialchars($role->getName(), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                        <td><?= htmlspecialchars($role->getDescription() ?: 'Sin descripción', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="module-status <?= $active ? 'is-active' : 'is-inactive' ?>"><?= $active ? 'Activo' : 'Inactivo' ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <section class="module-empty-state">
                <span class="module-empty-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11l2 2h4.5A2.5 2.5 0 0 1 20 7.5v10a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5z"/><path d="M8 11h8M8 15h5"/></svg>
                </span>
                <span class="module-eyebrow">MÓDULO</span>
                <h2><?= htmlspecialchars($module['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p>Esta sección ya tiene una ruta propia. Su modelo, operaciones y conexión de datos todavía no están implementados en el proyecto.</p>
                <a class="module-return-link" href="/dashboard">Volver al Dashboard</a>
            </section>
        <?php endif; ?>
    </section>
</main>
</div>
</body>
</html>