ALTER TABLE empleados
    ADD COLUMN directorio_id VARCHAR(150) NULL AFTER rol_id,
    ADD UNIQUE KEY uq_empleados_directorio_id (directorio_id);