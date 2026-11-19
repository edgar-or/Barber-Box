<?php
// Gestión de citas para el personal.
// Administrador: ve el historial de todas las citas. Barbero: solo las citas agendadas con él.
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/conexion.php";

requerirEmpleadoJson();
header('Content-Type: application/json; charset=utf-8');

function responder(array $datos): void {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

$esAdmin = (int)$_SESSION['id_rol'] === ROL_ADMIN;
$opcion = $_POST['opcion'] ?? '';

// Para un barbero: su id_empleado (las consultas se limitan a él)
$idEmpleadoSesion = 0;
if (!$esAdmin) {
    $stmt = $conexion->prepare("SELECT id_empleado FROM empleado WHERE id_usuario = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['idusuario']);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $idEmpleadoSesion = (int)($fila['id_empleado'] ?? 0);
    if ($idEmpleadoSesion <= 0) {
        responder(["estado" => true, "esAdmin" => false, "citas" => []]);
    }
}

try {
    if ($opcion === "consultar") {
        $sql = "SELECT c.id_cita, c.fecha_hora_inicio, c.fecha_hora_fin, c.total_cita, c.estado_cita,
                       CONCAT(cl.nombre, ' ', cl.apellido) AS cliente, cl.telefono,
                       CONCAT(e.nombre, ' ', e.apellido) AS barbero,
                       (SELECT GROUP_CONCAT(s.nombre_servicio SEPARATOR ', ')
                          FROM detalle_cita d JOIN servicio s ON s.id_servicio = d.id_servicio
                         WHERE d.id_cita = c.id_cita) AS servicios
                  FROM cita c
                  JOIN cliente cl ON cl.id_cliente = c.id_cliente
                  JOIN empleado e ON e.id_empleado = c.id_empleado";
        if ($esAdmin) {
            $stmt = $conexion->prepare($sql . " ORDER BY c.fecha_hora_inicio DESC");
        } else {
            $stmt = $conexion->prepare($sql . " WHERE c.id_empleado = ? ORDER BY c.fecha_hora_inicio DESC");
            $stmt->bind_param("i", $idEmpleadoSesion);
        }
        $stmt->execute();
        $citas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        responder(["estado" => true, "esAdmin" => $esAdmin, "citas" => $citas]);
    }

    if ($opcion === "detalle" || $opcion === "cambiarEstado") {
        $idCita = (int)($_POST['id_cita'] ?? 0);

        // Verificar acceso a la cita (un barbero solo accede a las suyas)
        $sql = "SELECT c.id_cita, c.fecha_hora_inicio, c.fecha_hora_fin, c.total_cita, c.estado_cita, c.nota,
                       CONCAT(cl.nombre, ' ', cl.apellido) AS cliente, cl.telefono, cl.correo,
                       CONCAT(e.nombre, ' ', e.apellido) AS barbero, c.id_empleado
                  FROM cita c
                  JOIN cliente cl ON cl.id_cliente = c.id_cliente
                  JOIN empleado e ON e.id_empleado = c.id_empleado
                 WHERE c.id_cita = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $idCita);
        $stmt->execute();
        $cita = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$cita || (!$esAdmin && (int)$cita['id_empleado'] !== $idEmpleadoSesion)) {
            responder(["estado" => false, "mensaje" => "Cita no encontrada."]);
        }

        if ($opcion === "detalle") {
            $stmt = $conexion->prepare("SELECT s.nombre_servicio, s.duracion_estimada, d.precio_unitario
                                          FROM detalle_cita d JOIN servicio s ON s.id_servicio = d.id_servicio
                                         WHERE d.id_cita = ?");
            $stmt->bind_param("i", $idCita);
            $stmt->execute();
            $detalle = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            responder(["estado" => true, "cita" => $cita, "detalle" => $detalle]);
        }

        // cambiarEstado
        $nuevo = $_POST['estado'] ?? '';
        if (!in_array($nuevo, ['Completada', 'Cancelada'], true)) {
            responder(["estado" => false, "mensaje" => "Estado no válido."]);
        }
        if ($cita['estado_cita'] !== 'Pendiente') {
            responder(["estado" => false, "mensaje" => "Solo se pueden modificar citas pendientes."]);
        }
        $stmt = $conexion->prepare("UPDATE cita SET estado_cita = ? WHERE id_cita = ?");
        $stmt->bind_param("si", $nuevo, $idCita);
        $stmt->execute();
        $stmt->close();
        responder(["estado" => true, "mensaje" => "Cita marcada como " . strtolower($nuevo) . "."]);
    }

    responder(["estado" => false, "mensaje" => "Opción no válida."]);
} catch (Throwable $e) {
    responder(["estado" => false, "mensaje" => "Error en el servidor: " . $e->getMessage()]);
}
