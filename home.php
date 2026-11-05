<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BarberShop - Tu Estilo, Tu Ley</title>

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="assets/images/favicon.ico">

  <!-- Estilos base y Bootstrap Icons -->
  <link rel="stylesheet" href="assets/libs/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/libs/bootstrap-icons/bootstrap-icons.css">

  <!-- Estilos personalizados -->
  <link rel="stylesheet" href="assets/css/main.css">
</head>

<body class="bg-dark text-white">

  <!-- ==========================================
       Navegación Pública (Navbar)
       ========================================== -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-secondary sticky-top px-4 py-3">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-white fs-4" href="index.php">
        <i class="bi bi-scissors text-danger fs-3"></i>
        <span>BarberShop</span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarLanding">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarLanding">
        <ul class="navbar-menu navbar-nav mx-auto mb-2 mb-lg-0 fw-semibold">
          <li class="nav-item"><a class="nav-link text-white" href="#servicios">Servicios</a></li>
          <li class="nav-item"><a class="nav-link text-white" href="#contacto">Contacto</a></li>
        </ul>

        <div class="d-flex gap-2">
          <a href="/barberBox/page-login.php" class="btn btn-outline-light px-4">
            <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
          </a>
          <a href="/barberBox/views/form-registrar-cliente.php" class="btn btn-danger px-4">
            <i class="bi bi-person-plus me-1"></i> Registrarse
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- ==========================================
       Hero Section (Bienvenida)
       ========================================== -->
<section class="py-5 text-center position-relative border-bottom border-secondary bg-black" style="background: linear-gradient(rgba(0, 0, 0, 0.85), rgba(0, 0, 0, 0.85)), url('assets/images/barber1.jpg') center/cover no-repeat;">
        <div class="container py-5">
      <span class="badge bg-danger text-uppercase px-3 py-2 mb-3">La experiencia que mereces</span>
      <h1 class="display-3 fw-bold mb-3 text-white">CORTE & ESTILO CLÁSICO</h1>
      <p class="lead text-light mx-auto mb-4" style="max-width: 600px;">
        Reserva tu cita en línea en segundos, elige a tu barbero preferido y disfruta de una atención de primera clase.
      </p>
      <div class="d-flex justify-content-center gap-3">
        <a href="page-login.php" class="btn btn-danger btn-lg px-4 fw-bold">
          <i class="bi bi-calendar-check me-2"></i>Agendar Cita Ahora
        </a>
        <a href="#servicios" class="btn btn-outline-primary btn-lg px-4 fw-bold">
          Ver Servicios
        </a>
      </div>
    </div>
  </section>

  <!-- ==========================================
       Nuestros Servicios
       ========================================== -->
  <section id="servicios" class="py-5 border-bottom border-secondary">
    <div class="container py-4">
      <div class="text-center mb-5">
        <h2 class="fw-bold text-white"><i class="bi bi-scissors text-danger me-2"></i>Nuestros Servicios</h2>
        <p class="text-muted">Servicios profesionales para mantener tu estilo al día.</p>
      </div>

      <div class="row g-4">
        <div class="col-12 col-md-4">
          <div class="card bg-black text-white border-secondary h-100 p-4 text-center">
            <div class="p-3 bg-danger bg-opacity-20 text-danger rounded-circle mx-auto mb-3" style="width: 60px; height: 60px;">
              <i class="bi bi-scissors fs-3"></i>
            </div>
            <h4 class="fw-bold text-light mb-2">Corte Tradicional</h4>
            <p class="text-white-50 small">Corte a tijera o máquina, incluye lavado con masaje capilar y peinado con fijación.</p>
            <h5 class="text-primary fw-bold mt-auto">$15.00</h5>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="card bg-black text-white border-secondary h-100 p-4 text-center">
            <div class="p-3 bg-primary bg-opacity-20 text-primary rounded-circle mx-auto mb-3" style="width: 60px; height: 60px;">
              <i class="bi bi-slash-circle fs-3"></i>
            </div>
            <h4 class="fw-bold text-light mb-2">Afeitado / Barba</h4>
            <p class="text-white-50 small">Ritual de toalla caliente, perfilado con navaja y bálsamo hidratante.</p>
            <h5 class="text-primary fw-bold mt-auto">$12.00</h5>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="card bg-black text-white border-secondary h-100 p-4 text-center">
            <div class="p-3 bg-danger bg-opacity-20 text-danger rounded-circle mx-auto mb-3" style="width: 60px; height: 60px;">
              <i class="bi bi-star-fill fs-3"></i>
            </div>
            <h4 class="fw-bold text-light mb-2">Combo BarberShop</h4>
            <p class="text-white-50 small">Corte completo + Servicio de Barba completo + Bebida de cortesía.</p>
            <h5 class="text-primary fw-bold mt-auto">$25.00</h5>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ==========================================
       Llamado a la Acción (Call To Action)
       ========================================== -->
  <section class="py-5 bg-black border-bottom border-secondary text-center">
    <div class="container py-4">
      <h2 class="fw-bold text-white mb-3">¿Listo para renovar tu look?</h2>
      <p class="text-muted mb-4">Crea una cuenta o inicia sesión para gestionar tus reservas y ver tus citas anteriores.</p>
      <a href="page-login.php" class="btn btn-primary btn-lg px-5 fw-bold">
        Acceder al Portal del Cliente
      </a>
    </div>
  </section>

  <!-- ==========================================
       Footer
       ========================================== -->
  <footer class="py-4 bg-black text-muted border-top border-secondary text-center">
    <div class="container">
      <div class="d-flex justify-content-center gap-2 align-items-center mb-2">
        <i class="bi bi-scissors text-danger"></i>
        <span class="text-white fw-bold">BarberShop</span>
      </div>
      <small>&copy; <?= date('Y'); ?> BarberShop. Todos los derechos reservados.</small>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>