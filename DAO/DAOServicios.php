<?php

$nombre_servicio   = $_POST['nombre'] ?? '';
$descripcion       = $_POST['descripcion'] ?? '';
$precio            = $_POST['precio'] ?? 0.00;
$duracion_estimada = $_POST['duracion'] ?? 0;
$opcion = isset($_REQUEST["opcion"]) ? $_REQUEST["opcion"] : "";

if ($opcion == "guardar") {
    require_once "../config/conexion.php";
    $conexion->begin_transaction();

    try {
        $estado_servicio = 'A'; // Valor por defecto según la BD

        $sqlServicio = "INSERT INTO servicio (nombre_servicio, descripcion, precio, duracion_estimada, estado_servicio) VALUES (?, ?, ?, ?, ?)";
        $stmtServ = $conexion->prepare($sqlServicio);
        $stmtServ->bind_param("ssdis", $nombre_servicio, $descripcion, $precio, $duracion_estimada, $estado_servicio);

        if (!$stmtServ->execute()) {
            throw new Exception("Error al guardar el servicio: " . $stmtServ->error);
        }

        $stmtServ->close();
        $conexion->commit();

        echo json_encode([
            "estado" => true,
            "mensaje" => "Servicio registrado correctamente"
        ]);
    } catch (Throwable $e) {
        $conexion->rollback();
        echo json_encode([
            "estado" => false,
            "mensaje" => $e->getMessage()
        ]);
    } finally {
        $conexion->close();
    }
}

if ($opcion == "modificar") {
    require_once "../config/conexion.php";

    $idservicio = $_POST['idservicio'] ?? 0;

    $sql = "UPDATE servicio SET nombre_servicio=?, descripcion=?, precio=?, duracion_estimada=? WHERE id_servicio=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssdii", $nombre_servicio, $descripcion, $precio, $duracion_estimada, $idservicio);

    if ($stmt->execute()) {
        echo json_encode(["estado" => true, "mensaje" => "Servicio modificado correctamente"]);
    } else {
        echo json_encode(["estado" => false, "mensaje" => "Error al modificar: " . $stmt->error]);
    }
    $stmt->close();
    $conexion->close();
}

if ($opcion == "eliminar") {
    require_once "../config/conexion.php";

    $idservicio = $_POST['idservicio'] ?? 0;

    if (!is_numeric($idservicio) || $idservicio <= 0) {
        echo json_encode(["estado" => false, "mensaje" => "ID de servicio no válido"]);
        exit;
    }

    $conexion->begin_transaction();

    try {
        $sqlServicio = "DELETE FROM servicio WHERE id_servicio=?";
        $stmtServ = $conexion->prepare($sqlServicio);
        $stmtServ->bind_param("i", $idservicio);
        
        if (!$stmtServ->execute()) {
            throw new Exception("Error al eliminar servicio: " . $stmtServ->error);
        }
        $stmtServ->close();

        $conexion->commit();
        echo json_encode(["estado" => true, "mensaje" => "Servicio eliminado correctamente"]);
    } catch (Throwable $e) {
        $conexion->rollback();
        echo json_encode(["estado" => false, "mensaje" => $e->getMessage()]);
    } finally {
        $conexion->close();
    }
}

if ($opcion == "consultar") {
    require_once "../config/conexion.php";

    try {
        $sql = "SELECT id_servicio, nombre_servicio, descripcion, precio, duracion_estimada, estado_servicio 
                FROM servicio 
                ORDER BY nombre_servicio;";

        $resultado = $conexion->query($sql);

        if (!$resultado) {
            throw new Exception("Error al consultar los servicios");
        }

        $servicios = [];

        while ($fila = $resultado->fetch_assoc()) {
            $servicios[] = [
                'id_servicio' => $fila['id_servicio'],
                'nombre_servicio' => $fila['nombre_servicio'],
                'descripcion' => $fila['descripcion'],
                'precio' => $fila['precio'],
                'duracion_estimada' => $fila['duracion_estimada'],
                'estado_servicio' => $fila['estado_servicio']
            ];
        }

        echo json_encode($servicios, JSON_UNESCAPED_UNICODE);

        $resultado->free();
        $conexion->close();
    } catch (Throwable $e) {
        echo json_encode(["mensaje" => "No se pudieron obtener los registros"], JSON_UNESCAPED_UNICODE);
    }
}

if ($opcion == "consultarIndividual") {
    require_once "../config/conexion.php";

    $idservicio = $_POST['idservicio'] ?? 0;

    $sql = "SELECT id_servicio, nombre_servicio, descripcion, precio, duracion_estimada FROM servicio WHERE id_servicio=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idservicio);

    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $datos = $resultado->fetch_assoc();
        echo json_encode([
            "estado" => true,
            "datos" => [
                "idservicio" => $datos["id_servicio"],
                "nombre" => $datos["nombre_servicio"],
                "descripcion" => $datos["descripcion"],
                "precio" => $datos["precio"],
                "duracion" => $datos["duracion_estimada"]
            ]
        ]);
    } else {
        echo json_encode(["estado" => false, "datos" => "No Existe"]);
    }

    $stmt->close();
    $conexion->close();
}