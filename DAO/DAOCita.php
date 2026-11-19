<?php
// Agenda de citas del cliente (solo clientes con sesión iniciada)
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/conexion.php";

header('Content-Type: application/json; charset=utf-8');

function responder(array $datos): void {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!esCliente()) {
    http_response_code(403);
    responder(["estado" => false, "mensaje" => "Acceso solo para clientes."]);
}

$opcion = $_POST['opcion'] ?? $_GET['opcion'] ?? '';
const PASO_MINUTOS = 30;

/** id_cliente ligado al usuario en sesión */
function idClienteSesion(mysqli $conexion): int {
    $stmt = $conexion->prepare("SELECT id_cliente FROM cliente WHERE id_usuario = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['idusuario']);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($fila['id_cliente'] ?? 0);
}

/** Normaliza "Miércoles" -> "miercoles" */
function normalizarDia(string $dia): string {
    return strtolower(strtr(trim($dia), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u']));
}

/** Servicios activos seleccionados: [duracionTotal, total, filas] */
function resumenServicios(mysqli $conexion, array $ids): array {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($i) => $i > 0)));
    if (!$ids) return [0, 0.0, []];

    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conexion->prepare("SELECT id_servicio, nombre_servicio, precio, duracion_estimada FROM servicio WHERE estado_servicio = 'A' AND id_servicio IN ($marcas)");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $duracion = 0; $total = 0.0;
    foreach ($filas as $f) { $duracion += (int)$f['duracion_estimada']; $total += (float)$f['precio']; }
    return [$duracion, $total, $filas];
}

/** Horas de inicio disponibles ("HH:MM") para un barbero, fecha y duración */
function calcularDisponibilidad(mysqli $conexion, int $idEmpleado, string $fecha, int $duracion): array {
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$d || $d->format('Y-m-d') !== $fecha || $duracion <= 0) return [];

    $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
    $diaBuscado = $dias[(int)$d->format('N') - 1];

    $stmt = $conexion->prepare("SELECT dia_semana, hora_entrada, hora_salida FROM horario_empleado WHERE id_empleado = ?");
    $stmt->bind_param("i", $idEmpleado);
    $stmt->execute();
    $horarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $horario = null;
    foreach ($horarios as $h) {
        if (normalizarDia($h['dia_semana']) === $diaBuscado) { $horario = $h; break; }
    }
    if (!$horario) return [];

    // Citas pendientes del barbero ese día
    $inicioDia = $fecha . ' 00:00:00';
    $finDia = $fecha . ' 23:59:59';
    $stmt = $conexion->prepare("SELECT fecha_hora_inicio, fecha_hora_fin FROM cita WHERE id_empleado = ? AND estado_cita = 'Pendiente' AND fecha_hora_inicio BETWEEN ? AND ?");
    $stmt->bind_param("iss", $idEmpleado, $inicioDia, $finDia);
    $stmt->execute();
    $ocupadas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $slots = [];
    $ahora = time();
    $cursor = strtotime("$fecha {$horario['hora_entrada']}");
    $salida = strtotime("$fecha {$horario['hora_salida']}");

    for (; $cursor + $duracion * 60 <= $salida; $cursor += PASO_MINUTOS * 60) {
        $fin = $cursor + $duracion * 60;
        if ($cursor <= $ahora) continue;

        $libre = true;
        foreach ($ocupadas as $o) {
            if (strtotime($o['fecha_hora_inicio']) < $fin && strtotime($o['fecha_hora_fin']) > $cursor) { $libre = false; break; }
        }
        if ($libre) $slots[] = date('H:i', $cursor);
    }
    return $slots;
}

try {
    if ($opcion === "catalogo") {
        $servicios = $conexion->query("SELECT id_servicio, nombre_servicio, descripcion, precio, duracion_estimada FROM servicio WHERE estado_servicio = 'A' ORDER BY nombre_servicio")->fetch_all(MYSQLI_ASSOC);
        $barberos = $conexion->query("SELECT id_empleado, CONCAT(nombre, ' ', apellido) AS nombre FROM empleado WHERE atiende_citas = 1 ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);
        responder(["estado" => true, "servicios" => $servicios, "barberos" => $barberos]);
    }

    if ($opcion === "disponibilidad") {
        [$duracion] = resumenServicios($conexion, (array)($_POST['servicios'] ?? []));
        $slots = calcularDisponibilidad($conexion, (int)($_POST['id_empleado'] ?? 0), $_POST['fecha'] ?? '', $duracion);
        responder(["estado" => true, "horas" => $slots, "duracion" => $duracion]);
    }

    if ($opcion === "agendar") {
        $idCliente = idClienteSesion($conexion);
        if ($idCliente <= 0) responder(["estado" => false, "mensaje" => "Tu cuenta no tiene un perfil de cliente."]);

        $idEmpleado = (int)($_POST['id_empleado'] ?? 0);
        $fecha = $_POST['fecha'] ?? '';
        $hora = $_POST['hora'] ?? '';
        $nota = mb_substr(trim($_POST['nota'] ?? ''), 0, 255);

        [$duracion, $total, $servicios] = resumenServicios($conexion, (array)($_POST['servicios'] ?? []));
        if (!$servicios) responder(["estado" => false, "mensaje" => "Selecciona al menos un servicio."]);

        $stmt = $conexion->prepare("SELECT 1 FROM empleado WHERE id_empleado = ? AND atiende_citas = 1");
        $stmt->bind_param("i", $idEmpleado);
        $stmt->execute();
        $existeBarbero = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$existeBarbero) responder(["estado" => false, "mensaje" => "Selecciona un barbero válido."]);

        $disponibles = calcularDisponibilidad($conexion, $idEmpleado, $fecha, $duracion);
        if (!in_array($hora, $disponibles, true)) {
            responder(["estado" => false, "mensaje" => "Ese horario ya no está disponible. Elige otro."]);
        }

        $inicio = strtotime("$fecha $hora");
        $fin = $inicio + $duracion * 60;
        $inicioSql = date('Y-m-d H:i:s', $inicio);
        $finSql = date('Y-m-d H:i:s', $fin);

        $conexion->begin_transaction();
        try {
            $stmt = $conexion->prepare("INSERT INTO cita (fecha_hora_inicio, fecha_hora_fin, total_cita, estado_cita, nota, id_cliente, id_empleado) VALUES (?, ?, ?, 'Pendiente', ?, ?, ?)");
            $stmt->bind_param("ssdsii", $inicioSql, $finSql, $total, $nota, $idCliente, $idEmpleado);
            $stmt->execute();
            $idCita = $conexion->insert_id;
            $stmt->close();

            $stmt = $conexion->prepare("INSERT INTO detalle_cita (id_cita, id_servicio, precio_unitario) VALUES (?, ?, ?)");
            foreach ($servicios as $s) {
                $idServ = (int)$s['id_servicio'];
                $precio = (float)$s['precio'];
                $stmt->bind_param("iid", $idCita, $idServ, $precio);
                $stmt->execute();
            }
            $stmt->close();
            $conexion->commit();
        } catch (Throwable $e) {
            $conexion->rollback();
            throw $e;
        }

        responder(["estado" => true, "mensaje" => "¡Cita agendada para el " . date('d/m/Y', $inicio) . " a las " . date('H:i', $inicio) . "!"]);
    }

    if ($opcion === "historial") {
        $idCliente = idClienteSesion($conexion);
        $stmt = $conexion->prepare(
            "SELECT c.id_cita, c.fecha_hora_inicio, c.fecha_hora_fin, c.total_cita, c.estado_cita, c.nota,
                    CONCAT(e.nombre, ' ', e.apellido) AS barbero,
                    (SELECT GROUP_CONCAT(s.nombre_servicio SEPARATOR ', ')
                       FROM detalle_cita d JOIN servicio s ON s.id_servicio = d.id_servicio
                      WHERE d.id_cita = c.id_cita) AS servicios
               FROM cita c JOIN empleado e ON e.id_empleado = c.id_empleado
              WHERE c.id_cliente = ?
              ORDER BY c.fecha_hora_inicio DESC"
        );
        $stmt->bind_param("i", $idCliente);
        $stmt->execute();
        $citas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        responder(["estado" => true, "citas" => $citas]);
    }

    if ($opcion === "cancelar") {
        $idCliente = idClienteSesion($conexion);
        $idCita = (int)($_POST['id_cita'] ?? 0);
        $stmt = $conexion->prepare("UPDATE cita SET estado_cita = 'Cancelada' WHERE id_cita = ? AND id_cliente = ? AND estado_cita = 'Pendiente' AND fecha_hora_inicio > NOW()");
        $stmt->bind_param("ii", $idCita, $idCliente);
        $stmt->execute();
        $afectadas = $stmt->affected_rows;
        $stmt->close();
        responder($afectadas > 0
            ? ["estado" => true, "mensaje" => "Cita cancelada."]
            : ["estado" => false, "mensaje" => "No se pudo cancelar la cita."]);
    }

    responder(["estado" => false, "mensaje" => "Opción no válida."]);
} catch (Throwable $e) {
    responder(["estado" => false, "mensaje" => "Error en el servidor: " . $e->getMessage()]);
}
