<?php
require_once __DIR__ . "/../config/auth.php";
requerirEmpleadoPagina();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = 'servicios';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Servicios</title>

    <meta name="description" content="Login Screen - Spark Admin Premium Bootstrap 5 Admin Dashboard Template">
    <meta name="author" content="Spark Admin Team">

    <link rel="icon" type="image/png" href="/barberBox/assets/images/favicon.ico">

    <!-- Local Third-Party Libraries -->
    <link rel="stylesheet" href="/barberBox/assets/libs/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/barberBox/assets/libs/bootstrap-icons/bootstrap-icons.css">

    <!-- Main Design System & Custom Stylesheet -->
    <link rel="stylesheet" href="/barberBox/assets/css/main.css">
    
    <!-- ✅ Tema Barbería (Solo una vez y con ruta corregida) -->
    <link rel="stylesheet" href="/barberBox/assets/css/tema-barberbox.css?v=41"> 

</head>

<body class="pagina-servicios">
    <?php include './menu/menuLateral.php'; ?>
    <?php include './menu/menu-header.php'; ?>

    <div class="login-wrapper">

        <!-- Glowing background shapes -->
        <div class="login-bg-shape login-bg-shape-1"></div>
        <div class="login-bg-shape login-bg-shape-2"></div>

        <div class="container w-100 position-relative py-4">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-7 col-xl-6">

                    <div class="text-center mb-3">
                        <a href="../index.php" class="login-brand text-decoration-none">
                            <i class="bi bi-asterisk"></i>
                            <span>Barber-Box</span>
                        </a>
                    </div>

                    <p class="login-subtitle text-center mb-4">Por favor, complete los datos</p>

                    <form action="" method="POST" id="formRegistrarServicio" name="formRegistrarServicio" enctype="multipart/form-data">

                        <div class="login-card w-100 mw-100 m-0">

                            <h2 class="h5 mb-4 text-white"><i class="bi bi-scissors me-2"></i>Datos Del Servicio</h2>

                            <div class="login-form-group">
                                <label for="nombre" class="login-form-label">Nombre</label>
                                <div class="login-input-group">
                                    <i class="bi bi-tag input-icon"></i>
                                    <input type="text" id="nombre" name="nombre" class="login-input form-control"
                                        placeholder="Ej. Corte Clásico" maxlength="60" autocomplete="off" required>
                                </div>
                            </div>

                            <div class="login-form-group">
                                <label for="descripcion" class="login-form-label">Descripción</label>
                                <div class="login-input-group">
                                    <i class="bi bi-file-text input-icon"></i>
                                    <input type="text" id="descripcion" name="descripcion" class="login-input form-control"
                                        placeholder="Ej. Corte de cabello con tijera y máquina" maxlength="120" autocomplete="off" required>
                                </div>
                            </div>

                            <div class="login-form-group">
                                <label for="precio" class="login-form-label">Precio</label>
                                <div class="login-input-group">
                                    <i class="bi bi-currency-dollar input-icon"></i>
                                    <input type="number" step="0.01" id="precio" name="precio" class="login-input form-control" placeholder="0.00" autocomplete="off" required>
                                </div>
                            </div>

                            <div class="login-form-group">
                                <label for="duracion" class="login-form-label">Duración estimada (en minutos)</label>
                                <div class="login-input-group">
                                    <i class="bi bi-clock input-icon"></i>
                                    <input type="number" id="duracion" name="duracion" class="login-input form-control"
                                        placeholder="Ej. 30" min="1" max="32767" autocomplete="off" required>
                                </div>
                            </div>

                            <div class="login-form-group">
                                <label for="imagenes_servicio" class="login-form-label">Galería de Imágenes del Servicio</label>
                                <div class="login-input-group">
                                    <i class="bi bi-images input-icon"></i>
                                    <input type="file" id="imagenes_servicio" name="imagenes_servicio[]" class="login-input form-control text-white" accept="image/png, image/jpeg, image/webp" multiple>
                                </div>
                                <div class="form-text text-muted mt-1" style="font-size: 0.8rem;">Puedes seleccionar varias imágenes a la vez.</div>
                            </div>

                            <div id="preview-galeria" class="d-flex flex-wrap gap-2 mb-3 justify-content-center"></div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-danger" id="btnRegistrar" name="btnRegistrar">
                                    <i class="bi bi-save me-1"></i> Registrar Servicio
                                </button>
                            </div>

                        </div>

                    </form>

                </div>
            </div>
        </div>

    </div>

    <!-- Scripts -->
    <script src="/barberBox/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/barberBox/assets/js/JSRegistrarServicios.js"></script>
    <script src="/barberBox/assets/libs/apexcharts/apexcharts.min.js"></script>
    <script src="/barberBox/assets/libs/flatpickr/flatpickr.min.js"></script>
    <script src="/barberBox/assets/js/dashboard.js"></script>
    <script src="/barberBox/assets/js/auth.js"></script>

</body>
</html>