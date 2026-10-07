<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Roles y Permisos</title>
    <link rel="stylesheet" href="/css/role.css" />
    <link rel="stylesheet" href="/css/menu.css" />
</head>
<body>
    <?php $activeModule = 'roles'; require __DIR__ . '/../partials/navigation.php'; ?>
    <div id="roleApp" class="role-app" aria-live="polite"></div>
    </main>
    </div>

    <script src="/js/roles.js"></script>
</body>
</html>