<?php
// Comisión global por servicio (solo administrador)
require_once __DIR__ . "/../config/auth.php";
requerirAdminJson();

header('Content-Type: application/json; charset=utf-8');

$opcion      = isset($_REQUEST["opcion"]) ? $_REQUEST["opcion"] : "";
$id_comision = intval($_POST['id_comision'] ?? 0);
$comision    = $_POST['comision'] ?? '';

if ($opcion == "guardar") {
    require_once "../config/conexion.php";

    if (!is_numeric($comision) || $comision < 0 || $comision > 100) {
        echo json_encode([
            "estado" => false,
            "mensaje" => "La comisión debe ser un número entre 0 y 100."
        ], JSON_UNESCAPED_UNICODE);
    } else {
        try {
            // Solo existe una comisión global: si ya hay una, se debe modificar
            $sql = "SELECT id_comision FROM comision LIMIT 1";
            $resultado = $conexion->query($sql);

            if ($resultado && $resultado->num_rows > 0) {
                echo json_encode([
                    "estado" => false,
                    "mensaje" => "Ya existe una comisión definida. Use modificar."
                ], JSON_UNESCAPED_UNICODE);
            } else {
                $valor = floatval($comision);

                $sql = "INSERT INTO comision (comision) VALUES (?)";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("d", $valor);

                if ($stmt->execute()) {
                    echo json_encode([
                        "estado" => true,
                        "mensaje" => "Datos Guardados Correctamente"
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        "estado" => false,
                        "mensaje" => "Error al guardar: " . $stmt->error
                    ], JSON_UNESCAPED_UNICODE);
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            echo json_encode([
                "estado" => false,
                "mensaje" => "No se pudo guardar la comisión"
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    $conexion->close();
}

if ($opcion == "modificar") {
    require_once "../config/conexion.php";

    if ($id_comision <= 0 || !is_numeric($comision) || $comision < 0 || $comision > 100) {
        echo json_encode([
            "estado" => false,
            "mensaje" => "La comisión debe ser un número entre 0 y 100."
        ], JSON_UNESCAPED_UNICODE);
    } else {
        try {
            $sql = "SELECT id_comision FROM comision WHERE id_comision = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $id_comision);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $existe = $resultado->num_rows > 0;
            $stmt->close();

            if (!$existe) {
                echo json_encode([
                    "estado" => false,
                    "mensaje" => "La comisión no existe"
                ], JSON_UNESCAPED_UNICODE);
            } else {
                $valor = floatval($comision);

                $sql = "UPDATE comision SET comision = ? WHERE id_comision = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("di", $valor, $id_comision);

                if ($stmt->execute()) {
                    echo json_encode([
                        "estado" => true,
                        "mensaje" => "Datos Modificados Correctamente"
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode([
                        "estado" => false,
                        "mensaje" => "Error al modificar: " . $stmt->error
                    ], JSON_UNESCAPED_UNICODE);
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            echo json_encode([
                "estado" => false,
                "mensaje" => "No se pudo modificar la comisión"
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    $conexion->close();
}

if ($opcion == "consultar") {
    require_once "../config/conexion.php";

    try {
        $sql = "SELECT id_comision, comision FROM comision ORDER BY id_comision DESC LIMIT 1";
        $resultado = $conexion->query($sql);

        if (!$resultado) {
            throw new Exception("Error al consultar la comisión");
        }

        if ($resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            echo json_encode([
                "estado" => true,
                "datos" => [
                    'id_comision' => $fila['id_comision'],
                    'comision' => $fila['comision']
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                "estado" => false,
                "datos" => "No Existe"
            ], JSON_UNESCAPED_UNICODE);
        }

        $resultado->free();
    } catch (Throwable $e) {
        echo json_encode([
            "estado" => false,
            "datos" => "Error al consultar la comisión"
        ], JSON_UNESCAPED_UNICODE);
    }
    $conexion->close();
}

if (!in_array($opcion, ["guardar", "modificar", "consultar"])) {
    echo json_encode([
        "estado" => false,
        "mensaje" => "Opción no válida."
    ], JSON_UNESCAPED_UNICODE);
}