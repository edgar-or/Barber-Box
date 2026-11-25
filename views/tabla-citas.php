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

<body class="pagina-historial">
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

  <!-- START: Modal de Cobro y Cierre de Cita -->
  <div class="modal fade" id="modalCobroBarber" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-black text-white">
          <h5 class="modal-title fw-bold"><i class="bi bi-cash-coin me-2"></i>Completar y Cobrar Cita</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="formCobroBarber">
            <input type="hidden" id="modalIdCita" name="id_cita">
            
            <div class="mb-3">
              <label class="form-label fw-bold">Detalle de la Cita:</label>
              <div id="modalInfoClienteServicios" class="p-2 bg-light rounded border text-muted">
                Cargando...
              </div>
            </div>

            <div class="mb-3">
              <label for="modalTotalCita" class="form-label fw-bold">Total a Pagar ($):</label>
              <input type="text" class="form-control fw-bold text-success fs-5" id="modalTotalCita" readonly>
            </div>

            <div class="mb-3">
              <label for="modalMetodoPago" class="form-label fw-bold">Método de Pago</label>
              <select class="form-select" id="modalMetodoPago" name="metodo_pago" required>
                <option value="Efectivo">Efectivo (Contado)</option>
                <option value="Transferencia">Transferencia</option>
              </select>
            </div>

            <div id="contenedorEfectivo">
              <div class="mb-3">
                <label for="modalMontoRecibido" class="form-label fw-bold">Dinero Recibido ($):</label>
                <input type="number" step="0.01" class="form-control" id="modalMontoRecibido" name="monto_recibido" placeholder="Ej. 20.00">
              </div>
              <div class="mb-3">
                <label for="modalVuelto" class="form-label fw-bold">Vuelto / Cambio ($):</label>
                <input type="text" class="form-control fw-bold text-danger fs-5" id="modalVuelto" readonly value="0.00">
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" id="btnConfirmarCobroFinal" class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Confirmar y Completar</button>
        </div>
      </div>
    </div>
  </div>
  <!-- END: Modal de Cobro -->

  <script>window.CITAS_ES_ADMIN = <?= $esAdmin ? 'true' : 'false'; ?>;</script>
  <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/libs/apexcharts/apexcharts.min.js"></script>
  <script src="../assets/libs/flatpickr/flatpickr.min.js"></script>
  <script src="../assets/js/dashboard.js"></script>
  <script src="https://unpkg.com/tabulator-tables@5.5.2/dist/js/tabulator.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/JSLista-citas.js"></script>
  <link rel="stylesheet" href="../assets/css/tema-barberbox.css?v=20">

</body>

</html>
