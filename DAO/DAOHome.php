<?php
// Datos públicos de la página de inicio (no requiere sesión)
header('Content-Type: application/json; charset=utf-8');

$opcion = isset($_REQUEST["opcion"]) ? $_REQUEST["opcion"] : "";

if ($opcion == "consultarServicios") {
    require_once "../config/conexion.php";

    try {
        $sql = "SELECT id_servicio, nombre_servicio, descripcion, precio, duracion_estimada
                FROM servicio
                WHERE estado_servicio = 'A'
                ORDER BY nombre_servicio";

        $resultado = $conexion->query($sql);

        if (!$resultado) {
            throw new Exception("Error al consultar los servicios");
        }

        $servicios = [];

        while ($fila = $resultado->fetch_assoc()) {
            $servicios[$fila['id_servicio']] = [
                'id_servicio' => $fila['id_servicio'],
                'nombre_servicio' => $fila['nombre_servicio'],
                'descripcion' => $fila['descripcion'],
                'precio' => number_format($fila['precio'], 2, '.', ','),
                'duracion_estimada' => $fila['duracion_estimada'],
                'imagen' => null,
                'imagenes' => []
            ];
        }
        $resultado->free();

        // Todas las imágenes de cada servicio
        try {
            $sqlImagen = "SELECT id_servicio, ruta_imagen FROM servicio_imagen ORDER BY id_servicio ASC";
            $resultadoImagen = $conexion->query($sqlImagen);

            if ($resultadoImagen) {
                while ($fila = $resultadoImagen->fetch_assoc()) {
                    $idServ = $fila['id_servicio'];
                    if (isset($servicios[$idServ])) {
                        $servicios[$idServ]['imagenes'][] = $fila['ruta_imagen'];
                        if ($servicios[$idServ]['imagen'] === null) {
                            $servicios[$idServ]['imagen'] = $fila['ruta_imagen'];
                        }
                    }
                }
                $resultadoImagen->free();
            }
        } catch (Throwable $e) {
            // Sin imágenes: las tarjetas muestran el ícono por defecto
        }

        $registros = array_values($servicios);

        echo json_encode($registros, JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode([
            "mensaje" => "No se pudieron obtener los servicios"
        ], JSON_UNESCAPED_UNICODE);
    }
    $conexion->close();
}
if (!in_array($opcion, ["consultarServicios"])) {
    echo json_encode([
        "estado" => false,
        "mensaje" => "Opción no válida."
    ], JSON_UNESCAPED_UNICODE);
}