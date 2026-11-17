<?php

$nombre_servicio   = $_POST['nombre'] ?? '';
$descripcion       = $_POST['descripcion'] ?? '';
$precio            = $_POST['precio'] ?? 0.00;
$duracion_estimada = $_POST['duracion'] ?? 0;
$opcion = isset($_REQUEST["opcion"]) ? $_REQUEST["opcion"] : "";

// Función para procesar y guardar múltiples imágenes
function guardarImagenesGaleria($conexion, $id_servicio) {
    if (isset($_FILES['imagenes_servicio'])) {
        $archivos = $_FILES['imagenes_servicio'];
        $uploadFileDir = '../assets/img/servicios/';
        
        if (!file_exists($uploadFileDir)) {
            mkdir($uploadFileDir, 0777, true);
        }

        for ($i = 0; $i < count($archivos['name']); $i++) {
            if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                $tmpFilePath = $archivos['tmp_name'][$i];
                $fileName = $archivos['name'][$i];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($fileExtension, $allowedExtensions)) {
                    $newFileName = md5(time() . $fileName . $i) . '.' . $fileExtension;
                    $dest_path = $uploadFileDir . $newFileName;

                    if (move_uploaded_file($tmpFilePath, $dest_path)) {
                        $ruta_bd = 'assets/img/servicios/' . $newFileName;
                        
                        $sqlImg = "INSERT INTO servicio_imagen (id_servicio, ruta_imagen) VALUES (?, ?)";
                        $stmtImg = $conexion->prepare($sqlImg);
                        $stmtImg->bind_param("is", $id_servicio, $ruta_bd);
                        $stmtImg->execute();
                        $stmtImg->close();
                    }
                }
            }
        }
    }
}

if ($opcion == "guardar") {
    require_once "../config/conexion.php";
    $conexion->begin_transaction();

    try {
        $estado_servicio = 'A'; 

        $sqlServicio = "INSERT INTO servicio (nombre_servicio, descripcion, precio, duracion_estimada, estado_servicio) VALUES (?, ?, ?, ?, ?)";
        $stmtServ = $conexion->prepare($sqlServicio);
        $stmtServ->bind_param("ssdis", $nombre_servicio, $descripcion, $precio, $duracion_estimada, $estado_servicio);

        if (!$stmtServ->execute()) {
            throw new Exception("Error al guardar el servicio: " . $stmtServ->error);
        }

        $id_nuevo_servicio = $conexion->insert_id;
        $stmtServ->close();

        // Guardar las imágenes asociadas
        guardarImagenesGaleria($conexion, $id_nuevo_servicio);

        $conexion->commit();
        echo json_encode(["estado" => true, "mensaje" => "Servicio y galería registrados correctamente"]);
    } catch (Throwable $e) {
        $conexion->rollback();
        echo json_encode(["estado" => false, "mensaje" => $e->getMessage()]);
    } finally {
        $conexion->close();
    }
}

if ($opcion == "modificar") {
    require_once "../config/conexion.php";
    $conexion->begin_transaction();

    try {
        $idservicio = $_POST['idservicio'] ?? 0;

        $sql = "UPDATE servicio SET nombre_servicio=?, descripcion=?, precio=?, duracion_estimada=? WHERE id_servicio=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssdii", $nombre_servicio, $descripcion, $precio, $duracion_estimada, $idservicio);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al modificar servicio");
        }
        $stmt->close();

        // Si se subieron nuevas imágenes al modificar, las añadimos a la galería
        guardarImagenesGaleria($conexion, $idservicio);

        $conexion->commit();
        echo json_encode(["estado" => true, "mensaje" => "Servicio modificado correctamente"]);
    } catch (Throwable $e) {
        $conexion->rollback();
        echo json_encode(["estado" => false, "mensaje" => $e->getMessage()]);
    } finally {
        $conexion->close();
    }
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
        $sql = "SELECT s.id_servicio, s.nombre_servicio, s.descripcion, s.precio, s.duracion_estimada, s.estado_servicio 
                FROM servicio s 
                ORDER BY s.nombre_servicio;";

        $resultado = $conexion->query($sql);
        $servicios = [];

        while ($fila = $resultado->fetch_assoc()) {
            $id_serv = $fila['id_servicio'];
            
            // Consultar las imágenes de cada servicio
            $sqlImg = "SELECT ruta_imagen FROM servicio_imagen WHERE id_servicio = ?";
            $stmtImg = $conexion->prepare($sqlImg);
            $stmtImg->bind_param("i", $id_serv);
            $stmtImg->execute();
            $resImg = $stmtImg->get_result();
            
            $imagenes = [];
            while($img = $resImg->fetch_assoc()) {
                $imagenes[] = $img;
            }
            $stmtImg->close();

            $servicios[] = [
                'id_servicio' => $fila['id_servicio'],
                'nombre_servicio' => $fila['nombre_servicio'],
                'descripcion' => $fila['descripcion'],
                'precio' => $fila['precio'],
                'duracion_estimada' => $fila['duracion_estimada'],
                'estado_servicio' => $fila['estado_servicio'],
                'imagenes' => $imagenes
            ];
        }

        echo json_encode($servicios, JSON_UNESCAPED_UNICODE);
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
        
        // Consultar su galería
        $sqlImg = "SELECT ruta_imagen FROM servicio_imagen WHERE id_servicio = ?";
        $stmtImg = $conexion->prepare($sqlImg);
        $stmtImg->bind_param("i", $idservicio);
        $stmtImg->execute();
        $resImg = $stmtImg->get_result();
        
        $imagenes = [];
        while($img = $resImg->fetch_assoc()) {
            $imagenes[] = $img;
        }
        $stmtImg->close();

        echo json_encode([
            "estado" => true,
            "datos" => [
                "idservicio" => $datos["id_servicio"],
                "nombre" => $datos["nombre_servicio"],
                "descripcion" => $datos["descripcion"],
                "precio" => $datos["precio"],
                "duracion" => $datos["duracion_estimada"],
                "imagenes" => $imagenes
            ]
        ]);
    } else {
        echo json_encode(["estado" => false, "datos" => "No Existe"]);
    }

    $stmt->close();
    $conexion->close();
}