<?php

require_once __DIR__ . "/../config/auth.php";
requerirEmpleadoJson();

$opcion = $_REQUEST['opcion'] ?? '';
$id_empleado = (int)($_POST['id_empleado'] ?? 0);

// true si el rango entrada-salida se traslapa con otro turno del mismo día.
// $exEntrada / $exSalida: turno que se excluye (el que se está modificando).
function hayTraslape($conexion, $id_empleado, $dia, $entrada, $salida, $exEntrada = null, $exSalida = null) {
    if ($exEntrada !== null && $exSalida !== null) {
        $sql = "SELECT id_horario FROM horario_empleado
                WHERE id_empleado = ? AND dia_semana = ?
                AND hora_entrada < ? AND hora_salida > ?
                AND NOT (hora_entrada = ? AND hora_salida = ?)";
        $q = $conexion->prepare($sql);
        $q->bind_param("isssss", $id_empleado, $dia, $salida, $entrada, $exEntrada, $exSalida);
    } else {
        $sql = "SELECT id_horario FROM horario_empleado
                WHERE id_empleado = ? AND dia_semana = ?
                AND hora_entrada < ? AND hora_salida > ?";
        $q = $conexion->prepare($sql);
        $q->bind_param("isss", $id_empleado, $dia, $salida, $entrada);
    }
    $q->execute();
    $existe = $q->get_result()->num_rows > 0;
    $q->close();
    return $existe;
}

if ($opcion == "consultar") {
    require_once "../config/conexion.php";
    $stmt = $conexion->prepare("SELECT dia_semana, hora_entrada, hora_salida FROM horario_empleado WHERE id_empleado = ? ORDER BY FIELD(dia_semana,'lunes','martes','miercoles','jueves','viernes','sabado','domingo'), hora_entrada");
    $stmt->bind_param("i", $id_empleado);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $horarios = [];
    while ($fila = $resultado->fetch_assoc()) {
        $horarios[] = $fila;
    }
    echo json_encode($horarios);
    $stmt->close();
    $conexion->close();
    exit;
}

if ($opcion == "guardar_uno") {
    require_once "../config/conexion.php";

    $dia = $_POST['dia_semana'] ?? '';
    $turnos = json_decode($_POST['turnos'] ?? '[]', true);

    if (empty($turnos)) {
        echo json_encode(["estado" => false, "mensaje" => "No se enviaron turnos válidos"]);
        exit;
    }

    $conexion->begin_transaction();
    $stmt = $conexion->prepare("INSERT INTO horario_empleado (id_empleado, dia_semana, hora_entrada, hora_salida) VALUES (?, ?, ?, ?)");

    foreach ($turnos as $turno) {
        $entrada = $turno['entrada'] ?? '';
        $salida = $turno['salida'] ?? '';

        if ($entrada === '' || $salida === '' || $entrada >= $salida) {
            $conexion->rollback();
            echo json_encode(["estado" => false, "mensaje" => "La hora de salida debe ser mayor que la de entrada"]);
            exit;
        }

        if (hayTraslape($conexion, $id_empleado, $dia, $entrada, $salida)) {
            $conexion->rollback();
            echo json_encode(["estado" => false, "mensaje" => "El turno $entrada - $salida se traslapa con otro turno del $dia"]);
            exit;
        }

        $stmt->bind_param("isss", $id_empleado, $dia, $entrada, $salida);
        if (!$stmt->execute()) {
            $conexion->rollback();
            echo json_encode(["estado" => false, "mensaje" => "Error: " . $stmt->error]);
            exit;
        }
    } // <-- aquí se cierra el foreach (era la llave que faltaba)

    $conexion->commit();
    $stmt->close();
    $conexion->close();

    echo json_encode(["estado" => true, "mensaje" => "Horario guardado correctamente"]);
    exit;
}

if ($opcion == "modificar") {
    require_once "../config/conexion.php";

    $dia = $_POST['dia_semana'] ?? '';
    $entrada = $_POST['hora_entrada'] ?? '';
    $salida = $_POST['hora_salida'] ?? '';
    $origEntrada = $_POST['hora_original_entrada'] ?? '';
    $origSalida = $_POST['hora_original_salida'] ?? '';

    if ($entrada === '' || $salida === '' || $entrada >= $salida) {
        echo json_encode(["estado" => false, "mensaje" => "La hora de salida debe ser mayor que la de entrada"]);
        exit;
    }

    // Se excluye el propio turno que se está editando
    if (hayTraslape($conexion, $id_empleado, $dia, $entrada, $salida, $origEntrada, $origSalida)) {
        echo json_encode(["estado" => false, "mensaje" => "El nuevo horario se traslapa con otro turno del $dia"]);
        exit;
    }

    $stmt = $conexion->prepare("UPDATE horario_empleado SET hora_entrada = ?, hora_salida = ? WHERE id_empleado = ? AND dia_semana = ? AND hora_entrada = ? AND hora_salida = ?");
    $stmt->bind_param("ssisss", $entrada, $salida, $id_empleado, $dia, $origEntrada, $origSalida);

    if ($stmt->execute()) {
        echo json_encode(["estado" => true, "mensaje" => "Horario modificado correctamente"]);
    } else {
        echo json_encode(["estado" => false, "mensaje" => "Error al modificar: " . $stmt->error]);
    }
    $stmt->close();
    $conexion->close();
    exit;
}

if ($opcion == "eliminar") {
    require_once "../config/conexion.php";

    $dia = $_POST['dia_semana'] ?? '';
    $entrada = $_POST['hora_entrada'] ?? '';
    $salida = $_POST['hora_salida'] ?? '';

    $stmt = $conexion->prepare("DELETE FROM horario_empleado WHERE id_empleado = ? AND dia_semana = ? AND hora_entrada = ? AND hora_salida = ?");
    $stmt->bind_param("isss", $id_empleado, $dia, $entrada, $salida);
    if ($stmt->execute()) {
        echo json_encode(["estado" => true]);
    } else {
        echo json_encode(["estado" => false]);
    }
    $stmt->close();
    $conexion->close();
    exit;
}
?>