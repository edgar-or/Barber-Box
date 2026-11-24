<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$opcion = $_REQUEST["opcion"] ?? "";

$pathConexion = __DIR__ . "/../config/conexion.php";
if (!file_exists($pathConexion)) {
    ob_clean();
    echo json_encode(["estado" => false, "mensaje" => "No se encontró conexion.php"]);
    exit;
}
require_once $pathConexion;

if (!isset($conexion) || $conexion->connect_error) {
    ob_clean();
    echo json_encode(["estado" => false, "mensaje" => "Error de conexión a la base de datos"]);
    exit;
}

$hoy = date('Y-m-d');

// 1. REPORTE DE CORTES POR BARBERO (CON COMISIÓN GLOBAL DEL MÓDULO DE COMISIONES)
if ($opcion == "reporteCortes") {
    try {
        $fechaInicio = $_POST['fechaInicio'] ?? '';
        $fechaFin = $_POST['fechaFin'] ?? '';

        if (empty($fechaInicio) || empty($fechaFin)) {
            ob_clean();
            echo json_encode(["estado" => false, "mensaje" => "Fechas requeridas"]);
            exit;
        }

        if ($fechaInicio > $hoy || $fechaFin > $hoy) {
            ob_clean();
            echo json_encode(["estado" => false, "mensaje" => "No se pueden consultar reportes de fechas futuras"]);
            exit;
        }

        // Consultar el porcentaje de comisión global configurado
        $sqlComision = "SELECT comision FROM comision ORDER BY id_comision DESC LIMIT 1";
        $resComision = $conexion->query($sqlComision);
        $porcentajeGlobal = 0.00;
        if ($resComision && $resComision->num_rows > 0) {
            $rowCom = $resComision->fetch_assoc();
            $porcentajeGlobal = floatval($rowCom['comision']);
        }

        $fInicio = $fechaInicio . " 00:00:00";
        $fFin = $fechaFin . " 23:59:59";

        $sql = "SELECT 
                    e.id_empleado,
                    CONCAT(e.nombre, ' ', e.apellido) AS nombre_barbero,
                    c.id_cita,
                    c.fecha_hora_inicio AS fecha_cita,
                    CONCAT(cl.nombre, ' ', cl.apellido) AS nombre_cliente,
                    s.nombre_servicio,
                    dc.precio_unitario AS precio
                FROM cita c
                INNER JOIN empleado e ON c.id_empleado = e.id_empleado
                INNER JOIN cliente cl ON c.id_cliente = cl.id_cliente
                INNER JOIN detalle_cita dc ON c.id_cita = dc.id_cita
                INNER JOIN servicio s ON dc.id_servicio = s.id_servicio
                WHERE c.estado_cita = 'Completada' 
                  AND c.fecha_hora_inicio BETWEEN ? AND ?
                ORDER BY e.id_empleado, c.fecha_hora_inicio ASC";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ss", $fInicio, $fFin);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $cortesPlana = [];
        while ($fila = $resultado->fetch_assoc()) {
            $cortesPlana[] = $fila;
        }
        $stmt->close();

        ob_clean();
        if (empty($cortesPlana)) {
            echo json_encode(["estado" => false, "datos" => [], "mensaje" => "No hay cortes en estas fechas"]);
            exit;
        }

        $barberosAgrupados = [];
        foreach ($cortesPlana as $row) {
            $idBarbero = $row['id_empleado'];
            $precioCorte = floatval($row['precio']);
            $comisionCorte = $precioCorte * ($porcentajeGlobal / 100);

            if (!isset($barberosAgrupados[$idBarbero])) {
                $barberosAgrupados[$idBarbero] = [
                    "id_barbero" => $idBarbero,
                    "nombre_barbero" => $row['nombre_barbero'],
                    "porcentaje_comision" => $porcentajeGlobal,
                    "cortes" => []
                ];
            }
            $barberosAgrupados[$idBarbero]["cortes"][] = [
                "id_cita" => $row['id_cita'],
                "fecha_cita" => date("d/m/Y H:i", strtotime($row['fecha_cita'])),
                "nombre_cliente" => $row['nombre_cliente'],
                "nombre_servicio" => $row['nombre_servicio'],
                "precio" => $precioCorte,
                "comision" => $comisionCorte
            ];
        }

        $conexion->close();

        echo json_encode([
            "estado" => true,
            "tipo" => "cortes",
            "datos" => array_values($barberosAgrupados)
        ], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
        ob_clean();
        echo json_encode(["estado" => false, "mensaje" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// 2. REPORTE DE VENTAS MENSUALES (Histórico)
else if ($opcion == "reporteMensual") {
    try {
        $sql = "SELECT 
                    DATE_FORMAT(c.fecha_hora_inicio, '%Y-%m') AS anio_mes,
                    COUNT(DISTINCT c.id_cita) AS total_citas,
                    SUM(c.total_cita) AS total_recaudado
                FROM cita c
                WHERE c.estado_cita = 'Completada'
                GROUP BY anio_mes
                ORDER BY anio_mes DESC";

        $resultado = $conexion->query($sql);
        $datos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }
        $conexion->close();

        ob_clean();
        if (empty($datos)) {
            echo json_encode(["estado" => false, "datos" => [], "mensaje" => "No hay registros de ventas"]);
            exit;
        }

        echo json_encode([
            "estado" => true,
            "tipo" => "mensual",
            "datos" => $datos
        ], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
        ob_clean();
        echo json_encode(["estado" => false, "mensaje" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// 3. REPORTE DE SERVICIOS MÁS RENTABLES (Filtrado por Rango de Fechas)
else if ($opcion == "reporteServicios") {
    try {
        $fechaInicio = $_POST['fechaInicio'] ?? '';
        $fechaFin = $_POST['fechaFin'] ?? '';

        if (empty($fechaInicio) || empty($fechaFin)) {
            ob_clean();
            echo json_encode(["estado" => false, "mensaje" => "Fechas requeridas"]);
            exit;
        }

        if ($fechaInicio > $hoy || $fechaFin > $hoy) {
            ob_clean();
            echo json_encode(["estado" => false, "mensaje" => "No se pueden consultar reportes de fechas futuras"]);
            exit;
        }

        $fInicio = $fechaInicio . " 00:00:00";
        $fFin = $fechaFin . " 23:59:59";

        $sql = "SELECT 
                    s.nombre_servicio,
                    COUNT(dc.id_servicio) AS cantidad_veces,
                    SUM(dc.precio_unitario) AS ingreso_total
                FROM detalle_cita dc
                INNER JOIN servicio s ON dc.id_servicio = s.id_servicio
                INNER JOIN cita c ON dc.id_cita = c.id_cita
                WHERE c.estado_cita = 'Completada'
                  AND c.fecha_hora_inicio BETWEEN ? AND ?
                GROUP BY s.id_servicio, s.nombre_servicio
                ORDER BY ingreso_total DESC";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ss", $fInicio, $fFin);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $datos[] = $fila;
        }
        $stmt->close();
        $conexion->close();

        ob_clean();
        if (empty($datos)) {
            echo json_encode(["estado" => false, "datos" => [], "mensaje" => "No hay servicios en este rango de fechas"]);
            exit;
        }

        echo json_encode([
            "estado" => true,
            "tipo" => "servicios",
            "datos" => $datos
        ], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
        ob_clean();
        echo json_encode(["estado" => false, "mensaje" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} else {
    ob_clean();
    echo json_encode(["estado" => false, "mensaje" => "Opción no válida"]);
}
?>