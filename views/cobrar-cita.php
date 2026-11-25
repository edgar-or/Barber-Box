<?php
require_once __DIR__ . "/../config/auth.php";
requerirEmpleadoPagina();

$id_cita = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_cita <= 0) {
    header("Location: tabla-citas.php");
    exit();
}

$currentPage = 'citas';
$esAdmin = (int)$_SESSION['id_rol'] === ROL_ADMIN;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Caja / Procesar Pago - Barber-Box</title>
  <link rel="icon" type="image/png" href="../assets/images/favicon.ico">
  <link rel="stylesheet" href="../assets/libs/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/libs/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/cobrar-cita.css?v=10">
</head>
<body class="pagina-caja">
  <?php include './menu/menuLateral.php'; ?>
  <?php include './menu/menu-header.php'; ?>

  <div class="page-header mb-4">
    <div class="d-flex align-items-center gap-3">
      <div class="page-icon bg-success text-white rounded-3 p-3 shadow-sm"><i class="bi bi-cash-coin fs-4"></i></div>
      <div>
        <h1 class="page-title fw-bold mb-1">Caja Registradora</h1>
        <p class="page-subtitle text-muted mb-0">Procesando pago de la Cita #<?= $id_cita; ?> — <strong class="text-dark" id="subtituloCliente">Cargando...</strong></p>
      </div>
    </div>
  </div>

  <div class="container-fluid pb-5">
    <div class="row g-4 justify-content-center">
        <!-- Columna Izquierda: Resumen del Servicio -->
        <div class="col-lg-5">
            <div class="card pos-card h-100">
                <div class="pos-header">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-receipt me-2"></i>Resumen de la Cuenta</h5>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                            <div class="me-3 text-primary fs-4"><i class="bi bi-person-circle"></i></div>
                            <div>
                                <span class="text-muted small d-block">Cliente</span>
                                <strong class="fs-6" id="infoCliente">Cargando...</strong>
                            </div>
                        </div>

                        <div class="d-flex align-items-center mb-4 pb-2 border-bottom">
                            <div class="me-3 text-secondary fs-4"><i class="bi bi-scissors"></i></div>
                            <div>
                                <span class="text-muted small d-block">Barbero Asignado</span>
                                <strong class="fs-6" id="infoBarbero">Cargando...</strong>
                            </div>
                        </div>

                        <h6 class="fw-bold text-muted text-uppercase fs-7 mb-3">Servicios Solicitados</h6>
                        <table class="table table-borderless align-middle mb-3">
                            <tbody id="listaServiciosCobro">
                                <tr><td colspan="2" class="text-center text-muted py-3">Cargando detalle...</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="total-box text-center mt-3">
                        <span class="text-muted small text-uppercase fw-bold d-block mb-1">Total a Pagar</span>
                        <span class="fs-1 fw-bold text-success" id="labelTotal">$0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Interfaz de Pago y Vuelto -->
        <div class="col-lg-6">
            <div class="card pos-card h-100">
                <div class="pos-header">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-calculator me-2"></i>Cálculo de Cobro</h5>
                </div>
                <div class="card-body p-4">
                    <form id="formProcesarCobro" class="needs-validation" novalidate>
                        <input type="hidden" id="idCita" name="id_cita" value="<?= $id_cita; ?>">
                        <input type="hidden" id="totalCita" name="total_cita" value="0">

                        <div class="mb-4">
                            <label for="metodoPago" class="form-label fw-bold text-secondary">Método de Pago</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white"><i class="bi bi-wallet2"></i></span>
                                <select class="form-select fs-5" id="metodoPago" name="metodo_pago" required>
                                    <option value="Efectivo" selected>Efectivo</option>
                                    <option value="Transferencia">Transferencia</option>
                                </select>
                            </div>
                        </div>

                        <div id="seccionEfectivo" class="mb-4">
                            <div class="mb-3">
                                <label for="montoRecibido" class="form-label fw-bold text-secondary">Efectivo Recibido ($)</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white text-success fw-bold">$</span>
                                    <input type="text" inputmode="decimal" class="form-control form-control-lg fw-bold fs-3 pos-input-monto" id="montoRecibido" autocomplete="off" autofocus>
                                </div>
                                <div class="form-text text-muted">Digite la cantidad exacta entregada por el cliente.</div>
                            </div>

                            <div class="change-box text-center">
                                <span class="text-muted small text-uppercase fw-bold d-block mb-1">Cambio / Vuelto a Entregar</span>
                                <span id="textoVuelto" class="display-6 fw-bold text-secondary">$0.00</span>
                            </div>
                        </div>

                        <div class="d-flex gap-3 pt-3">
                            <a href="tabla-citas.php" class="btn btn-outline-secondary btn-lg px-4">
                                <i class="bi bi-arrow-left me-1"></i> Volver
                            </a>
                            <button type="submit" id="btnConfirmarCobro" class="btn btn-success btn-lg flex-grow-1 shadow-sm">
                                <i class="bi bi-check-circle-fill me-2"></i> Cobrar y Finalizar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </div>

  <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/JSCobrar-cita.js"></script>
</body>
</html>