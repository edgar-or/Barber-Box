<?php 
  $currentPage = 'empleado'; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Empleado</title>
    
    <!-- SEO Optimization -->
    <meta name="description" content="Login Screen - Spark Admin Premium Bootstrap 5 Admin Dashboard Template">
    <meta name="author" content="Spark Admin Team">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.ico">
    
    <!-- Local Third-Party Libraries (100% Offline Compatible) -->
    <link rel="stylesheet" href="../assets/libs/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/libs/bootstrap-icons/bootstrap-icons.css">
    
    <!-- Main Design System & Custom Stylesheet -->
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
      <?php include './menu/menuLateral.php'; ?>
            <?php include './menu/menu-header.php'; ?>

    <div class="login-wrapper">

        <!-- Glowing background shapes for modern visual appearance -->
        <div class="login-bg-shape login-bg-shape-1"></div>
        <div class="login-bg-shape login-bg-shape-2"></div>

        <!-- Contenedor Bootstrap: centra y limita el ancho. position-relative lo deja sobre las formas de fondo -->
        <div class="container w-100 position-relative py-4">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10 col-xl-9">

                    <!-- Brand Identity -->
                    <div class="text-center mb-3">
                        <a href="../index.php" class="login-brand text-decoration-none">
                            <i class="bi bi-asterisk"></i>
                            <span>Barber-Box</span>
                        </a>
                    </div>

                    <p class="login-subtitle text-center mb-4">Por favor, complete los datos</p>

                    <!-- UN solo formulario que envuelve ambos paneles -->
                    <form action="" method="POST" id="formRegistrarEmpleado" name="formRegistrarEmpleado">

                        <div class="row g-4">

                            <!-- ===== Panel izquierdo: datos personales ===== -->
                            <div class="col-12 col-md-6">
                                <div class="login-card w-100 mw-100 m-0 h-100">

                                    <h2 class="h5 mb-4 text-white"><i class="bi bi-person me-2"></i>Datos personales</h2>
                                    
                                    <div class="login-form-group">
                                    <label for="dui" class="login-form-label">Dui</label>
                                    <div class="login-input-group">
                                        <i class="bi bi-card-text input-icon"></i>
                                    <input type="text" id="dui" name="dui" class="login-input form-control"
                                        placeholder="00000000-0" maxlength="10" required>
                                            </div>
                                            </div>

                                    <div class="login-form-group">
                                        <label for="nombre" class="login-form-label">Nombre</label>
                                        <div class="login-input-group">
                                            <i class="bi bi-person input-icon"></i>
                                            <input type="text" id="nombre" name="nombre" class="login-input form-control"
                                                   placeholder="Edgar" maxlength="60" autocomplete="given-name" required>
                                        </div>
                                    </div>

                                    <div class="login-form-group">
                                        <label for="apellido" class="login-form-label">Apellido</label>
                                        <div class="login-input-group">
                                            <i class="bi bi-person-badge input-icon"></i>
                                            <input type="text" id="apellido" name="apellido" class="login-input form-control"
                                                   placeholder="Ayala" maxlength="60" autocomplete="family-name" required>
                                        </div>
                                    </div>

                                    <div class="login-form-group">
                                        <label for="telefono" class="login-form-label">Teléfono</label>
                                        <div class="login-input-group">
                                            <i class="bi bi-telephone input-icon"></i>
                                            <input type="tel" id="telefono" name="telefono" class="login-input form-control"
                                                   placeholder="6776-9876" maxlength="9"
                                                   title="Ingresa 8 dígitos, por ejemplo 6776-9876" required>
                                        </div>
                                    </div>

                                    

                                    <div class="login-form-group">
                                        <label for="correo" class="login-form-label">Correo electrónico</label>
                                        <div class="login-input-group">
                                            <i class="bi bi-envelope input-icon"></i>
                                            <input type="email" id="correo" name="correo" class="login-input form-control"
                                                   placeholder="edgar@gmail.com" maxlength="120" autocomplete="email" required>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <!-- ===== Panel derecho: usuario y contraseña ===== -->
                            <div class="col-12 col-md-6">
                                <div class="login-card w-100 mw-100 m-0 h-100">

                                    <h2 class="h5 mb-4 text-white"><i class="bi bi-shield-lock me-2"></i>Datos de la cuenta</h2>

                                    <div class="login-form-group">
                                        <label for="usuario" class="login-form-label">Usuario</label>
                                        <div class="login-input-group">
                                            <i class="bi bi-at input-icon"></i>
                                            <input type="text" id="usuario" name="usuario" class="login-input form-control"
                                                   placeholder="edgar.ayala" autocomplete="username"
                                                   pattern="[A-Za-z0-9_.]{4,30}" minlength="4" maxlength="30"
                                                   title="De 4 a 30 caracteres: letras, números, punto o guion bajo" required>
                                        </div>
                                    </div>

                                    <div class="login-form-group">
                                        <label for="contrasenia" class="login-form-label">Contraseña</label>
                                        <div class="login-input-group">
                                            <i class="bi bi-shield-lock input-icon"></i>
                                            <input type="password" id="contrasenia" name="contrasenia"
                                                   class="login-input login-input-password form-control"
                                                   placeholder="••••••••"  autocomplete="new-password" required>
                                            <button type="button" class="password-toggle-btn" id="toggle-password"
                                                    aria-label="Mostrar contraseña">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="login-form-group">
                                        <label for="contrasenia_confirm" class="login-form-label">Confirmar contraseña</label>
                                        <div class="login-input-group">
                                            <i class="bi bi-shield-check input-icon"></i>
                                            <input type="password" id="contrasenia_confirm" name="contrasenia_confirm"
                                                   class="login-input login-input-password form-control"
                                                   placeholder="••••••••" autocomplete="new-password" required>
                                            <button type="button" class="password-toggle-btn" id="toggle-password-confirm"
                                                    aria-label="Mostrar contraseña">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>

                        <!-- Botón centrado debajo de los dos paneles -->
                        <div class="d-flex flex-column align-items-center gap-3 mt-4">
                           <button type="button" class="btn-login w-auto px-5" id="btnRegistrar" name="btnRegistrar">Registrar 
                            <i class="bi bi-person-plus"></i></button>
                            <p class="mb-0">¿Ya tienes cuenta? <a href="page-login.php">Inicia sesión</a></p>
                        </div>

                    </form>

                </div>
            </div>
        </div>

    </div>

    <!-- Local Bootstrap bundle -->
    <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/JSRegistrarEmpleados.js"></script>

     <!-- Local Third-Party Libraries Script dependencies -->
  <script src="../assets/libs/apexcharts/apexcharts.min.js"></script>
  <script src="../assets/libs/flatpickr/flatpickr.min.js"></script>

  <!-- Local dashboard interactions controller -->
  <script src="../assets/js/dashboard.js"></script>
    
    <!-- Custom Authentication interactions script -->
    <script src="../assets/js/auth.js"></script>
</body>
</html>