-- Correo del empleado (necesario para la verificación por código)
ALTER TABLE empleado ADD COLUMN correo VARCHAR(255) NULL AFTER telefono;
ALTER TABLE empleado ADD UNIQUE KEY uq_empleado_correo (correo);

-- Usuarios ligados a un empleado deben tener rol Barbero (2)
UPDATE usuario u JOIN empleado e ON e.id_usuario = u.id_usuario SET u.id_rol = 2 WHERE u.id_rol = 3;
