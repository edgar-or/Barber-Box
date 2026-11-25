<?php
require_once __DIR__ . "/../config/auth.php";
requerirAdminPagina();
$currentPage = 'empleados';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Empleados - Barber-Box</title>
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
      <div class="page-icon"><i class="bi bi-person-badge"></i></div>
      <div>
        <h1 class="page-title">Empleados</h1>
        <p class="page-subtitle">Gestiona al personal y sus horarios de atención.</p>
      </div>
    </div>
    <button class="btn-nuevo" type="button" onclick="location.href='form-registrar-empleados.php'">
      <i class="bi bi-plus-lg"></i> Nuevo empleado
    </button>
  </div>

  <!-- START: Basic Table Card Container -->
<div class="table-card-custom bg-dark text-white shadow-lg" style="border-radius: 15px; border: 1px solid #333;">
  
  <!-- Header Controls -->
  <div class="table-header-control border-bottom border-secondary pb-3 mb-3 d-flex justify-content-between align-items-center">
    <!-- Search bar -->
    <div class="table-search-box bg-secondary bg-opacity-25" style="border-radius: 8px; padding: 5px 15px;">
        <i class="bi bi-search table-search-icon text-light"></i>
        <input type="text" id="table-filter" class="table-search-input bg-transparent border-0 text-white" placeholder="Buscar empleados..." style="outline: none;">
    </div>
  </div>

  <!-- Contenedor dinámico de Tabulator -->
  <div id="tablaEmpleados"></div>

</div>
  <link rel="stylesheet" href="../assets/css/tablas.css">
<link rel="stylesheet" href="../assets/css/tema-barberbox.css?v=41">
  <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/libs/apexcharts/apexcharts.min.js"></script>
  <script src="../assets/libs/flatpickr/flatpickr.min.js"></script>
  <script src="../assets/js/dashboard.js"></script>
  <script src="https://unpkg.com/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/JSLista-empleados.js?v=6"></script>
</body>

</html>
