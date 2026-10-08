CREATE TABLE IF NOT EXISTS privilegios (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS permisos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS privilegios_permisos (
    permiso_id    INT UNSIGNED NOT NULL,
    privilegio_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (permiso_id, privilegio_id),
    CONSTRAINT fk_pp_permiso FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE CASCADE,
    CONSTRAINT fk_pp_privilegio FOREIGN KEY (privilegio_id) REFERENCES privilegios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS roles_permisos (
    permiso_id INT UNSIGNED NOT NULL,
    rol_id     INT UNSIGNED NOT NULL,
    PRIMARY KEY (permiso_id, rol_id),
    CONSTRAINT fk_rp_permiso FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO privilegios (nombre, descripcion)
SELECT catalogo.nombre, catalogo.descripcion
FROM (
    SELECT 'Lectura' AS nombre, 'Acceso de solo lectura a los módulos' AS descripcion
    UNION ALL SELECT 'Escritura', 'Acceso para crear y modificar registros'
) AS catalogo
WHERE NOT EXISTS (SELECT 1 FROM privilegios p WHERE p.nombre = catalogo.nombre);

INSERT IGNORE INTO permisos (nombre, descripcion) VALUES
    ('dashboard.leer', 'Permite ver Dashboard'), ('dashboard.exportar', 'Permite exportar reportes del Dashboard'),
    ('usuarios.leer', 'Permite ver usuarios'), ('usuarios.crear', 'Permite crear usuarios'), ('usuarios.editar', 'Permite editar usuarios'), ('usuarios.eliminar', 'Permite eliminar usuarios'),
    ('roles.leer', 'Permite ver roles'), ('roles.crear', 'Permite crear roles'), ('roles.editar', 'Permite editar roles'), ('roles.eliminar', 'Permite eliminar roles'),
    ('empleados.leer', 'Permite ver empleados'), ('empleados.crear', 'Permite crear empleados'), ('empleados.editar', 'Permite editar empleados'), ('empleados.eliminar', 'Permite eliminar empleados'),
    ('categorias.leer', 'Permite ver categorías'), ('categorias.crear', 'Permite crear categorías'), ('categorias.editar', 'Permite editar categorías'), ('categorias.eliminar', 'Permite eliminar categorías'),
    ('ordenes.leer', 'Permite ver órdenes de trabajo'), ('ordenes.crear', 'Permite crear órdenes de trabajo'), ('ordenes.editar', 'Permite editar órdenes de trabajo'), ('ordenes.eliminar', 'Permite eliminar órdenes de trabajo'), ('ordenes.asignar', 'Permite asignar órdenes de trabajo'),
    ('formularios.leer', 'Permite ver formularios'), ('formularios.crear', 'Permite crear formularios'), ('formularios.editar', 'Permite editar formularios'), ('formularios.eliminar', 'Permite eliminar formularios'), ('formularios.responder', 'Permite diligenciar formularios'),
    ('contratos.leer', 'Permite ver contratos'), ('contratos.crear', 'Permite crear contratos'), ('contratos.editar', 'Permite editar contratos'), ('contratos.eliminar', 'Permite eliminar contratos'),
    ('regiones.leer', 'Permite ver regiones'), ('regiones.crear', 'Permite crear regiones'), ('regiones.editar', 'Permite editar regiones'), ('regiones.eliminar', 'Permite eliminar regiones');

INSERT IGNORE INTO privilegios_permisos (permiso_id, privilegio_id)
SELECT p.id, pr.id
FROM permisos p
INNER JOIN privilegios pr ON pr.nombre = CASE
    WHEN SUBSTRING_INDEX(p.nombre, '.', -1) IN ('leer', 'exportar') THEN 'Lectura'
    ELSE 'Escritura'
END
WHERE p.nombre REGEXP '^(dashboard|usuarios|roles|empleados|categorias|ordenes|formularios|contratos|regiones)\\.'
  AND SUBSTRING_INDEX(p.nombre, '.', -1) IN ('leer', 'exportar', 'crear', 'editar', 'eliminar', 'asignar', 'responder');