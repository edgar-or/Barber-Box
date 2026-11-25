<?php
// Agenda de citas para empleados y administradores (pueden agendar para cualquier cliente)
require_once __DIR__ . "/../config/auth.php";
requerirEmpleadoJson();

header('Content-Type: application/json; charset=utf-8');

const PASO_MINUTOS = 15;

$opcion        = isset($_REQUEST["opcion"]) ? $_REQUEST["opcion"] : "";
$id_cita       = intval($_POST['id_cita'] ?? 0);
$id_cliente    = intval($_POST['id_cliente'] ?? 0);
$id_empleado   = intval($_POST['id_empleado'] ?? 0);
$fecha         = $_POST['fecha'] ?? '';
$hora          = $_POST['hora'] ?? '';
$nota          = trim($_POST['nota'] ?? '');
$buscar        = trim($_POST['q'] ?? '');
$servicios_ids = $_POST['servicios'] ?? [];
$filtro_fecha  = $_POST['filtro_fecha'] ?? '';
$filtro_emp    = intval($_POST['filtro_empleado'] ?? 0);


if ($opcion == "consultarServicios") {
    require_once "../config/conexion.php";

    try {
        $sql = "SELECT id_servicio, nombre_servicio, descripcion, precio, duracion_estimada
                FROM servicio
                WHERE estado_servicio = 'A'
                ORDER BY nombre_servicio";

        $resultado = $conexion->query($sql);

        if (!$resultado) {
            throw new Exception("Error al consultar los servicios");
        }

        $servicios = [];

        while ($fila = $resultado->fetch_assoc()) {
            $servicios[] = $fila;
        }

        echo json_encode($servicios, JSON_UNESCAPED_UNICODE);

        $resultado->free();
    } catch (Throwable $e) {
        echo json_encode([
            "mensaje" => "No se pudieron obtener los servicios"
        ], JSON_UNESCAPED_UNICODE);
    }
    $conexion->close();
}

if ($opcion == "consultarBarberos") {
    require_once "../config/conexion.php";

    try {
        $sql = "SELECT id_empleado, CONCAT(nombre, ' ', apellido) AS nombre
                FROM empleado
                WHERE atiende_citas = 1
                ORDER BY nombre";

        $resultado = $conexion->query($sql);

        if (!$resultado) {
            throw new Exception("Error al consultar los barberos");
        }

        $barberos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $barberos[] = $fila;
        }

        echo json_encode($barberos, JSON_UNESCAPED_UNICODE);

        $resultado->free();
    } catch (Throwable $e) {
        echo json_encode([
            "mensaje" => "No se pudieron obtener los barberos"
        ], JSON_UNESCAPED_UNICODE);
    }
    $conexion->close();
}

if ($opcion == "consultarClientes") {
    require_once "../config/conexion.php";

    try {
        $clientes = [];

        if (strlen($buscar) >= 2) {
            $like = "%" . $buscar . "%";

            $sql = "SELECT id_cliente, CONCAT(nombre, ' ', apellido) AS nombre
                    FROM cliente
                    WHERE CONCAT(nombre, ' ', apellido) LIKE ?
                    ORDER BY nombre
                    LIMIT 10";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("s", $like);
            $stmt->execute();
            $resultado = $stmt->get_result();

            while ($fila = $resultado->fetch_assoc()) {
                $clientes[] = $fila;
            }
            $stmt->close();
        }

        echo json_encode($clientes, JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode([
            "mensaje" => "No se pudieron obtener los clientes"
        ], JSON_UNESCAPED_UNICODE);
    }
    $conexion->close();
}

if ($opcion == "consultarDisponibilidad") {
    require_once "../config/conexion.php";

    try {
        $servicios = obtenerServicios($conexion, $servicios_ids);
        $totales   = sumarServicios($servicios);
        $horas     = calcularDisponibilidad($conexion, $id_empleado, $fecha, $totales['duracion'], $id_cita);

        echo json_encode($horas, JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode([
            "mensaje" => "No se pudo consultar la disponibilidad"
        ], JSON_UNESCAPED_UNICODE);
    }
    $conexion->close();
}

if ($opcion == "guardar") {
    require_once "../config/conexion.php";

    $conexion->begin_transaction();

    try {
        if ($id_cliente <= 0 || !clienteExiste($conexion, $id_cliente)) {
            throw new Exception("Selecciona un cliente válido.");
        }

        if ($fecha == "" || $hora == "") {
            throw new Exception("Selecciona la fecha y la hora de la cita.");
        }

        $servicios = obtenerServicios($conexion, $servicios_ids);
        if (count($servicios) == 0) {
            throw new Exception("Selecciona al menos un servicio.");
        }

        $totales  = sumarServicios($servicios);
        $duracion = $totales['duracion'];
        $total    = $totales['total'];

        // Valida el barbero y bloquea su fila: dos reservas no pueden tomar la misma hora a la vez
        $sql = "SELECT id_empleado FROM empleado WHERE id_empleado = ? AND atiende_citas = 1 FOR UPDATE";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_empleado);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $existe_barbero = $resultado->num_rows > 0;
        $stmt->close();

        if (!$existe_barbero) {
            throw new Exception("Selecciona un barbero válido.");
        }

        $disponibles = calcularDisponibilidad($conexion, $id_empleado, $fecha, $duracion, 0);
        if (!in_array($hora, $disponibles)) {
            throw new Exception("Ese horario ya no está disponible. Elige otro.");
        }

        $inicio     = strtotime($fecha . " " . $hora);
        $inicio_sql = date('Y-m-d H:i:s', $inicio);
        $fin_sql    = date('Y-m-d H:i:s', $inicio + $duracion * 60);

        $sql = "INSERT INTO cita (fecha_hora_inicio, fecha_hora_fin, total_cita, estado_cita, nota, id_cliente, id_empleado)
                VALUES (?, ?, ?, 'Pendiente', ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssdsii", $inicio_sql, $fin_sql, $total, $nota, $id_cliente, $id_empleado);

        if (!$stmt->execute()) {
            throw new Exception("Error al guardar: " . $stmt->error);
        }
        $id_nueva_cita = $conexion->insert_id;
        $stmt->close();

        $sql = "INSERT INTO detalle_cita (id_cita, id_servicio, precio_unitario) VALUES (?, ?, ?)";
        $stmt = $conexion->prepare($sql);

        foreach ($servicios as $s) {
            $id_servicio = intval($s['id_servicio']);
            $precio      = floatval($s['precio']);
            $stmt->bind_param("iid", $id_nueva_cita, $id_servicio, $precio);
            $stmt->execute();
        }
        $stmt->close();

        $conexion->commit();

        echo json_encode([
            "estado" => true,
            "mensaje" => "Cita agendada para el " . date('d/m/Y', $inicio) . " a las " . date('H:i', $inicio)
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        $conexion->rollback();
        echo json_encode([
            "estado" => false,
            "mensaje" => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    $conexion->close();
}

if ($opcion == "modificar") {
    require_once "../config/conexion.php";

    $conexion->begin_transaction();

    try {
        if ($fecha == "" || $hora == "") {
            throw new Exception("Selecciona la fecha y la hora de la cita.");
        }

        // La cita debe estar pendiente y ser futura (se bloquea la fila). El cliente no cambia.
        $sql = "SELECT id_cita FROM cita
                WHERE id_cita = ? AND estado_cita = 'Pendiente' AND fecha_hora_inicio > NOW() FOR UPDATE";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_cita);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $es_modificable = $resultado->num_rows > 0;
        $stmt->close();

        if (!$es_modificable) {
            throw new Exception("Esta cita ya no se puede modificar.");
        }

        $servicios = obtenerServicios($conexion, $servicios_ids);
        if (count($servicios) == 0) {
            throw new Exception("Selecciona al menos un servicio.");
        }

        $totales  = sumarServicios($servicios);
        $duracion = $totales['duracion'];
        $total    = $totales['total'];

        $sql = "SELECT id_empleado FROM empleado WHERE id_empleado = ? AND atiende_citas = 1 FOR UPDATE";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_empleado);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $existe_barbero = $resultado->num_rows > 0;
        $stmt->close();

        if (!$existe_barbero) {
            throw new Exception("Selecciona un barbero válido.");
        }

        // Disponibilidad ignorando la propia cita
        $disponibles = calcularDisponibilidad($conexion, $id_empleado, $fecha, $duracion, $id_cita);
        if (!in_array($hora, $disponibles)) {
            throw new Exception("Ese horario ya no está disponible. Elige otro.");
        }

        $inicio     = strtotime($fecha . " " . $hora);
        $inicio_sql = date('Y-m-d H:i:s', $inicio);
        $fin_sql    = date('Y-m-d H:i:s', $inicio + $duracion * 60);

        $sql = "UPDATE cita SET fecha_hora_inicio = ?, fecha_hora_fin = ?, total_cita = ?, nota = ?, id_empleado = ?
                WHERE id_cita = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssdsii", $inicio_sql, $fin_sql, $total, $nota, $id_empleado, $id_cita);

        if (!$stmt->execute()) {
            throw new Exception("Error al modificar: " . $stmt->error);
        }
        $stmt->close();

        // Se reemplazan los servicios de la cita
        $sql = "DELETE FROM detalle_cita WHERE id_cita = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_cita);
        $stmt->execute();
        $stmt->close();

        $sql = "INSERT INTO detalle_cita (id_cita, id_servicio, precio_unitario) VALUES (?, ?, ?)";
        $stmt = $conexion->prepare($sql);

        foreach ($servicios as $s) {
            $id_servicio = intval($s['id_servicio']);
            $precio      = floatval($s['precio']);
            $stmt->bind_param("iid", $id_cita, $id_servicio, $precio);
            $stmt->execute();
        }
        $stmt->close();

        $conexion->commit();

        echo json_encode([
            "estado" => true,
            "mensaje" => "Cita modificada para el " . date('d/m/Y', $inicio) . " a las " . date('H:i', $inicio)
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        $conexion->rollback();
        echo json_encode([
            "estado" => false,
            "mensaje" => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    $conexion->close();
}

if ($opcion == "cancelar") {
    require_once "../config/conexion.php";

    try {
        // Solo cancela citas pendientes y futuras
        $sql = "UPDATE cita SET estado_cita = 'Cancelada'
                WHERE id_cita = ? AND estado_cita = 'Pendiente' AND fecha_hora_inicio > NOW()";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_cita);
        $stmt->execute();
        $afectadas = $stmt->affected_rows;
        $stmt->close();

        if ($afectadas > 0) {
            echo json_encode([
                "estado" => true,
                "mensaje" => "Cita Cancelada Correctamente"
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                "estado" => false,
                "mensaje" => "No se pudo cancelar la cita"
            ], JSON_UNESCAPED_UNICODE);
        }
    } catch (Throwable $e) {
        echo json_encode([
            "estado" => false,
            "mensaje" => "Error al cancelar la cita"
        ], JSON_UNESCAPED_UNICODE);
    }
    $conexion->close();
}

if ($opcion == "consultar") {
    require_once "../config/conexion.php";

    // Agenda general: sin fecha muestra de hoy en adelante
    try {
        $sql = "SELECT c.id_cita, c.fecha_hora_inicio, c.total_cita, c.estado_cita,
                       CONCAT(cl.nombre, ' ', cl.apellido) AS cliente,
                       CONCAT(e.nombre, ' ', e.apellido) AS barbero,
                       (SELECT GROUP_CONCAT(s.nombre_servicio SEPARATOR ', ')
                          FROM detalle_cita d
                         INNER JOIN servicio s ON s.id_servicio = d.id_servicio
                         WHERE d.id_cita = c.id_cita) AS servicios
                FROM cita c
                INNER JOIN empleado e ON e.id_empleado = c.id_empleado
                INNER JOIN cliente cl ON cl.id_cliente = c.id_cliente
                WHERE ((? = '' AND c.fecha_hora_inicio >= CURDATE()) OR DATE(c.fecha_hora_inicio) = ?)
                AND (? = 0 OR c.id_empleado = ?)
                ORDER BY c.fecha_hora_inicio ASC";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssii", $filtro_fecha, $filtro_fecha, $filtro_emp, $filtro_emp);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $registros = [];

        while ($fila = $resultado->fetch_assoc()) {
            $registros[] = [
                'id_cita' => $fila['id_cita'],
                'fecha_hora_inicio' => $fila['fecha_hora_inicio'],
                'cliente' => $fila['cliente'],
                'servicios' => $fila['servicios'],
                'barbero' => $fila['barbero'],
                'total_cita' => number_format($fila['total_cita'], 2, '.', ','),
                'estado_cita' => $fila['estado_cita']
            ];
        }

        echo json_encode($registros, JSON_UNESCAPED_UNICODE);

        $stmt->close();
    } catch (Throwable $e) {
        echo json_encode([
            "mensaje" => "No se pudieron obtener los registros"
        ], JSON_UNESCAPED_UNICODE);
    }
    $conexion->close();
}

if ($opcion == "consultarIndividual") {
    require_once "../config/conexion.php";

    try {
        $sql = "SELECT c.id_cita, c.fecha_hora_inicio, c.nota, c.id_cliente, c.id_empleado,
                       CONCAT(cl.nombre, ' ', cl.apellido) AS cliente,
                       (SELECT GROUP_CONCAT(d.id_servicio)
                          FROM detalle_cita d
                         WHERE d.id_cita = c.id_cita) AS servicios_ids
                FROM cita c
                INNER JOIN cliente cl ON cl.id_cliente = c.id_cliente
                WHERE c.id_cita = ?";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_cita);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            echo json_encode([
                "estado" => true,
                "datos" => [
                    'id_cita' => $fila['id_cita'],
                    'fecha_hora_inicio' => $fila['fecha_hora_inicio'],
                    'nota' => $fila['nota'],
                    'id_cliente' => $fila['id_cliente'],
                    'id_empleado' => $fila['id_empleado'],
                    'cliente' => $fila['cliente'],
                    'servicios_ids' => $fila['servicios_ids']
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                "estado" => false,
                "datos" => "No Existe"
            ], JSON_UNESCAPED_UNICODE);
        }

        $stmt->close();
    } catch (Throwable $e) {
        echo json_encode([
            "estado" => false,
            "datos" => "Error al consultar la cita"
        ], JSON_UNESCAPED_UNICODE);
    }
    $conexion->close();
}

if (!in_array($opcion, ["consultarServicios", "consultarBarberos", "consultarClientes", "consultarDisponibilidad", "guardar", "modificar", "cancelar", "consultar", "consultarIndividual"])) {
    echo json_encode([
        "estado" => false,
        "mensaje" => "Opción no válida."
    ], JSON_UNESCAPED_UNICODE);
}


// ================== FUNCIONES ==================

// Servicios activos seleccionados (precio y duración)
function obtenerServicios($conexion, $ids)
{
    $servicios = [];
    $vistos = [];

    if (!is_array($ids)) {
        return $servicios;
    }

    $sql = "SELECT id_servicio, precio, duracion_estimada FROM servicio WHERE id_servicio = ? AND estado_servicio = 'A'";
    $stmt = $conexion->prepare($sql);

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

// Verifica que el cliente exista
function clienteExiste($conexion, $id_cliente)
{
    $sql = "SELECT id_cliente FROM cliente WHERE id_cliente = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $existe = $resultado->num_rows > 0;
    $stmt->close();

    return $existe;
}

// Horas de inicio disponibles ("HH:MM") para un barbero, fecha y duración
function calcularDisponibilidad($conexion, $id_empleado, $fecha, $duracion, $excluir_cita)
{
    $partes = explode('-', $fecha);

    if (strlen($fecha) != 10 || count($partes) != 3 || !checkdate(intval($partes[1]), intval($partes[2]), intval($partes[0])) || $duracion <= 0) {
        return [];
    }

    $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
    $dia_buscado = $dias[date('N', strtotime($fecha)) - 1];

    // 1. Traemos TODOS los horarios del barbero
    $sql = "SELECT dia_semana, hora_entrada, hora_salida FROM horario_empleado WHERE id_empleado = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_empleado);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $turnosDelDia = [];

    while ($fila = $resultado->fetch_assoc()) {
        $dia = strtolower(strtr(trim($fila['dia_semana']), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u']));

        if ($dia == $dia_buscado) {
            $turnosDelDia[] = $fila; // Guardamos TODOS los turnos (mañana y tarde)
        }
    }
    $stmt->close();

    if (empty($turnosDelDia)) {
        return [];
    }

    // Citas pendientes del barbero ese día (se ignora la cita que se está modificando)
    $inicio_dia = $fecha . ' 00:00:00';
    $fin_dia    = $fecha . ' 23:59:59';

    $sql = "SELECT fecha_hora_inicio, fecha_hora_fin FROM cita
            WHERE id_empleado = ? AND estado_cita = 'Pendiente' AND id_cita <> ?
            AND fecha_hora_inicio BETWEEN ? AND ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iiss", $id_empleado, $excluir_cita, $inicio_dia, $fin_dia);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $ocupadas = [];

    while ($fila = $resultado->fetch_assoc()) {
        $ocupadas[] = $fila;
    }
    $stmt->close();

    $horas  = [];
    $ahora  = time();

    // Recorremos CADA turno (mañana y tarde)
    foreach ($turnosDelDia as $horario) {
        $cursor = strtotime($fecha . " " . $horario['hora_entrada']);
        $salida = strtotime($fecha . " " . $horario['hora_salida']);

        while ($cursor + $duracion * 60 <= $salida) {
            $fin = $cursor + $duracion * 60;

            if ($cursor > $ahora) {
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
    }

    $horas = array_unique($horas);
    sort($horas);

    return array_values($horas);
}