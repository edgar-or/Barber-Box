<?php
// Gestión de citas para el personal y catálogo para clientes.
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/conexion.php";

header('Content-Type: application/json; charset=utf-8');

const PASO_MINUTOS = 15;

function responder(array $datos): void {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

$opcion = $_POST['opcion'] ?? ($_GET['opcion'] ?? '');

// Si la opción es "catalogo", permitimos a cualquier sesión activa; para el resto exigimos empleado
if ($opcion === "catalogo") {
    if (!sesionActiva()) {
        responder(["estado" => false, "mensaje" => "Sesión no iniciada."]);
    }
} else {
    requerirEmpleadoJson();
}

$esAdmin = isset($_SESSION['id_rol']) && (int)$_SESSION['id_rol'] === ROL_ADMIN;

// Para un barbero: su id_empleado (las consultas se limitan a él)
$idEmpleadoSesion = 0;
if ($opcion !== "catalogo" && !$esAdmin) {
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
    if ($opcion === "catalogo") {
        $sqlServicios = "SELECT id_servicio, nombre_servicio, descripcion, precio, duracion_estimada
                         FROM servicio
                         WHERE estado_servicio = 'A'
                         ORDER BY nombre_servicio";
        $resServicios = $conexion->query($sqlServicios);
        if (!$resServicios) {
            responder(["estado" => false, "mensaje" => "Error al obtener servicios."]);
        }

        $servicios = [];
        while ($fila = $resServicios->fetch_assoc()) {
            $idServ = (int)$fila['id_servicio'];
            $imagenes = [];

            // Obtener todas las imágenes asociadas al servicio
            try {
                $stmtImg = $conexion->prepare("SELECT ruta_imagen FROM servicio_imagen WHERE id_servicio = ? ORDER BY id_servicio ASC");
                $stmtImg->bind_param("i", $idServ);
                $stmtImg->execute();
                $resImg = $stmtImg->get_result();
                while ($img = $resImg->fetch_assoc()) {
                    if (!empty($img['ruta_imagen'])) {
                        $imagenes[] = $img['ruta_imagen'];
                    }
                }
                $stmtImg->close();
            } catch (Throwable $e) {
                $imagenes = [];
            }

            $fila['imagenes'] = $imagenes;
            $servicios[] = $fila;
        }
        $resServicios->free();

        $barberos = [];
        $resBarberos = $conexion->query("SELECT id_empleado, CONCAT(nombre, ' ', apellido) AS nombre FROM empleado ORDER BY nombre");
        if ($resBarberos) {
            while ($b = $resBarberos->fetch_assoc()) {
                $barberos[] = $b;
            }
            $resBarberos->free();
        }

        responder([
            "estado" => true,
            "servicios" => $servicios,
            "barberos" => $barberos
        ]);
    }

    if ($opcion === "consultar") {
        $sql = "SELECT c.id_cita, c.fecha_hora_inicio, c.fecha_hora_fin, c.total_cita, c.estado_cita,
                       c.nota, c.id_empleado,
                       CONCAT(cl.nombre, ' ', cl.apellido) AS cliente, cl.telefono,
                       CONCAT(e.nombre, ' ', e.apellido) AS barbero,
                       (SELECT GROUP_CONCAT(s.nombre_servicio SEPARATOR ', ')
                          FROM detalle_cita d JOIN servicio s ON s.id_servicio = d.id_servicio
                         WHERE d.id_cita = c.id_cita) AS servicios,
                       (SELECT GROUP_CONCAT(d.id_servicio)
                          FROM detalle_cita d
                         WHERE d.id_cita = c.id_cita) AS servicios_ids
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
            // Se incluye id_servicio para poder precargar el formulario de modificación
            $stmt = $conexion->prepare("SELECT d.id_servicio, s.nombre_servicio, s.duracion_estimada, d.precio_unitario
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

    // Horas libres para modificar una cita (se ignora la propia cita)
    if ($opcion === "disponibilidad") {
        $idCita       = (int)($_POST['id_cita'] ?? 0);
        $idEmpleado   = (int)($_POST['id_empleado'] ?? 0);
        $fecha        = $_POST['fecha'] ?? '';
        $serviciosIds = $_POST['servicios'] ?? [];

        // Solo los barberos modifican citas, y solo en su propia agenda
        if ($esAdmin) {
            responder(["estado" => false, "mensaje" => "Solo los barberos pueden modificar citas."]);
        }
        $idEmpleado = $idEmpleadoSesion;

        $servicios = obtenerServicios($conexion, $serviciosIds);
        $totales   = sumarServicios($servicios);
        $horas     = calcularDisponibilidad($conexion, $idEmpleado, $fecha, $totales['duracion'], $idCita, true);

        responder(["estado" => true, "horas" => $horas, "duracion" => $totales['duracion']]);
    }

    // Modificar los servicios de una cita pendiente de hoy en adelante (solo barberos, y solo las suyas).
    if ($opcion === "modificar") {
        $idCita       = (int)($_POST['id_cita'] ?? 0);
        $serviciosIds = $_POST['servicios'] ?? [];

        if ($esAdmin) {
            responder(["estado" => false, "mensaje" => "Solo los barberos pueden modificar citas."]);
        }

        $conexion->begin_transaction();

        try {
            // La cita debe ser suya, pendiente y de hoy en adelante (se bloquea la fila)
            $sql = "SELECT id_cita, fecha_hora_inicio FROM cita
                    WHERE id_cita = ? AND id_empleado = ? AND estado_cita = 'Pendiente' AND fecha_hora_inicio >= CURDATE() FOR UPDATE";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ii", $idCita, $idEmpleadoSesion);
            $stmt->execute();
            $citaActual = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$citaActual) {
                throw new Exception("Esta cita ya no se puede modificar.");
            }

            $servicios = obtenerServicios($conexion, $serviciosIds);
            if (count($servicios) === 0) {
                throw new Exception("La cita debe tener al menos un servicio.");
            }

            $totales  = sumarServicios($servicios);
            $duracion = $totales['duracion'];
            $total    = $totales['total'];

            // Bloquea la fila del barbero para evitar choques con otras reservas simultáneas
            $stmt = $conexion->prepare("SELECT id_empleado FROM empleado WHERE id_empleado = ? FOR UPDATE");
            $stmt->bind_param("i", $idEmpleadoSesion);
            $stmt->execute();
            $stmt->get_result();
            $stmt->close();

            // La hora de inicio no cambia: debe seguir libre con la nueva duración
            $inicio = strtotime($citaActual['fecha_hora_inicio']);
            $fecha  = date('Y-m-d', $inicio);
            $hora   = date('H:i', $inicio);

            // Se incluyen horas pasadas: la cita puede estar en curso
            $disponibles = calcularDisponibilidad($conexion, $idEmpleadoSesion, $fecha, $duracion, $idCita, true);
            if (!in_array($hora, $disponibles, true)) {
                throw new Exception("Con esos servicios la cita choca con otra cita o se pasa del horario del barbero.");
            }

            $finSql = date('Y-m-d H:i:s', $inicio + $duracion * 60);

            $stmt = $conexion->prepare("UPDATE cita SET fecha_hora_fin = ?, total_cita = ? WHERE id_cita = ?");
            $stmt->bind_param("sdi", $finSql, $total, $idCita);
            if (!$stmt->execute()) {
                throw new Exception("Error al modificar la cita: " . $stmt->error);
            }
            $stmt->close();

            // Se reemplazan los servicios de la cita
            $stmt = $conexion->prepare("DELETE FROM detalle_cita WHERE id_cita = ?");
            $stmt->bind_param("i", $idCita);
            $stmt->execute();
            $stmt->close();

            $stmt = $conexion->prepare("INSERT INTO detalle_cita (id_cita, id_servicio, precio_unitario) VALUES (?, ?, ?)");
            foreach ($servicios as $s) {
                $idServicio = (int)$s['id_servicio'];
                $precio     = (float)$s['precio'];
                $stmt->bind_param("iid", $idCita, $idServicio, $precio);
                $stmt->execute();
            }
            $stmt->close();

            $conexion->commit();
            responder(["estado" => true, "mensaje" => "Servicios de la cita actualizados. Ahora termina a las " . date('H:i', $inicio + $duracion * 60)]);
        } catch (Throwable $e) {
            $conexion->rollback();
            responder(["estado" => false, "mensaje" => $e->getMessage()]);
        }
    }

    responder(["estado" => false, "mensaje" => "Opción no válida."]);
} catch (Throwable $e) {
    responder(["estado" => false, "mensaje" => "Error en el servidor: " . $e->getMessage()]);
}


// ================== FUNCIONES ==================

// Servicios activos seleccionados (precio y duración salen de la BD)
function obtenerServicios($conexion, $ids)
{
    $servicios = [];
    $vistos = [];

    if (!is_array($ids)) {
        return $servicios;
    }

    $stmt = $conexion->prepare("SELECT id_servicio, precio, duracion_estimada FROM servicio WHERE id_servicio = ? AND estado_servicio = 'A'");

    foreach ($ids as $valor) {
        $id = intval($valor);

        if ($id <= 0 || in_array($id, $vistos)) {
            continue;
        }
        $vistos[] = $id;

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($fila = $resultado->fetch_assoc()) {
            $servicios[] = $fila;
        }
    }
    $stmt->close();

    return $servicios;
}

// Suma la duración y el precio de los servicios elegidos
function sumarServicios($servicios)
{
    $duracion = 0;
    $total = 0;

    foreach ($servicios as $s) {
        $duracion += intval($s['duracion_estimada']);
        $total += floatval($s['precio']);
    }

    return ["duracion" => $duracion, "total" => $total];
}

// Horas de inicio disponibles ("HH:MM") para un barbero, fecha y duración
function calcularDisponibilidad($conexion, $id_empleado, $fecha, $duracion, $excluir_cita, $incluirPasadas = false)
{
    $partes = explode('-', $fecha);

    if (strlen($fecha) != 10 || count($partes) != 3 || !checkdate(intval($partes[1]), intval($partes[2]), intval($partes[0])) || $duracion <= 0) {
        return [];
    }

    $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
    $dia_buscado = $dias[date('N', strtotime($fecha)) - 1];

    // Horario del barbero para ese día de la semana
    $stmt = $conexion->prepare("SELECT dia_semana, hora_entrada, hora_salida FROM horario_empleado WHERE id_empleado = ?");
    $stmt->bind_param("i", $id_empleado);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $horario = null;

    while ($fila = $resultado->fetch_assoc()) {
        $dia = strtolower(strtr(trim($fila['dia_semana']), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u']));

        if ($dia == $dia_buscado) {
            $horario = $fila;
            break;
        }
    }
    $stmt->close();

    if (!$horario) {
        return [];
    }

    // Citas pendientes del barbero ese día (se ignora la cita que se está modificando)
    $inicio_dia = $fecha . ' 00:00:00';
    $fin_dia    = $fecha . ' 23:59:59';

    $stmt = $conexion->prepare("SELECT fecha_hora_inicio, fecha_hora_fin FROM cita
                                WHERE id_empleado = ? AND estado_cita = 'Pendiente' AND id_cita <> ?
                                AND fecha_hora_inicio BETWEEN ? AND ?");
    $stmt->bind_param("iiss", $id_empleado, $excluir_cita, $inicio_dia, $fin_dia);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $ocupadas = [];

    while ($fila = $resultado->fetch_assoc()) {
        $ocupadas[] = $fila;
    }
    $stmt->close();

    // Se recorre el horario de 30 en 30 minutos descartando horas pasadas u ocupadas
    $horas  = [];
    $ahora  = time();
    $cursor = strtotime($fecha . " " . $horario['hora_entrada']);
    $salida = strtotime($fecha . " " . $horario['hora_salida']);

    while ($cursor + $duracion * 60 <= $salida) {
        $fin = $cursor + $duracion * 60;

        if ($incluirPasadas || $cursor > $ahora) {
            $libre = true;

            foreach ($ocupadas as $o) {
                if (strtotime($o['fecha_hora_inicio']) < $fin && strtotime($o['fecha_hora_fin']) > $cursor) {
                    $libre = false;
                    break;
                }
            }

            if ($libre) {
                $horas[] = date('H:i', $cursor);
            }
        }

        $cursor += PASO_MINUTOS * 60;
    }

    return $horas;
}