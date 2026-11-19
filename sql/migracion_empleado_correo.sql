-- Correo del empleado (necesario para la verificación por código)
ALTER TABLE empleado ADD COLUMN correo VARCHAR(255) NULL AFTER telefono;
ALTER TABLE empleado ADD UNIQUE KEY uq_empleado_correo (correo);

-- Usuarios ligados a un empleado deben tener rol Barbero (2)
UPDATE usuario u JOIN empleado e ON e.id_usuario = u.id_usuario SET u.id_rol = 2 WHERE u.id_rol = 3;

-- Galería de imágenes de servicios (la usa DAOServicios.php)
CREATE TABLE IF NOT EXISTS servicio_imagen (
  id_imagen INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  id_servicio INT UNSIGNED NOT NULL,
  ruta_imagen VARCHAR(255) NOT NULL,
  CONSTRAINT fk_servicio_imagen_servicio FOREIGN KEY (id_servicio) REFERENCES servicio (id_servicio) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
