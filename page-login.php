<?php 
  $currentPage = 'login'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    
    <!-- SEO Optimization -->
    <meta name="description" content="Login Screen - Spark Admin Premium Bootstrap 5 Admin Dashboard Template">
    <meta name="author" content="Spark Admin Team">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.ico">
    
    <!-- Local Third-Party Libraries (100% Offline Compatible) -->
    <link rel="stylesheet" href="assets/libs/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/libs/bootstrap-icons/bootstrap-icons.css">
    
    <!-- Main Design System & Custom Stylesheet -->
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>

    <!-- ==========================================
         START: Authentication Container & Login Card
         ========================================== -->
    <div class="login-wrapper">
        <!-- Glowing background shapes for modern visual appearance -->
        <div class="login-bg-shape login-bg-shape-1"></div>
        <div class="login-bg-shape login-bg-shape-2"></div>
        
        <!-- Main centered login card -->
        <div class="login-card">
            
            <!-- Brand Identity -->
            <a href="index.php" class="login-brand text-decoration-none">
                <i class="bi bi-asterisk"></i>
                <span>Barber-Box</span>
            </a>
            
            <p class="login-subtitle">Por favor inicie sesion</p>
            
            <!-- Login Form -->
            <form id="form-login" method="POST" class="needs-validation" novalidate>
                
                <!-- Email Input Group -->
                <div class="login-form-group">
                    <label for="email" class="login-form-label">Usuario</label>
                    <div class="login-input-group">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="text" id="usuario"  name="usuario" class="login-input form-control" placeholder="Juan" required>
                    </div>
                </div>
                
                <!-- Password Input Group -->
                <div class="login-form-group">
                    <label for="password" class="login-form-label">Contraseña</label>
                    <div class="login-input-group">
                        <i class="bi bi-shield-lock input-icon"></i>
                        <input type="password" id="clave" name="clave" class="login-input login-input-password form-control" placeholder="••••••••" required>
                        <button type="button" class="password-toggle-btn" id="toggle-password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Options (Remember me & Forgot Password) -->
                <div class="login-options">
                    <label class="custom-control-label">
                        <input type="checkbox" class="custom-checkbox-input" id="rememberMe">
                        <span>Recordarme </span>
                    </label>
                    <a href="#" class="forgot-password-link">Olvide mi contraseña</a>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn-login" id="btn-login">
                    <span>Iniciar Sesión</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
                
            </form>


            <!-- Divider -->
<!---
            <div class="login-divider">O continuar con</div>
            
            <div class="social-login-grid">
                <button class="btn-social" type="button" id="btn-google">
                    <i class="bi bi-google text-danger"></i>
                    <span>Google</span>
                </button>
               
            </div>
            -->

            
            <!-- Footer Link -->
            <p class="login-footer-text">
                No tienes una cuenta? <a href="/barberBox/views/form-registrar-cliente.php" id="link-register">Registrate</a>
            </p>

            <div class="d-flex justify-content-center gap-2 mt-3">
                <button class="btn-custom btn-custom-light" type="button" onclick="location.href='/barberBox/home.php'">Volver al Inicio</button>

            </div>

            
        </div>
    </div>
    <!-- END: Authentication Container -->

    <!-- Local Bootstrap bundle -->
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    
    <!-- Custom Authentication interactions script -->
    <script src="/barberBox/assets/js/JSLogin.js"></script>

</body>
</html>
