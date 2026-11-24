<?php
require_once __DIR__ . "/../config/auth.php";

// Limpiar cualquier HTML o espacios previos antes de enviar el JSON
if (ob_get_length()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

$opcion = $_POST['opcion'] ?? $_GET['opcion'] ?? '';

if ($opcion === "iniciar_sesion") {
    require_once "../config/conexion.php";

    try {
        $usuario = trim($_POST['usuario'] ?? '');
        $clave = trim($_POST['clave'] ?? '');

        $sql = "SELECT id_usuario, usuario, contrasena_hash, estado_usuario, id_rol
                FROM usuario
                WHERE usuario = ? LIMIT 1;";

        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta");
        }

        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $row = $resultado->fetch_assoc();

        $passwordValida = $row && (
            password_verify($clave, $row['contrasena_hash']) ||
            ($clave === $row['contrasena_hash'])
        );

        if (!$passwordValida) {
            echo json_encode([
                "estado" => false,
                "mensaje" => "Error en inicio de sesión"
            ], JSON_UNESCAPED_UNICODE);
        } elseif ($row['estado_usuario'] !== 'A') {
            echo json_encode([
                "estado" => false,
                "mensaje" => "El usuario se encuentra inactivo."
            ], JSON_UNESCAPED_UNICODE);
        } else {
            session_regenerate_id(true);
            $idRol = (int)$row['id_rol'];

            $_SESSION['idusuario'] = $row['id_usuario'];
            $_SESSION['usuario'] = $row['usuario'];
            $_SESSION['id_rol'] = $idRol;
            $_SESSION['login_time'] = time();

            $esEmpleado = in_array($idRol, [ROL_ADMIN, ROL_BARBERO], true);
            $_SESSION['rol_nombre'] = $esEmpleado ? ($idRol === ROL_ADMIN ? 'Administrador' : 'Barbero') : 'Cliente';

            $rutaDestino = ($idRol === ROL_BARBERO)
                ? "/barberBox/views/tabla-citas.php"
                : "/barberBox/home.php";

            echo json_encode([
                "estado" => true,
                "mensaje" => "¡Bienvenido/a " . $row['usuario'] . "!",
                "redirect" => $rutaDestino
            ], JSON_UNESCAPED_UNICODE);
        }

        $stmt->close();
        $conexion->close();
    } catch (Throwable $e) {
        echo json_encode([
            "estado" => false,
            "mensaje" => "Ocurrió un error en el servidor: " . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($opcion === "cerrar_sesion") {
    session_destroy();
    echo json_encode([
        "estado" => true,
        "redirect" => "/barberBox/page-login.php"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}