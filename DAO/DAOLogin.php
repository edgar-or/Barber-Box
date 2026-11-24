<?php
require_once __DIR__ . "/../config/auth.php";

// Limpiar cualquier HTML o espacios previos antes de enviar el JSON
if (ob_get_length()) {
    ob_clean();
}
header('Content-Type: application/json; charset=utf-8');

$opcion = $_POST['opcion'] ?? $_GET['opcion'] ?? '';

if ($opcion === "iniciar_sesion") {
    require_once __DIR__ . "/../config/conexion.php";

    try {
        $usuario = trim($_POST['usuario'] ?? '');
        $clave = trim($_POST['clave'] ?? '');

        if ($usuario === '' || $clave === '') {
            echo json_encode([
                "estado" => false,
                "mensaje" => "Ingrese su usuario y contraseña."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sql = "SELECT id_usuario, usuario, contrasena_hash, estado_usuario, id_rol
                FROM usuario
                WHERE usuario = ?
                LIMIT 1";

        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta");
        }

        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $row = $resultado->fetch_assoc();
        $stmt->close();

        // Verificar existencia de usuario y contraseña (hash o texto plano temporal)
        $passwordValida = $row && (
            password_verify($clave, $row['contrasena_hash']) ||
            hash_equals((string)$row['contrasena_hash'], $clave)
        );

        if (!$passwordValida) {
            echo json_encode([
                "estado" => false,
                "mensaje" => "Usuario o contraseña incorrectos."
            ], JSON_UNESCAPED_UNICODE);
            $conexion->close();
            exit;
        }

        if ($row['estado_usuario'] !== 'A') {
            echo json_encode([
                "estado" => false,
                "mensaje" => "El usuario se encuentra inactivo."
            ], JSON_UNESCAPED_UNICODE);
            $conexion->close();
            exit;
        }

        session_regenerate_id(true);
        $idRol = (int)$row['id_rol'];

        $_SESSION['idusuario'] = $row['id_usuario'];
        $_SESSION['usuario'] = $row['usuario'];
        $_SESSION['id_rol'] = $idRol;
        $_SESSION['login_time'] = time();

        $esEmpleado = in_array($idRol, [ROL_ADMIN, ROL_BARBERO], true);
        $_SESSION['rol_nombre'] = $esEmpleado
            ? ($idRol === ROL_ADMIN ? 'Administrador' : 'Barbero')
            : 'Cliente';

        if ($idRol === ROL_ADMIN) {
            $rutaDestino = "/barberBox/index.php";
        } elseif ($idRol === ROL_BARBERO) {
            $rutaDestino = "/barberBox/views/tabla-citas.php";
        } else {
            $rutaDestino = "/barberBox/views/cliente-panel.php";
        }

        $conexion->close();

        echo json_encode([
            "estado" => true,
            "mensaje" => "¡Bienvenido/a " . $row['usuario'] . "!",
            "redirect" => $rutaDestino
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode([
            "estado" => false,
            "mensaje" => "Ocurrió un error en el servidor: " . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($opcion === "cerrar_sesion") {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();

    echo json_encode([
        "estado" => true,
        "redirect" => "/barberBox/page-login.php"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    "estado" => false,
    "mensaje" => "Opción no válida."
], JSON_UNESCAPED_UNICODE);