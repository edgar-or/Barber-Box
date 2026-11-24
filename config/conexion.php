<?php

//$servidor = "localhost";
//$usuario = "root";
//$laclave = "";
//$bbdd = "bdbarberbox";

$servidor = "localhost";
$usuario = "partysvc_renecarrillosUser";
$laclave = "KQSSV3_etVw4L#6";
$bbdd = "partysvc_renecarrillos";

$conexion = new mysqli($servidor, $usuario, $laclave, $bbdd);

if ($conexion->connect_error) {
    die("Error de conexion:" . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");