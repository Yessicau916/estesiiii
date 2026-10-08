<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Empleados | AppSig</title>
    <link rel="stylesheet" href="/css/role.css" />
    <link rel="stylesheet" href="/css/menu.css" />
    <link rel="stylesheet" href="/css/employees.css" />
</head>
<body>
    <?php require __DIR__ . '/../partials/navigation.php'; ?>
    <div id="employeeApp" class="employee-app" data-directory-mode="<?= ActiveDirectory::isMockMode() ? 'mock' : 'ldap' ?>" data-roles="<?= htmlspecialchars(json_encode($roles, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>" aria-live="polite"></div>
    </main>
    </div>
    <script src="/js/menu.js"></script>
    <script src="/js/employees.js"></script>
</body>
</html>