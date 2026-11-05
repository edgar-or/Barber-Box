<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si NO existe la sesión, redirigir al login y detener la ejecución
if (!isset($_SESSION['idusuario']) || empty($_SESSION['idusuario'])) {
    header("Location: /barberBox/page-login.php");
    exit();
}
  $currentPage = 'empleados'; 
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Basic Table - Spark Admin Premium Bootstrap 5 Admin Dashboard Template</title>

  <!-- SEO Optimization -->
  <meta name="description" content="Basic Table - Spark Admin Premium Bootstrap 5 Admin Dashboard Template">
  <meta name="author" content="Spark Admin Team">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="../assets/images/favicon.ico">

  <!-- Local Third-Party Libraries (100% Offline Compatible) -->
  <link rel="stylesheet" href="../assets/libs/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/libs/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/libs/apexcharts/apexcharts.css">
  <link rel="stylesheet" href="../assets/libs/flatpickr/flatpickr.min.css">

  <!-- Main Design System & Custom Stylesheet -->
  <link rel="stylesheet" href="../assets/css/main.css">
  

<!--tabulador para la tabla-->
  <link href="https://unpkg.com/tabulator-tables@5.5.2/dist/css/tabulator.min.css" rel="stylesheet">
</head>

<body>
      <?php include './menu/menuLateral.php'; ?>
            <?php include './menu/menu-header.php'; ?>


    <!-- START: Basic Table Card Container -->
    <div class="table-card-custom">
      
      <!-- Header Controls -->
      <div class="table-header-control">
          <button  class="btn-custom btn-custom-outline-danger" type="button" onclick="location.href='form-registrar-empleados.php'">Nuevo</button>
        <!-- Search bar -->
    
        <!-- Action buttons / Filter options -->
        
      </div>
      
<div class="table-card-custom">
    <!-- Header Controls -->
    <div class="table-header-control">
        <div class="table-search-box">
            <i class="bi bi-search table-search-icon"></i>
            <input type="text" id="table-filter" class="table-search-input" placeholder="Buscar empleados...">
        </div>
    </div>

    <!-- Contenedor dinámico de Tabulator -->
    <div id="tablaEmpleados"></div>

      

    
    <!-- END: Basic Table Card Container -->

    <!-- START: Footer Component -->

<!---->
    
    <!-- END: Footer Component -->

  </div>
  <!-- ==========================================
         END: Main Content Area
         ========================================== -->

  <!-- Local Third-Party Libraries Script dependencies -->
  <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/libs/apexcharts/apexcharts.min.js"></script>
  <script src="../assets/libs/flatpickr/flatpickr.min.js"></script>

  <!-- Local dashboard interactions controller -->
  <script src="../assets/js/dashboard.js"></script>
    <script src="https://unpkg.com/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
    <script src="../assets/js/JSLista-empleados.js?v=2"></script>
 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


</body>

</html>