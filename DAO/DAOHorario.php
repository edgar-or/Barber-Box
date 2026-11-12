<?php
 
$opcion = $_REQUEST['opcion'] ?? '';
$id_empleado = (int)($_POST['id_empleado'] ?? 0);
 
if ($opcion == "consultar") {
    require_once "../config/conexion.php";
    $stmt = $conexion->prepare("SELECT dia_semana, hora_entrada, hora_salida FROM horario_empleado WHERE id_empleado = ?");
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
    $entrada = $_POST['hora_entrada'] ?? '';
    $salida = $_POST['hora_salida'] ?? '';
 
    // Validar que no exista ya ese día para ese empleado
    $check = $conexion->prepare("SELECT id_horario FROM horario_empleado WHERE id_empleado = ? AND dia_semana = ?");
    $check->bind_param("is", $id_empleado, $dia);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(["estado" => false, "mensaje" => "Ya existe un horario para el día " . $dia]);
        exit;
    }
    $check->close();
 
    // dia_semana es texto: "isss" (int, string, string, string)
    $stmt = $conexion->prepare("INSERT INTO horario_empleado (id_empleado, dia_semana, hora_entrada, hora_salida) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $id_empleado, $dia, $entrada, $salida);
 
    if ($stmt->execute()) {
        echo json_encode(["estado" => true, "mensaje" => "Guardado"]);
    } else {
        echo json_encode(["estado" => false, "mensaje" => "Error: " . $stmt->error]);
    }
    $stmt->close();
    $conexion->close();
    exit;
}
 
if ($opcion == "modificar") {
    require_once "../config/conexion.php";
 
    $dia = $_POST['dia_semana'] ?? '';
    $entrada = $_POST['hora_entrada'] ?? '';
    $salida = $_POST['hora_salida'] ?? '';
 
    // "ssis" (string, string, int, string)
    $stmt = $conexion->prepare("UPDATE horario_empleado SET hora_entrada = ?, hora_salida = ? WHERE id_empleado = ? AND dia_semana = ?");
    $stmt->bind_param("ssis", $entrada, $salida, $id_empleado, $dia);
 
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
 
    $stmt = $conexion->prepare("DELETE FROM horario_empleado WHERE id_empleado = ? AND dia_semana = ?");
    $stmt->bind_param("is", $id_empleado, $dia);
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
 