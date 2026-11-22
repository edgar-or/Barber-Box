<?php
// Agenda de citas del cliente (solo clientes con sesión iniciada)
require_once __DIR__ . "/../config/auth.php";
requerirClienteJson();
require_once __DIR__ . "/../config/conexion.php";

header('Content-Type: application/json; charset=utf-8');

const PASO_MINUTOS = 30;

$opcion        = isset($_REQUEST["opcion"]) ? $_REQUEST["opcion"] : "";
$id_empleado   = (int)($_POST['id_empleado'] ?? 0);
$fecha         = $_POST['fecha'] ?? '';
$hora          = $_POST['hora'] ?? '';
$nota          = mb_substr(trim($_POST['nota'] ?? ''), 0, 255);
$servicios_ids = (array)($_POST['servicios'] ?? []);
$id_cita       = (int)($_POST['id_cita'] ?? 0);

// id_cliente ligado al usuario en sesión
$id_usuario = (int)$_SESSION['idusuario'];
$stmtCli = $conexion->prepare("SELECT id_cliente FROM cliente WHERE id_usuario = ? LIMIT 1");
$stmtCli->bind_param("i", $id_usuario);
$stmtCli->execute();
$filaCli = $stmtCli->get_result()->fetch_assoc();
$stmtCli->close();
$id_cliente = (int)($filaCli['id_cliente'] ?? 0);

// Servicios activos seleccionados (precio y duración salen de la BD, nunca del cliente)
function obtenerServicios($conexion, $ids) {
    $servicios = [];
    $stmt = $conexion->prepare("SELECT id_servicio, precio, duracion_estimada FROM servicio WHERE id_servicio = ? AND estado_servicio = 'A'");

    foreach (array_unique(array_map('intval', $ids)) as $id) {
        if ($id <= 0) continue;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        if ($fila) $servicios[] = $fila;
    }
    $stmt->close();
    return $servicios;
}

// Horas de inicio disponibles ("HH:MM") para un barbero, fecha y duración
function calcularDisponibilidad($conexion, $id_empleado, $fecha, $duracion, $excluir_cita = 0) {
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$d || $d->format('Y-m-d') !== $fecha || $duracion <= 0) return [];

    $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
    $dia_buscado = $dias[(int)$d->format('N') - 1];

    // Horario del barbero para ese día de la semana
    $stmt = $conexion->prepare("SELECT dia_semana, hora_entrada, hora_salida FROM horario_empleado WHERE id_empleado = ?");
    $stmt->bind_param("i", $id_empleado);
    $stmt->execute();
    $horarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $horario = null;
    foreach ($horarios as $h) {
        $dia = strtolower(strtr(trim($h['dia_semana']), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u']));
        if ($dia === $dia_buscado) { $horario = $h; break; }
    }
    if (!$horario) return [];

    // Citas pendientes del barbero ese día (se ignora la cita que se está modificando)
    $inicio_dia = $fecha . ' 00:00:00';
    $fin_dia    = $fecha . ' 23:59:59';
    $stmt = $conexion->prepare("SELECT fecha_hora_inicio, fecha_hora_fin FROM cita WHERE id_empleado = ? AND estado_cita = 'Pendiente' AND id_cita <> ? AND fecha_hora_inicio BETWEEN ? AND ?");
    $stmt->bind_param("iiss", $id_empleado, $excluir_cita, $inicio_dia, $fin_dia);
    $stmt->execute();
    $ocupadas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Se recorre el horario de 30 en 30 minutos descartando horas pasadas u ocupadas
    $horas  = [];
    $ahora  = time();
    $cursor = strtotime("$fecha {$horario['hora_entrada']}");
    $salida = strtotime("$fecha {$horario['hora_salida']}");

    for (; $cursor + $duracion * 60 <= $salida; $cursor += PASO_MINUTOS * 60) {
        $fin = $cursor + $duracion * 60;
        if ($cursor <= $ahora) continue;

        $libre = true;
        foreach ($ocupadas as $o) {
            if (strtotime($o['fecha_hora_inicio']) < $fin && strtotime($o['fecha_hora_fin']) > $cursor) {
                $libre = false;
                break;
            }
        }
        if ($libre) $horas[] = date('H:i', $cursor);
    }
    return $horas;
}

if ($opcion == "catalogo") {
    try {
        $servicios = $conexion->query("SELECT id_servicio, nombre_servicio, descripcion, precio, duracion_estimada FROM servicio WHERE estado_servicio = 'A' ORDER BY nombre_servicio")->fetch_all(MYSQLI_ASSOC);
        $barberos  = $conexion->query("SELECT id_empleado, CONCAT(nombre, ' ', apellido) AS nombre FROM empleado WHERE atiende_citas = 1 ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

        echo json_encode(["estado" => true, "servicios" => $servicios, "barberos" => $barberos], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(["estado" => false, "mensaje" => "No se pudo obtener el catálogo"], JSON_UNESCAPED_UNICODE);
    }
}

if ($opcion == "disponibilidad") {
    try {
        $servicios = obtenerServicios($conexion, $servicios_ids);
        $duracion  = (int)array_sum(array_column($servicios, 'duracion_estimada'));
        $horas     = calcularDisponibilidad($conexion, $id_empleado, $fecha, $duracion, $id_cita);

        echo json_encode(["estado" => true, "horas" => $horas, "duracion" => $duracion], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(["estado" => false, "mensaje" => "No se pudo consultar la disponibilidad"], JSON_UNESCAPED_UNICODE);
    }
}

if ($opcion == "agendar") {
    $conexion->begin_transaction();

    try {
        if ($id_cliente <= 0) {
            throw new Exception("Tu cuenta no tiene un perfil de cliente.");
        }

        $servicios = obtenerServicios($conexion, $servicios_ids);
        if (!$servicios) {
            throw new Exception("Selecciona al menos un servicio.");
        }
        $duracion = (int)array_sum(array_column($servicios, 'duracion_estimada'));
        $total    = (float)array_sum(array_column($servicios, 'precio'));

        // Valida el barbero y bloquea su fila: dos clientes no pueden reservar la misma hora a la vez
        $stmt = $conexion->prepare("SELECT id_empleado FROM empleado WHERE id_empleado = ? AND atiende_citas = 1 FOR UPDATE");
        $stmt->bind_param("i", $id_empleado);
        $stmt->execute();
        $existe_barbero = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$existe_barbero) {
            throw new Exception("Selecciona un barbero válido.");
        }

        $disponibles = calcularDisponibilidad($conexion, $id_empleado, $fecha, $duracion);
        if (!in_array($hora, $disponibles, true)) {
            throw new Exception("Ese horario ya no está disponible. Elige otro.");
        }

        $inicio     = strtotime("$fecha $hora");
        $inicio_sql = date('Y-m-d H:i:s', $inicio);
        $fin_sql    = date('Y-m-d H:i:s', $inicio + $duracion * 60);

        $stmt = $conexion->prepare("INSERT INTO cita (fecha_hora_inicio, fecha_hora_fin, total_cita, estado_cita, nota, id_cliente, id_empleado) VALUES (?, ?, ?, 'Pendiente', ?, ?, ?)");
        $stmt->bind_param("ssdsii", $inicio_sql, $fin_sql, $total, $nota, $id_cliente, $id_empleado);
        if (!$stmt->execute()) {
            throw new Exception("Error al guardar la cita: " . $stmt->error);
        }
        $id_nueva_cita = $conexion->insert_id;
        $stmt->close();

        $stmt = $conexion->prepare("INSERT INTO detalle_cita (id_cita, id_servicio, precio_unitario) VALUES (?, ?, ?)");
        foreach ($servicios as $s) {
            $id_servicio = (int)$s['id_servicio'];
            $precio      = (float)$s['precio'];
            $stmt->bind_param("iid", $id_nueva_cita, $id_servicio, $precio);
            $stmt->execute();
        }
        $stmt->close();

        $conexion->commit();
        echo json_encode(["estado" => true, "mensaje" => "¡Cita agendada para el " . date('d/m/Y', $inicio) . " a las " . date('H:i', $inicio) . "!"], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        $conexion->rollback();
        echo json_encode(["estado" => false, "mensaje" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

if ($opcion == "historial") {
    try {
       $sql = "SELECT c.id_cita, c.fecha_hora_inicio, c.fecha_hora_fin, c.total_cita, c.estado_cita, c.nota, c.id_empleado,
               CONCAT(e.nombre, ' ', e.apellido) AS barbero,
               (SELECT GROUP_CONCAT(s.nombre_servicio SEPARATOR ', ')
                  FROM detalle_cita d JOIN servicio s ON s.id_servicio = d.id_servicio
                 WHERE d.id_cita = c.id_cita) AS servicios,
               (SELECT GROUP_CONCAT(d.id_servicio)
                  FROM detalle_cita d
                 WHERE d.id_cita = c.id_cita) AS servicios_ids
          FROM cita c JOIN empleado e ON e.id_empleado = c.id_empleado
         WHERE c.id_cliente = ?
         ORDER BY c.fecha_hora_inicio DESC";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_cliente);
        $stmt->execute();
        $citas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(["estado" => true, "citas" => $citas], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(["estado" => false, "mensaje" => "No se pudo obtener el historial"], JSON_UNESCAPED_UNICODE);
    }
}

if ($opcion == "cancelar") {
    try {
        // Solo cancela citas propias, pendientes y futuras
        $stmt = $conexion->prepare("UPDATE cita SET estado_cita = 'Cancelada' WHERE id_cita = ? AND id_cliente = ? AND estado_cita = 'Pendiente' AND fecha_hora_inicio > NOW()");
        $stmt->bind_param("ii", $id_cita, $id_cliente);
        $stmt->execute();
        $afectadas = $stmt->affected_rows;
        $stmt->close();

        if ($afectadas > 0) {
            echo json_encode(["estado" => true, "mensaje" => "Cita cancelada."], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["estado" => false, "mensaje" => "No se pudo cancelar la cita."], JSON_UNESCAPED_UNICODE);
        }
    } catch (Throwable $e) {
        echo json_encode(["estado" => false, "mensaje" => "Error al cancelar la cita"], JSON_UNESCAPED_UNICODE);
    }
}

if ($opcion == "modificar") {
    $conexion->begin_transaction();

    try {
        if ($id_cliente <= 0) {
            throw new Exception("Tu cuenta no tiene un perfil de cliente.");
        }

        // La cita debe ser propia, pendiente y futura (se bloquea la fila)
        $stmt = $conexion->prepare("SELECT id_cita FROM cita WHERE id_cita = ? AND id_cliente = ? AND estado_cita = 'Pendiente' AND fecha_hora_inicio > NOW() FOR UPDATE");
        $stmt->bind_param("ii", $id_cita, $id_cliente);
        $stmt->execute();
        $es_modificable = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$es_modificable) {
            throw new Exception("Esta cita ya no se puede modificar.");
        }

        $servicios = obtenerServicios($conexion, $servicios_ids);
        if (!$servicios) {
            throw new Exception("Selecciona al menos un servicio.");
        }
        $duracion = (int)array_sum(array_column($servicios, 'duracion_estimada'));
        $total    = (float)array_sum(array_column($servicios, 'precio'));

        $stmt = $conexion->prepare("SELECT id_empleado FROM empleado WHERE id_empleado = ? AND atiende_citas = 1 FOR UPDATE");
        $stmt->bind_param("i", $id_empleado);
        $stmt->execute();
        $existe_barbero = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$existe_barbero) {
            throw new Exception("Selecciona un barbero válido.");
        }

        // Disponibilidad ignorando la propia cita
        $disponibles = calcularDisponibilidad($conexion, $id_empleado, $fecha, $duracion, $id_cita);
        if (!in_array($hora, $disponibles, true)) {
            throw new Exception("Ese horario ya no está disponible. Elige otro.");
        }

        $inicio     = strtotime("$fecha $hora");
        $inicio_sql = date('Y-m-d H:i:s', $inicio);
        $fin_sql    = date('Y-m-d H:i:s', $inicio + $duracion * 60);

        $stmt = $conexion->prepare("UPDATE cita SET fecha_hora_inicio = ?, fecha_hora_fin = ?, total_cita = ?, nota = ?, id_empleado = ? WHERE id_cita = ? AND id_cliente = ?");
        $stmt->bind_param("ssdsiii", $inicio_sql, $fin_sql, $total, $nota, $id_empleado, $id_cita, $id_cliente);
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar la cita: " . $stmt->error);
        }
        $stmt->close();

        // Se reemplazan los servicios de la cita
        $stmt = $conexion->prepare("DELETE FROM detalle_cita WHERE id_cita = ?");
        $stmt->bind_param("i", $id_cita);
        $stmt->execute();
        $stmt->close();

        $stmt = $conexion->prepare("INSERT INTO detalle_cita (id_cita, id_servicio, precio_unitario) VALUES (?, ?, ?)");
        foreach ($servicios as $s) {
            $id_servicio = (int)$s['id_servicio'];
            $precio      = (float)$s['precio'];
            $stmt->bind_param("iid", $id_cita, $id_servicio, $precio);
            $stmt->execute();
        }
        $stmt->close();

        $conexion->commit();
        echo json_encode(["estado" => true, "mensaje" => "¡Cita actualizada para el " . date('d/m/Y', $inicio) . " a las " . date('H:i', $inicio) . "!"], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        $conexion->rollback();
        echo json_encode(["estado" => false, "mensaje" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

if (!in_array($opcion, ["catalogo", "disponibilidad", "agendar", "modificar", "historial", "cancelar"], true)) {
    echo json_encode(["estado" => false, "mensaje" => "Opción no válida."], JSON_UNESCAPED_UNICODE);
}

