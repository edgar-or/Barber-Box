<?php
require_once __DIR__ . "/../config/verificacion.php";

$telefono     = $_POST['telefono'] ?? '';
$nombre       = $_POST['nombre'] ?? '';
$apellido     = $_POST['apellido'] ?? '';
$correo       = trim($_POST['correo'] ?? '');
$nickUsuario  = $_POST['usuario'] ?? '';
$contrasenia  = $_POST['contrasenia'] ?? '';
$idcliente    = $_POST['idcliente'] ?? 0;

$opcion = isset($_REQUEST["opcion"]) ? $_REQUEST["opcion"] : "";

// El autoregistro es público; todo lo demás (administrar clientes) es solo para empleados
if (!in_array($opcion, ["enviarCodigo", "guardar"], true)) {
    requerirEmpleadoJson();
}

if ($opcion == "enviarCodigo") {
    header('Content-Type: application/json; charset=utf-8');

    require_once "../config/conexion.php";

    $stmt = $conexion->prepare("SELECT 1 FROM cliente WHERE correo = ? LIMIT 1");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $existe = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    $conexion->close();

    if ($existe) {
        echo json_encode(["estado" => false, "mensaje" => "Este correo ya está registrado."]);
        exit;
    }

    [$ok, $mensaje] = enviarCodigoVerificacion($correo);
    echo json_encode(["estado" => $ok, "mensaje" => $mensaje]);
    exit;
}

if ($opcion == "guardar") {
    header('Content-Type: application/json; charset=utf-8');

    // El registro solo procede si el código enviado al correo es válido
    $errorCodigo = validarCodigoVerificacion($correo, $_POST['codigoVerificacion'] ?? '');
    if ($errorCodigo !== null) {
        echo json_encode(["estado" => false, "mensaje" => $errorCodigo]);
        exit;
    }

    require_once "../config/conexion.php";

    // Usuario duplicado
    $stmtDup = $conexion->prepare("SELECT 1 FROM usuario WHERE usuario = ? LIMIT 1");
    $stmtDup->bind_param("s", $nickUsuario);
    $stmtDup->execute();
    if ($stmtDup->get_result()->num_rows > 0) {
        echo json_encode(["estado" => false, "mensaje" => "El nombre de usuario ya existe."]);
        $stmtDup->close();
        $conexion->close();
        exit;
    }
    $stmtDup->close();

    $conexion->begin_transaction();

    try {
        $contrasena_hash = password_hash($contrasenia, PASSWORD_DEFAULT);
        $id_rol = 3;
        $estado_usuario = 'A';

        $sqlUsuario = "INSERT INTO usuario (usuario, contrasena_hash, estado_usuario, id_rol) VALUES (?, ?, ?, ?)";
        $stmtUser = $conexion->prepare($sqlUsuario);
        $stmtUser->bind_param("sssi", $nickUsuario, $contrasena_hash, $estado_usuario, $id_rol);

        if (!$stmtUser->execute()) {
            throw new Exception("Error al crear usuario: " . $stmtUser->error);
        }

        $id_usuario_generado = $conexion->insert_id;
        $stmtUser->close();

        $sqlCliente = "INSERT INTO cliente (nombre, apellido, telefono, correo, id_usuario) VALUES (?, ?, ?, ?, ?)";
        $stmtEmp = $conexion->prepare($sqlCliente);
        $stmtEmp->bind_param("ssssi", $nombre, $apellido, $telefono, $correo, $id_usuario_generado);

        if (!$stmtEmp->execute()) {
            throw new Exception("Error al guardar datos del cliente: " . $stmtEmp->error);
        }
        $stmtEmp->close();

        $conexion->commit();

        unset(
            $_SESSION['codigo_verificacion'],
            $_SESSION['correo_verificacion'],
            $_SESSION['codigo_expira'],
            $_SESSION['codigo_intentos'],
            $_SESSION['codigo_enviado_en']
        );

        echo json_encode([
            "estado"  => true,
            "mensaje" => "Cliente y Usuario registrados correctamente"
        ]);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode([
            "estado"  => false,
            "mensaje" => $e->getMessage()
        ]);
    } finally {
        $conexion->close();
    }
}

if ($opcion == "modificar") {
    require_once "../config/conexion.php";

    $sql = "UPDATE cliente SET nombre=?, apellido=?, telefono=? WHERE id_cliente=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssi", $nombre, $apellido, $telefono, $idcliente);

    if ($stmt->execute()) {
        echo json_encode([
            "estado"  => true,
            "mensaje" => "Datos Modificados Correctamente"
        ]);
    } else {
        echo json_encode([
            "estado"  => false,
            "mensaje" => "Error al modificar: " . $stmt->error
        ]);
    }
    $stmt->close();
    $conexion->close();
}

if ($opcion == "eliminar") {
    require_once "../config/conexion.php";

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
        echo json_encode([
            "estado"  => true,
            "mensaje" => "Cliente eliminado correctamente"
        ]);
    } catch (Throwable $e) {
        $conexion->rollback();
        echo json_encode([
            "estado"  => false,
            "mensaje" => $e->getMessage()
        ]);
    } finally {
        $conexion->close();
    }
}

if ($opcion == "consultar") {
    require_once "../config/conexion.php";

    try {
        $sql = "SELECT id_cliente, nombre, apellido, telefono, correo,
                       CONCAT(nombre,' ',apellido) AS nombreCompleto
                FROM cliente
                ORDER BY nombre";

        $resultado = $conexion->query($sql);

        if (!$resultado) {
            throw new Exception("Error al consultar los clientes");
        }

        $registros = [];
        $correlativo = 0;

        while ($fila = $resultado->fetch_assoc()) {
            $registros[] = [
                'id_cliente'     => $fila['id_cliente'],
                'nombre'         => $fila['nombre'],
                'apellido'       => $fila['apellido'],
                'telefono'       => $fila['telefono'],
                'correo'         => $fila['correo'],
                'nombreCompleto' => $fila['nombreCompleto'],
                'correlativo'    => $correlativo++
            ];
        }

        echo json_encode($registros, JSON_UNESCAPED_UNICODE);

        $resultado->free();
        $conexion->close();
    } catch (Throwable $e) {
        echo json_encode(
            ["mensaje" => "No se pudieron obtener los registros"],
            JSON_UNESCAPED_UNICODE
        );
    }
}

if ($opcion == "consultarIndividual") {
    require_once "../config/conexion.php";

    $sql = "SELECT id_cliente, nombre, apellido, telefono, correo
            FROM cliente
            WHERE id_cliente = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idcliente);

    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $datos = $resultado->fetch_assoc();
        echo json_encode([
            "estado" => true,
            "datos"  => [
                "idcliente" => $datos["id_cliente"],
                "nombre"    => $datos["nombre"],
                "apellido"  => $datos["apellido"],
                "telefono"  => $datos["telefono"],
                "correo"    => $datos["correo"]
            ]
        ]);
    } else {
        echo json_encode([
            "estado" => false,
            "datos"  => "No Existe"
        ]);
    }

    $stmt->close();
    $conexion->close();
}