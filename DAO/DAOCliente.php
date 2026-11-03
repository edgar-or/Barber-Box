<?php


$telefono = $_POST['telefono'] ?? '';
$nombre = $_POST['nombre'] ?? '';
$apellido = $_POST['apellido'] ?? '';
$correo = $_POST['correo'] ?? '';
$nickUsuario = $_POST['usuario'] ?? '';
$contrasenia = $_POST['contrasenia'] ?? '';


$opcion = isset($_REQUEST["opcion"]) ? $_REQUEST["opcion"] : "";

if ($opcion == "guardar") {
    require_once "../config/conexion.php";
    $conexion->begin_transaction();

    try {
        // Encriptar la contraseñs
        $contrasena_hash = password_hash($contrasenia, PASSWORD_DEFAULT);
        
    
        $id_rol = 3; 
        $estado_usuario = 'A';

        //Insertar en la tabla USUARIO
        $sqlUsuario = "INSERT INTO usuario (usuario, contrasena_hash, estado_usuario, id_rol) VALUES (?, ?, ?, ?)";
        $stmtUser = $conexion->prepare($sqlUsuario);
        $stmtUser->bind_param("sssi", $nickUsuario, $contrasena_hash, $estado_usuario, $id_rol);
        
        if (!$stmtUser->execute()) {
            throw new Exception("Error al crear usuario: " . $stmtUser->error);
        }

        // Obtener el ID generado automáticamente para este usuario
        $id_usuario_generado = $conexion->insert_id;
        $stmtUser->close();

        $sqlCliente = "INSERT INTO cliente (nombre, apellido, telefono, correo, id_usuario) VALUES (?, ?, ?, ?, ?)";
        $stmtEmp = $conexion->prepare($sqlCliente);
        $stmtEmp->bind_param("ssssi", $nombre, $apellido, $telefono, $correo, $id_usuario_generado);

        if (!$stmtEmp->execute()) {
            throw new Exception("Error al guardar datos del cliente: " . $stmtEmp->error);
        }
        $stmtEmp->close();

        // Confirmar ambas operaciones en la base de datos
        $conexion->commit();

        echo json_encode([
            "estado" => true,
            "mensaje" => "Cliente y Usuario registrados correctamente"
        ]);

    } catch (Exception $e) {
        // En caso de cualquier falla, cancela los cambios en ambas tablas
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

    $sql = "UPDATE empleado SET dui=?, nombre=?, apellido=?, telefono=?, isss=?, afp=?, nup=?, renta=?, sueldo=?, idcargo=?, idcanton=?, estado=?, fecha_ingreso=? WHERE idempleado=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssssssssssssii", $dui, $nombre, $apellido, $telefono, $isss, $afp, $nup, $renta, $sueldo, $cargo, $canton, $estado, $fechaIngreso, $idempleado);
    
    if ($stmt->execute()) {
        echo json_encode([
            "estado" => true,
            "mensaje" => "Datos Modificados Correctamente"
        ]);
    } else {
        echo json_encode([
            "estado" => false,
            "mensaje" => "Error al modificar: " . $stmt->error
        ]);
    }
    $stmt->close();
    $conexion->close();
}

if ($opcion == "eliminar") {
    require_once "../config/conexion.php";

    $sql = "DELETE FROM empleado WHERE idempleado=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idempleado);
    if ($stmt->execute()) {
        echo json_encode([
            "estado" => true,
            "mensaje" => "Datos Eliminados Correctamente"
        ]);
    } else {
        echo json_encode([
            "estado" => false,
            "mensaje" => "Error al eliminar los datos"
        ]);
    }
    $stmt->close();
    $conexion->close();
}

if ($opcion == "consultarDUI") {
    require_once "../config/conexion.php";

    $sql = "SELECT dui FROM empleado WHERE dui=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $dui);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        echo json_encode([
            "estado" => true,
            "mensaje" => "Este Dui ya existe"
        ]);
    } else {
        echo json_encode([
            "estado" => false,
            "mensaje" => "a"
        ]);
    }

    $stmt->close();
    $conexion->close();
}

if ($opcion == "consultar") {
    require_once "../config/conexion.php";

    try {
        $sql = "SELECT id_cliente, nombre, apellido, telefono, correo, concat(nombre,' ',apellido) as nombreCompleto
                FROM cliente
                ORDER BY nombre;";

        $resultado = $conexion->query($sql);

        if (!$resultado) {
            throw new Exception("Error al consultar los clientes");
        }

        $clientes = [];

        while ($fila = $resultado->fetch_assoc()) {
            $clientes[] = [
                'id_cliente' => $fila['id_cliente'],
                'nombre' => $fila['nombre'],
                'apellido' => $fila['apellido'],
                'telefono' => $fila['telefono'],
                'correo' => $fila['correo'],
                'nombreCompleto' => $fila['nombreCompleto']
            ];
        }

        echo json_encode(
            $clientes,
            JSON_UNESCAPED_UNICODE
        );

        $resultado->free();
        $conexion->close();
    } catch (Throwable $e) {
        echo json_encode([
            "mensaje" => "No se pudieron obtener los registros"
        ], JSON_UNESCAPED_UNICODE);
    }
}

if ($opcion == "consultarIndividual") {
    require_once "../config/conexion.php";

    $sql = "SELECT idempleado, dui, nombre, apellido, telefono, isss, afp, nup, renta, sueldo, idcargo, idcanton, estado, fecha_ingreso
            FROM empleado WHERE idempleado=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idempleado);

    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $datos = $resultado->fetch_assoc();
        echo json_encode([
            "estado" => true,
            "datos" => [
                "idempleado" => $datos["idempleado"],
                "dui" => $datos["dui"],
                "nombre" => $datos["nombre"],
                "apellido" => $datos["apellido"],
                "telefono" => $datos["telefono"],
                "isss" => $datos["isss"],
                "afp" => $datos["afp"],
                "nup" => $datos["nup"],
                "renta" => $datos["renta"],
                "sueldo" => $datos["sueldo"],
                "cargo" => $datos["idcargo"], 
                "canton" => $datos["idcanton"],
                "estado" => $datos["estado"],
                "fecha_ingreso" => $datos["fecha_ingreso"]
            ]
        ]);
    } else {
        echo json_encode([
            "estado" => false,
            "datos" => "No Existe"
        ]);
    }

    $stmt->close();
    $conexion->close();
}

if ($opcion == "consultarLectores") {
    require_once "../config/conexion.php";

    try {
        $sql = "SELECT idempleado, CONCAT(nombre, ' ', apellido) as nombre 
                FROM empleado 
                WHERE idcargo = 1";

        $resultado = $conexion->query($sql);
        $empleados = [];

        while ($fila = $resultado->fetch_assoc()) {
            $empleados[] = $fila;
        }

        echo json_encode($empleados, JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}
?>