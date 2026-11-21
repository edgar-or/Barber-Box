<?php
require_once __DIR__ . "/../config/auth.php";
requerirEmpleadoPagina();
$currentPage = 'agendar-admin';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de citas - Barber-Box</title>
  <link rel="icon" type="image/png" href="../assets/images/favicon.ico">

  <link rel="stylesheet" href="../assets/libs/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/libs/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/panel.css">
</head>

<body>
  <?php include './menu/menuLateral.php'; ?>
  <?php include './menu/menu-header.php'; ?>

  <div class="page-header">
    <div>
      <h1 class="page-title">Gestión de citas</h1>
      <p class="page-subtitle">Agenda citas para tus clientes y consulta la agenda.</p>
    </div>
  </div>

  <ul class="nav nav-pills panel-tabs mb-4" id="panelTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="tab-agendar" data-bs-toggle="pill" data-bs-target="#pane-agendar" type="button" role="tab">
        <i class="bi bi-calendar-plus me-2"></i>Agendar cita
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-historial" data-bs-toggle="pill" data-bs-target="#pane-historial" type="button" role="tab">
        <i class="bi bi-calendar-week me-2"></i>Agenda
      </button>
    </li>
  </ul>

  <div id="bannerEdicion" class="alert alert-warning d-none align-items-center justify-content-between" role="alert">
    <span><i class="bi bi-pencil-square me-2"></i>Estás modificando una cita existente.</span>
    <button type="button" class="btn btn-sm btn-outline-dark" id="btnCancelarEdicion">Descartar cambios</button>
  </div>

  <div class="tab-content">

    <!-- ============ AGENDAR ============ -->
    <div class="tab-pane fade show active" id="pane-agendar" role="tabpanel">
      <div class="row g-4">
        <div class="col-12 col-xl-8">

          <div class="card panel-step mb-4 overflow-visible">
            <div class="panel-step-title "><span class="step-num">0</span>Cliente</div>
            <div class="position-relative">
              <input type="text" id="buscarCliente" class="form-control" placeholder="Escribe el nombre del cliente..." autocomplete="off">
              <div id="resultadosCliente" class="list-group position-absolute w-100 shadow d-none" style="z-index:1050"></div>
            </div>
            <div id="clienteElegido" class="mt-2 d-none align-items-center justify-content-between">
              <span><i class="bi bi-person-check-fill me-2"></i><strong id="clienteNombre"></strong></span>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="btnQuitarCliente">Cambiar</button>
            </div>
          </div>

          <div class="card panel-step mb-4">
            <div class="panel-step-title"><span class="step-num">1</span>Elige los servicios</div>
            <div class="row g-3" id="listaServicios">
              <div class="col-12 text-muted">Cargando servicios...</div>
            </div>
          </div>

          <div class="card panel-step mb-4">
            <div class="panel-step-title"><span class="step-num">2</span>Barbero y fecha</div>
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label for="selBarbero" class="form-label fw-semibold">Barbero</label>
                <select id="selBarbero" class="form-select"></select>
              </div>
              <div class="col-12 col-md-6">
                <label for="fechaCita" class="form-label fw-semibold">Fecha</label>
                <input type="date" id="fechaCita" class="form-control">
              </div>
            </div>
          </div>

          <div class="card panel-step">
            <div class="panel-step-title"><span class="step-num">3</span>Horarios disponibles</div>
            <div id="listaHoras" class="d-flex flex-wrap gap-2">
              <span class="text-muted">Selecciona servicios, barbero y fecha para ver horarios.</span>
            </div>
            <div class="mt-3">
              <label for="notaCita" class="form-label fw-semibold">Nota (opcional)</label>
              <input type="text" id="notaCita" class="form-control" maxlength="255" placeholder="Ej: Quiere el degradado bajo">
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-4">
          <div class="card panel-resumen sticky-xl-top">
            <h2 class="h5 fw-bold mb-3"><i class="bi bi-receipt me-2"></i>Resumen</h2>
            <ul class="list-unstyled mb-3" id="resumenServicios">
              <li class="text-muted">Sin servicios seleccionados</li>
            </ul>
            <div class="resumen-linea"><span>Cliente</span><strong id="resumenCliente">—</strong></div>
            <div class="resumen-linea"><span>Duración</span><strong id="resumenDuracion">0 min</strong></div>
            <div class="resumen-linea"><span>Fecha</span><strong id="resumenFecha">—</strong></div>
            <div class="resumen-linea"><span>Hora</span><strong id="resumenHora">—</strong></div>
            <div class="resumen-linea total"><span>Total</span><strong id="resumenTotal">$0.00</strong></div>
            <button type="button" class="btn btn-danger w-100 mt-3 py-2 fw-bold" id="btnAgendar" disabled>
              <i class="bi bi-calendar-check me-2"></i>Confirmar cita
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============ AGENDA ============ -->
    <div class="tab-pane fade" id="pane-historial" role="tabpanel">
      <div class="row g-2 mb-3">
        <div class="col-12 col-md-4"><input type="date" id="filtroFecha" class="form-control"></div>
        <div class="col-12 col-md-4"><select id="filtroBarbero" class="form-select"></select></div>
        <div class="col-12 col-md-4"><button type="button" class="btn btn-outline-secondary w-100" id="btnLimpiarFiltros">Limpiar filtros</button></div>
      </div>

      <div class="card panel-historial">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Servicios</th>
                <th>Barbero</th>
                <th class="text-end">Total</th>
                <th class="text-center">Estado</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="tbodyHistorial">
              <tr><td colspan="7" class="text-center text-muted py-4">Cargando...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/libs/apexcharts/apexcharts.min.js"></script>
  <script src="../assets/libs/flatpickr/flatpickr.min.js"></script>
  <script src="../assets/js/dashboard.js"></script>
  <script src="../assets/js/JSAgendar-cita-admin.js?v=<?php echo filemtime('../assets/js/JSAgendar-cita-admin.js'); ?>"></script>
</body>

</html>