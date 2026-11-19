<?php
require_once __DIR__ . "/../config/verificacion.php";

// Toda la gestión de empleados es solo para personal autorizado
requerirEmpleadoJson();

$telefono = $_POST['telefono'] ?? '';
$nombre = $_POST['nombre'] ?? '';
$apellido = $_POST['apellido'] ?? '';
$correo = trim($_POST['correo'] ?? '');
$nickUsuario = $_POST['usuario'] ?? '';
$contrasenia = $_POST['contrasenia'] ?? '';
$dui = $_POST['dui'] ?? '';
$opcion = isset($_REQUEST["opcion"]) ? $_REQUEST["opcion"] : "";

if ($opcion == "enviarCodigo") {
    header('Content-Type: application/json; charset=utf-8');

    require_once "../config/conexion.php";
    $stmt = $conexion->prepare("SELECT 1 FROM empleado WHERE correo = ? LIMIT 1");
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

    $stmtDup = $conexion->prepare("SELECT 1 FROM usuario WHERE usuario = ? LIMIT 1");
    $stmtDup->bind_param("s", $nickUsuario);
    $stmtDup->execute();
    if ($stmtDup->get_result()->num_rows > 0) {
        echo json_encode(["estado" => false, "mensaje" => "El nombre de usuario ya existe."]);
        exit;
    }
    $stmtDup->close();

    $conexion->begin_transaction();

    try {
        $contrasena_hash = password_hash($contrasenia, PASSWORD_DEFAULT);

        $id_rol = 2;
        $estado_usuario = 'A';

        // Insertar en la tabla USUARIO
        $sqlUsuario = "INSERT INTO usuario (usuario, contrasena_hash, estado_usuario, id_rol) VALUES (?, ?, ?, ?)";
        $stmtUser = $conexion->prepare($sqlUsuario);
        $stmtUser->bind_param("sssi", $nickUsuario, $contrasena_hash, $estado_usuario, $id_rol);

        if (!$stmtUser->execute()) {
            throw new Exception("Error al crear usuario: " . $stmtUser->error);
        }

        $id_usuario_generado = $conexion->insert_id;
        $stmtUser->close();

        $sqlEmpleado = "INSERT INTO empleado (nombre, apellido, telefono, correo, id_usuario, dui) VALUES (?, ?, ?, ?, ?, ?)";
        $stmtEmp = $conexion->prepare($sqlEmpleado);
        $stmtEmp->bind_param("ssssis", $nombre, $apellido, $telefono, $correo, $id_usuario_generado, $dui);

        if (!$stmtEmp->execute()) {
            throw new Exception("Error al guardar datos del Empleado: " . $stmtEmp->error);
        }
        $stmtEmp->close();

        $conexion->commit();
        consumirCodigoVerificacion();

        echo json_encode([
            "estado" => true,
            "mensaje" => "Empleado y Usuario registrados correctamente"
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

    $idempleado = $_POST['idempleado'] ?? 0;

    $sql = "UPDATE empleado SET nombre=?, apellido=?, telefono=?, dui=? WHERE id_empleado=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssssi", $nombre, $apellido, $telefono, $dui, $idempleado);
    
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

    $idempleado = $_POST['idempleado'] ?? 0;

    if (!is_numeric($idempleado) || $idempleado <= 0) {
        echo json_encode(["estado" => false, "mensaje" => "ID de empleado no válido"]);
        exit;
    }

    $conexion->begin_transaction();

    try {
        // 1. Primero buscamos si tiene un usuario asignado
        $sqlBuscar = "SELECT id_usuario FROM empleado WHERE id_empleado = ?";
        $stmtBuscar = $conexion->prepare($sqlBuscar);
        $stmtBuscar->bind_param("i", $idempleado);
        $stmtBuscar->execute();
        $resultadoBuscar = $stmtBuscar->get_result();
        
        $id_usuario = null;
        if ($fila = $resultadoBuscar->fetch_assoc()) {
            $id_usuario = $fila['id_usuario'];
        }
        $stmtBuscar->close();

        $sqlEmpleado = "DELETE FROM empleado WHERE id_empleado=?";
        $stmtEmp = $conexion->prepare($sqlEmpleado);
        $stmtEmp->bind_param("i", $idempleado);
        if (!$stmtEmp->execute()) {
            throw new Exception("Error al eliminar empleado: " . $stmtEmp->error);
        }
        $stmtEmp->close();

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
        echo json_encode(["estado" => true, "mensaje" => "Empleado eliminado correctamente"]);

    } catch (Throwable $e) {
        $conexion->rollback();
        echo json_encode(["estado" => false, "mensaje" => $e->getMessage()]);
    } finally {
        $conexion->close();
    }
}
if ($opcion == "consultarDUI") {
    echo json_encode(["estado" => false, "mensaje" => "La tabla no tiene campo DUI"]);
}

if ($opcion == "consultar") {
    require_once "../config/conexion.php";

    try {
        $sql = "SELECT id_empleado, nombre, apellido, telefono, correo, dui, concat(nombre,' ',apellido) as nombreCompleto
                FROM empleado
                ORDER BY nombre;";

        $resultado = $conexion->query($sql);

        if (!$resultado) {
            throw new Exception("Error al consultar los empleados");
        }

        $empleados = [];

        while ($fila = $resultado->fetch_assoc()) {
            $empleados[] = [
                'id_empleado' => $fila['id_empleado'],
                'nombre' => $fila['nombre'],
                'apellido' => $fila['apellido'],
                'telefono' => $fila['telefono'],
                'correo' => $fila['correo'],
                'nombreCompleto' => $fila['nombreCompleto'],
                'dui' => $fila['dui']
            ];
        }

        echo json_encode($empleados, JSON_UNESCAPED_UNICODE);

        $resultado->free();
        $conexion->close();
    } catch (Throwable $e) {
        echo json_encode(["mensaje" => "No se pudieron obtener los registros"], JSON_UNESCAPED_UNICODE);
    }
}

if ($opcion == "consultarIndividual") {
    require_once "../config/conexion.php";

    $idempleado = $_POST['idempleado'] ?? 0; 

    $sql = "SELECT id_empleado, nombre, apellido, telefono, correo, dui FROM empleado WHERE id_empleado=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idempleado);

    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $datos = $resultado->fetch_assoc();
        echo json_encode([
            "estado" => true,
            "datos" => [
                "idempleado" => $datos["id_empleado"], 
                "nombre" => $datos["nombre"],
                "apellido" => $datos["apellido"],
                "telefono" => $datos["telefono"],
                "correo" => $datos["correo"],
                "dui" => $datos["dui"] 
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
        $sql = "SELECT id_empleado as idempleado, CONCAT(nombre, ' ', apellido) as nombre 
                FROM empleado 
                WHERE atiende_citas = 1";

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