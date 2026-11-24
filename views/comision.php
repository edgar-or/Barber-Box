<?php
require_once __DIR__ . "/../config/auth.php";
requerirAdminPagina();
$currentPage = 'comision';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comisión - Barber-Box</title>
  <link rel="icon" type="image/png" href="../assets/images/favicon.ico">

  <link rel="stylesheet" href="../assets/libs/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/libs/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/libs/apexcharts/apexcharts.css">
  <link rel="stylesheet" href="../assets/libs/flatpickr/flatpickr.min.css">

  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/panel.css">
  <link rel="stylesheet" href="../assets/css/tema-barberbox.css?v=6">
</head>

<body>
  <?php include './menu/menuLateral.php'; ?>
  <?php include './menu/menu-header.php'; ?>

  <div class="page-header">
    <div class="d-flex align-items-center gap-3">
      <div class="page-icon"><i class="bi bi-percent"></i></div>
      <div>
        <h1 class="page-title">Comisión</h1>
        <p class="page-subtitle">Define el porcentaje que se aplica al precio de cada servicio en los reportes.</p>
      </div>
    </div>
  </div>

  <div class="card panel-step" style="max-width: 640px;">
    <div class="panel-step-title"><i class="bi bi-percent me-2"></i>Comisión por servicio</div>

    <form id="formComision" autocomplete="off">
      <div class="row g-3 align-items-end">
        <div class="col-12 col-md-7">
          <label for="comisionPorcentaje" class="form-label fw-semibold">Porcentaje (%)</label>
          <div class="input-group">
            <input type="number" class="form-control" id="comisionPorcentaje" name="comision"
              min="0" max="100" step="0.01" placeholder="5" required>
            <span class="input-group-text">%</span>
          </div>
        </div>
        <div class="col-12 col-md-5">
          <button type="button" class="btn btn-danger w-100 fw-bold" id="btnGuardarComision">Guardar</button>
        </div>
      </div>
    </form>

    <p class="text-muted small mt-3 mb-0">
      <i class="bi bi-info-circle me-1"></i>Se calcula sobre el precio de cada servicio en el reporte de cortes por barbero.
    
    </p>
  </div>

  <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/libs/apexcharts/apexcharts.min.js"></script>
  <script src="../assets/libs/flatpickr/flatpickr.min.js"></script>
  <script src="../assets/js/dashboard.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/JSComision.js?v=<?php echo filemtime('../assets/js/JSComision.js'); ?>"></script>
</body>

</html>