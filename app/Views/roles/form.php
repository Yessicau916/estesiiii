<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($role) ? 'Editar Rol' : 'Crear Rol'; ?></title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
    <div class="container">
        <h1><?php echo isset($role) ? 'Editar Rol' : 'Crear Nuevo Rol'; ?></h1>
        <form action="<?php echo isset($role) ? '/roles/update/' . $role->id : '/roles/store'; ?>" method="POST">
            <div class="form-group">
                <label for="name">Nombre del Rol <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" value="<?php echo isset($role) ? htmlspecialchars($role->name) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Descripción</label>
                <textarea id="description" name="description"><?php echo isset($role) ? htmlspecialchars($role->description) : ''; ?></textarea>
            </div>
            <div class="form-group">
                <label for="status">Estado</label>
                <select id="status" name="status">
                    <option value="ACTIVO" <?php echo (isset($role) && $role->status === 'ACTIVO') ? 'selected' : ''; ?>>Activo</option>
                    <option value="INACTIVO" <?php echo (isset($role) && $role->status === 'INACTIVO') ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
            <div class="form-group">
                <label>Permisos</label>
                <div>
                    <!-- Aquí se generarán los checkboxes para los permisos -->
                    <?php foreach ($modules as $module): ?>
                        <div>
                            <h4><?php echo htmlspecialchars($module['name']); ?></h4>
                            <?php foreach ($module['actions'] as $action): ?>
                                <label>
                                    <input type="checkbox" name="permissions[<?php echo $module['id']; ?>][]" value="<?php echo $action['id']; ?>" 
                                    <?php echo (isset($role) && in_array($action['id'], $role->permissions[$module['id']] ?? [])) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($action['label']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit"><?php echo isset($role) ? 'Actualizar Rol' : 'Crear Rol'; ?></button>
        </form>
        <a href="/roles" class="btn btn-secondary">Volver a la lista</a>
    </div>
</body>
</html>