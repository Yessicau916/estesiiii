ALTER TABLE ordenes_trabajo
    ADD COLUMN prioridad ENUM('ALTA', 'MEDIA', 'BAJA') NOT NULL DEFAULT 'MEDIA' AFTER numero,
    ADD COLUMN estado_orden ENUM('ACTIVO', 'PENDIENTE', 'EJECUTADO', 'SUSPENDIDO', 'CANCELADO', 'REASIGNADO', 'INACTIVO') NOT NULL DEFAULT 'PENDIENTE' AFTER estado;

UPDATE ordenes_trabajo o
LEFT JOIN asignaciones_contrato a ON a.id = o.asignacion_id
SET o.estado_orden = CASE
    WHEN o.estado = 0 THEN 'INACTIVO'
    WHEN a.estado = 'realizado' THEN 'EJECUTADO'
    WHEN a.estado = 'reasignar' THEN 'REASIGNADO'
    WHEN a.estado = 'asignado' THEN 'ACTIVO'
    ELSE 'PENDIENTE'
END;
