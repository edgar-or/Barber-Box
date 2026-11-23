<?php
// Utilidades de sesión y roles. Roles: 1 Administrador, 2 Barbero (empleados), 3 Cliente.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const ROL_ADMIN   = 1;
const ROL_BARBERO = 2;
const ROL_CLIENTE = 3;

function sesionActiva(): bool {
    return !empty($_SESSION['idusuario']);
}

function esEmpleado(): bool {
    return sesionActiva() && in_array((int)($_SESSION['id_rol'] ?? 0), [ROL_ADMIN, ROL_BARBERO], true);
}

function esCliente(): bool {
    return sesionActiva() && (int)($_SESSION['id_rol'] ?? 0) === ROL_CLIENTE;
}
function esAdmin(): bool {
    return sesionActiva() && (int)($_SESSION['id_rol'] ?? 0) === ROL_ADMIN;
}

/** Páginas del panel administrativo: solo empleados. Los clientes van a su panel. */
function requerirEmpleadoPagina(): void {
    if (!sesionActiva()) {
        header("Location: /barberBox/page-login.php");
        exit;
    }
    if (!esEmpleado()) {
        header("Location: /barberBox/views/cliente-panel.php");
        exit;
    }
}

/** Páginas del cliente. */
function requerirClientePagina(): void {
    if (!sesionActiva()) {
        header("Location: /barberBox/page-login.php");
        exit;
    }
    if (!esCliente()) {
        header("Location: /barberBox/views/panel-agendar-cita-admin.php");
        exit;
    }
}

function requerirAdminPagina(): void {
    if (!sesionActiva()) {
        header("Location: /barberBox/page-login.php");
        exit;
    }
    if (!esAdmin()) {
        header("Location: " . (esCliente() ? "/barberBox/views/cliente-panel.php" : "/barberBox/views/panel-agendar-cita-admin.php"));
        exit;
    }
}

/** Endpoints JSON (DAO): corta con 403 si no es empleado. */
function requerirEmpleadoJson(): void {
    if (!esEmpleado()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["estado" => false, "mensaje" => "No tienes permisos para esta acción."]);
        exit;
    }
}

/** Endpoints JSON del cliente: corta con 403 si no es cliente. */
function requerirClienteJson(): void {
    if (!esCliente()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["estado" => false, "mensaje" => "Acceso solo para clientes."]);
        exit;
    }
}

/** Endpoints JSON solo para el administrador. */
function requerirAdminJson(): void {
    if (!esAdmin()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["estado" => false, "mensaje" => "Solo el administrador puede realizar esta acción."]);
        exit;
    }
}
