<?php
require_once __DIR__ . "/../config/auth.php";
requerirEmpleadoPagina(); 
  $currentPage = 'empleados'; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horarios del Empleado</title>
    <link rel="icon" type="image/png" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="../assets/libs/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/libs/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body class="pagina-horarios">
    <?php include './menu/menuLateral.php'; ?>
    <?php include './menu/menu-header.php'; ?> 

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <!-- ✅ Cambiado text-dark a text-white -->
            <h2 class="h4 mb-0 text-white" id="tituloEmpleado">Horarios del Empleado</h2>
            
            <button type="button" class="btn-custom btn-custom-outline-danger" onclick="location.href='tabla-empleados.php'">
                <i class="bi bi-arrow-left"></i> Volver a Empleados
            </button>
        </div>

        <div class="card p-4 shadow-sm" style="border-radius: 12px;">
            
            <!-- Botón para abrir el Modal -->
            <div class="mb-3">
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalHorario">
                    <i class="bi bi-plus-circle"></i> Asignar Horario
                </button>
            </div>

            <!-- Se quitó el formulario y el botón "Guardar Todo" -->
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Día</th>
                            <th>Hora Entrada</th>
                            <th>Hora Salida</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tablaHorarios">
                        <!-- Se llena con JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL DE ASIGNAR HORARIO -->
    <div class="modal fade" id="modalHorario" tabindex="-1" aria-labelledby="modalHorarioLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalHorarioLabel">Asignar Horario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id_empleado">
                     <div class="mb-4">
        <label class="form-label fw-bold text-secondary">Día de la semana</label>
        <select class="form-select form-select-lg shadow-sm" id="comboDia">
            <option value="lunes">Lunes</option>
            <option value="martes">Martes</option>
            <option value="miercoles">Miércoles</option>
            <option value="jueves">Jueves</option>
            <option value="viernes">Viernes</option>
            <option value="sabado">Sábado</option>
            <option value="domingo">Domingo</option>
        </select>
    </div>

    <div class="row g-3">
        <!-- TURNO MAÑANA -->
        <div class="col-md-6">
            <div class="card border-0 bg-light shadow-sm h-100">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3 text-danger">
                        <i class="me-2"></i>Turno Mañana
                    </h6>
                    <div class="mb-3">
                        <label class="form-label text-muted small mb-1">Hora de Entrada</label>
                        <input type="time" class="form-control shadow-sm" id="entrada1" value="" onfocus="this.value = this.value || '08:00';">
                        
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-muted small mb-1">Hora de Salida</label>
                        <input type="time" class="form-control shadow-sm" id="salida1" value="" onfocus="this.value = this.value || '12:00';">
                    </div>
                </div>
            </div>
        </div>

        <!-- TURNO TARDE -->
        <div class="col-md-6">
            <div class="card border-0 bg-light shadow-sm h-100">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3 text-primary">
                        <i class=" me-2"></i>Turno Tarde
                    </h6>
                    <div class="mb-3">
                        <label class="form-label text-muted small mb-1">Hora de Entrada</label>
                        <input type="time" class="form-control shadow-sm" id="entrada2" value="" onfocus="this.value = this.value || '13:00';">
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-muted small mb-1">Hora de Salida</label>
                        <input type="time" class="form-control shadow-sm" id="salida2" value="" onfocus="this.value = this.value || '17:00';">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnGuardarHorarioModal">Guardar Horario</button>
                </div>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="../assets/css/tema-barberbox.css">
    <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/JSHorariosEmpleado.js?v=20"></script> 
        <link rel="stylesheet" href="../assets/css/horarios.css?v=99">

</body>
</html>