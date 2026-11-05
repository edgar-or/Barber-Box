<?php
session_start();

// Limpiar cualquier HTML o espacios previos antes de enviar el JSON
if (ob_get_length()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

$opcion = $_POST['opcion'] ?? $_GET['opcion'] ?? '';

if ($opcion === "iniciar_sesion") {
    require_once "../config/conexion.php";

    try {
        $usuario = trim($_POST['usuario'] ?? '');
        $clave = trim($_POST['clave'] ?? '');

        $sql = "SELECT id_usuario, usuario, contrasena_hash, estado_usuario
                FROM usuario
                WHERE usuario = ? LIMIT 1;";

        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en la preparación de la consulta");
        }

        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($row = $resultado->fetch_assoc()) {
            
            // Verificar si el usuario está activo (opcional)
            if (isset($row['estado']) && $row['estado'] == 0) {
                echo json_encode([
                    "estado" => false,
                    "mensaje" => "El usuario se encuentra inactivo."
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Verificar contraseña (soporta password_hash o texto plano temporal)
            $passwordValida = password_verify($clave, $row['contrasena_hash']) || ($clave === $row['contrasena_hash']);

            if ($passwordValida) {
                // Guardar variables en la sesión del servidor
                $_SESSION['idusuario'] = $row['id_usuario'];
                $_SESSION['usuario'] = $row['usuario'];
                $_SESSION['login_time'] = time();

                echo json_encode([
                    "estado" => true,
                    "mensaje" => "¡Bienvenido/a " . $row['usuario'] . "!",
                    "redirect" => "/barberBox/index.php"
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    "estado" => false,
                    "mensaje" => "Error en inicio de sesión"
                ], JSON_UNESCAPED_UNICODE);
            }

        } else {
            echo json_encode([
                "estado" => false,
                "mensaje" => "Error en inicio de sesión"
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