<?php
require_once __DIR__ . "/../config/auth.php";
requerirEmpleadoPagina();
$currentPage = 'citas';
$esAdmin = (int)$_SESSION['id_rol'] === ROL_ADMIN;
$titulo = $esAdmin ? 'Historial de citas' : 'Mis citas';
$subtitulo = $esAdmin ? 'Todas las citas agendadas por los clientes.' : 'Citas que los clientes han agendado contigo.';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $titulo; ?> - Barber-Box</title>
  <link rel="icon" type="image/png" href="../assets/images/favicon.ico">

  <link rel="stylesheet" href="../assets/libs/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/libs/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/libs/apexcharts/apexcharts.css">
  <link rel="stylesheet" href="../assets/libs/flatpickr/flatpickr.min.css">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link href="https://unpkg.com/tabulator-tables@5.5.2/dist/css/tabulator.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/tablas.css">
</head>

<body>
  <?php include './menu/menuLateral.php'; ?>
  <?php include './menu/menu-header.php'; ?>

  <div class="page-header">
    <div class="d-flex align-items-center gap-3">
      <div class="page-icon"><i class="bi bi-calendar-check"></i></div>
      <div>
        <h1 class="page-title"><?= $titulo; ?></h1>
        <p class="page-subtitle"><?= $subtitulo; ?></p>
      </div>
    </div>
  </div>

  <div class="table-card-custom tabla-moderna">
    <div class="table-header-control">
      <div class="d-flex flex-wrap gap-2 flex-grow-1">
        <div class="table-search-box">
          <i class="bi bi-search table-search-icon"></i>
          <input type="text" id="table-filter" class="table-search-input" placeholder="Buscar por cliente, barbero o servicio...">
        </div>
        <select id="filtroEstado" class="form-select filtro-estado">
          <option value="">Todos los estados</option>
          <option value="Pendiente">Pendiente</option>
          <option value="Completada">Completada</option>
          <option value="Cancelada">Cancelada</option>
        </select>
      </div>
      <div class="tabla-total"><i class="bi bi-calendar3"></i> Total: <strong id="total-registros">0</strong></div>
    </div>

    <div id="tablaCitas"></div>
  </div>

  <!-- Detalle de la cita -->
  <div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content detalle-cita">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-bold"><i class="bi bi-receipt me-2"></i>Detalle de la cita</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body" id="detalleCuerpo"></div>
      </div>
    </div>
  </div>

  <script>window.CITAS_ES_ADMIN = <?= $esAdmin ? 'true' : 'false'; ?>;</script>
  <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/libs/apexcharts/apexcharts.min.js"></script>
  <script src="../assets/libs/flatpickr/flatpickr.min.js"></script>
  <script src="../assets/js/dashboard.js"></script>
  <script src="https://unpkg.com/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/JSLista-citas.js"></script>
</body>

</html>
