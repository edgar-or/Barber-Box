<?php
require_once __DIR__ . "/../config/auth.php";
requerirEmpleadoPagina();
$currentPage = 'servicios';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Servicios - Barber-Box</title>
  <link rel="icon" type="image/png" href="../assets/images/favicon.ico">

  <link rel="stylesheet" href="../assets/libs/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/libs/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/libs/apexcharts/apexcharts.css">
  <link rel="stylesheet" href="../assets/libs/flatpickr/flatpickr.min.css">

  <link rel="stylesheet" href="../assets/css/main.css">
  <!-- Tabulator -->
  <link href="https://unpkg.com/tabulator-tables@5.5.2/dist/css/tabulator.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/tablas.css">
</head>

<body>
  <?php include './menu/menuLateral.php'; ?>
  <?php include './menu/menu-header.php'; ?>

  <div class="page-header">
    <div class="d-flex align-items-center gap-3">
      <div class="page-icon"><i class="bi bi-scissors"></i></div>
      <div>
        <h1 class="page-title">Servicios</h1>
        <p class="page-subtitle">Catálogo de servicios, precios y duración.</p>
      </div>
    </div>
    <button class="btn-nuevo" type="button" onclick="location.href='form-registrar-servicios.php'">
      <i class="bi bi-plus-lg"></i> Nuevo servicio
    </button>
  </div>

  <div class="table-card-custom tabla-moderna">
    <div class="table-header-control">
      <div class="table-search-box">
        <i class="bi bi-search table-search-icon"></i>
        <input type="text" id="table-filter" class="table-search-input" placeholder="Buscar servicios...">
      </div>
      <div class="tabla-total"><i class="bi bi-people"></i> Total: <strong id="total-registros">0</strong></div>
    </div>

    <div id="tablaServicios"></div>
  </div>

  <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/libs/apexcharts/apexcharts.min.js"></script>
  <script src="../assets/libs/flatpickr/flatpickr.min.js"></script>
  <script src="../assets/js/dashboard.js"></script>
  <script src="https://unpkg.com/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/JSLista-servicios.js"></script>
</body>

</html>
