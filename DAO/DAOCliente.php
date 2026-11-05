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

    $idcliente = $_POST['idcliente'] ?? 0;

    $sql = "UPDATE cliente SET nombre=?, apellido=?, telefono=? WHERE id_cliente=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssi", $nombre, $apellido, $telefono, $idcliente);
    
    if ($stmt->execute()) {
        echo json_encode(["estado" => true, "mensaje" => "Datos Modificados Correctamente"]);
    } else {
        echo json_encode(["estado" => false, "mensaje" => "Error al modificar: " . $stmt->error]);
    }
    $stmt->close();
    $conexion->close();
}

if ($opcion == "eliminar") {
    require_once "../config/conexion.php";

    $idcliente = $_POST['idcliente'] ?? 0;

    if (!is_numeric($idcliente) || $idcliente <= 0) {
        echo json_encode(["estado" => false, "mensaje" => "ID de cliente no válido"]);
        exit;
    }

    $conexion->begin_transaction();

    try {
        // 1. Primero buscamos si tiene un usuario asignado
        $sqlBuscar = "SELECT id_usuario FROM cliente WHERE id_cliente = ?";
        $stmtBuscar = $conexion->prepare($sqlBuscar);
        $stmtBuscar->bind_param("i", $idcliente);
        $stmtBuscar->execute();
        $resultadoBuscar = $stmtBuscar->get_result();
        
        $id_usuario = null;
        if ($fila = $resultadoBuscar->fetch_assoc()) {
            $id_usuario = $fila['id_usuario'];
        }
        $stmtBuscar->close();

        $sqlCliente = "DELETE FROM cliente WHERE id_cliente=?";
        $stmtClient = $conexion->prepare($sqlCliente);
        $stmtClient->bind_param("i", $idcliente);
        if (!$stmtClient->execute()) {
            throw new Exception("Error al eliminar cliente: " . $stmtClient->error);
        }
        $stmtClient->close();

        if ($id_usuario) {
            $sqlUsuario = "DELETE FROM usuario WHERE id_usuario=?";
            $stmtUser = $conexion->prepare($sqlUsuario);
            $stmtUser->bind_param("i", $id_usuario);
            if (!$stmtUser->execute()) {
                throw new Exception("Error al eliminar usuario: " . $stmtUser->error);
            }
            $stmtUser->close();
        }

        $conexion->commit();
        echo json_encode(["estado" => true, "mensaje" => "Cliente eliminado correctamente"]);

    } catch (Throwable $e) {
        $conexion->rollback();
        echo json_encode(["estado" => false, "mensaje" => $e->getMessage()]);
    } finally {
        $conexion->close();
    }
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

    $idcliente = $_POST['idcliente'] ?? 0; 

    $sql = "SELECT id_cliente, nombre, apellido, telefono, correo FROM cliente WHERE id_cliente=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idcliente);

    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $datos = $resultado->fetch_assoc();
        echo json_encode([
            "estado" => true,
            "datos" => [
                "idcliente" => $datos["id_cliente"], 
                "nombre" => $datos["nombre"],
                "apellido" => $datos["apellido"],
                "telefono" => $datos["telefono"],
                "correo" => $datos["correo"] 
            ]
        ]);
    } else {
        echo json_encode(["estado" => false, "datos" => "No Existe"]);
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