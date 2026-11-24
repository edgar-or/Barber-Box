<?php
require_once __DIR__ . "/config/auth.php";

// Página pública: cualquiera puede verla. Solo se ajustan los botones según la sesión.
$currentPage = 'home';

if (esCliente()) {
  $urlPanel = "/barberBox/views/cliente-panel.php";
} elseif (esEmpleado()) {
  $urlPanel = "/barberBox/views/panel-agendar-cita-admin.php";
} else {
  $urlPanel = "/barberBox/page-login.php";
}
?><!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Barber-Box - Tu Estilo, Tu Ley</title>

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="assets/images/favicon.ico">

  <!-- Estilos base y Bootstrap Icons -->
  <link rel="stylesheet" href="assets/libs/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/libs/bootstrap-icons/bootstrap-icons.css">

  <!-- Estilos personalizados -->
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/registro.css">
</head>

<body class="bg-dark text-white pagina-home">
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
          <?php if (sesionActiva()): ?>
            <a href="<?= $urlPanel; ?>" class="btn btn-danger px-4">
              <i class="bi bi-person-circle me-1"></i> Ir a mi panel
            </a>
          <?php else: ?>
            <a href="/barberBox/page-login.php" class="btn btn-outline-light px-4">
              <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
            </a>
            <a href="/barberBox/views/form-registrar-cliente.php" class="btn btn-danger px-4">
              <i class="bi bi-person-plus me-1"></i> Registrarse
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>

  <!-- ==========================================
       Hero Section (Bienvenida)
       ========================================== -->
  <section class="home-hero text-center position-relative border-bottom border-secondary">
    <div class="container py-5">
      <span class="badge bg-danger text-uppercase px-3 py-2 mb-3">La experiencia que mereces</span>
      <h1 class="display-3 mb-3 text-white">CORTE &amp; <span>ESTILO</span> CLÁSICO</h1>
      <p class="lead text-light mx-auto mb-4" style="max-width: 620px;">
        Reserva tu cita en línea en segundos, elige a tu barbero preferido y disfruta de una atención de primera clase.
      </p>
      <div class="d-flex flex-wrap justify-content-center gap-3 mb-5">
        <a href="<?= $urlPanel; ?>" class="btn btn-danger btn-lg px-4 fw-bold">
          <i class="bi bi-calendar-check me-2"></i>Agendar Cita Ahora
        </a>
        <a href="#servicios" class="btn btn-outline-light btn-lg px-4 fw-bold">Ver Servicios</a>
      </div>
      <div class="home-stats row g-3 justify-content-center">
        <div class="col-6 col-md-3"><div class="stat"><strong class="text-danger" id="totalServicios">0</strong><small class="text-white-50">Servicios</small></div></div>
        <div class="col-6 col-md-3"><div class="stat"><strong class="text-danger">100%</strong><small class="text-white-50">Reserva en línea</small></div></div>
      </div>
    </div>
  </section>

  <!-- ==========================================
       Nuestros Servicios (se cargan desde DAOHome.php)
       ========================================== -->
  <section id="servicios" class="py-5 border-bottom border-secondary">
    <div class="container py-4">
      <div class="text-center mb-5">
        <h2 class="section-title text-white"><i class="bi bi-scissors text-danger me-2"></i>Nuestros Servicios</h2>
        <p class="text-white-50 mt-3">Servicios profesionales para mantener tu estilo al día.</p>
      </div>

      <div class="row g-4 justify-content-center" id="listaServicios">
        <div class="col-12 text-center text-white-50">Cargando servicios...</div>
      </div>
    </div>
  </section>

  <!-- ==========================================
       Contacto (datos ficticios)
       ========================================== -->
  <section id="contacto" class="py-5 border-bottom border-secondary">
    <div class="container py-4">
      <div class="text-center mb-5">
        <h2 class="section-title text-white"><i class="bi bi-geo-alt text-danger me-2"></i>Contacto</h2>
        <p class="text-white-50 mt-3">Escríbenos o visítanos, con gusto te atendemos.</p>
      </div>

      <div class="row g-4 justify-content-center">
        <div class="col-12 col-md-6 col-lg-3">
          <div class="h-100 p-4 text-center bg-black border border-secondary rounded-4">
            <i class="bi bi-geo-alt-fill text-danger fs-2 d-block mb-3"></i>
            <h5 class="fw-bold text-light mb-2">Dirección</h5>
            <p class="text-white-50 small mb-0">Colonia Escalón, Calle Ejemplo #123<br>San Salvador, El Salvador</p>
          </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
          <div class="h-100 p-4 text-center bg-black border border-secondary rounded-4">
            <i class="bi bi-telephone-fill text-danger fs-2 d-block mb-3"></i>
            <h5 class="fw-bold text-light mb-2">Teléfono</h5>
            <p class="text-white-50 small mb-0">
              <a href="tel:+50322220000" class="text-white-50 text-decoration-none">+503 2222-0000</a><br>
              <a href="https://wa.me/50370000000" class="text-white-50 text-decoration-none">WhatsApp: 7000-0000</a>
            </p>
          </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
          <div class="h-100 p-4 text-center bg-black border border-secondary rounded-4">
            <i class="bi bi-envelope-fill text-danger fs-2 d-block mb-3"></i>
            <h5 class="fw-bold text-light mb-2">Correo</h5>
            <p class="text-white-50 small mb-0">
              <a href="mailto:contacto@barberbox.example" class="text-white-50 text-decoration-none">contacto@barberbox.example</a>
            </p>
          </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
          <div class="h-100 p-4 text-center bg-black border border-secondary rounded-4">
            <i class="bi bi-clock-fill text-danger fs-2 d-block mb-3"></i>
            <h5 class="fw-bold text-light mb-2">Horario</h5>
            <p class="text-white-50 small mb-0">Lunes a sábado<br>8:00 a.m. - 5:00 p.m.<br>Domingo: cerrado</p>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-center gap-2 mt-5">
        <a href="#" class="btn btn-outline-light" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
        <a href="#" class="btn btn-outline-light" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
        <a href="https://wa.me/50370000000" class="btn btn-outline-light" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
      </div>
    </div>
  </section>

  <!-- ==========================================
       Llamado a la Acción (solo para quien no ha iniciado sesión)
       ========================================== -->
  <?php if (!sesionActiva()): ?>
  <section class="cta-section py-5 border-bottom border-secondary text-center">
    <div class="container py-4">
      <h2 class="fw-bold text-white mb-3">¿Listo para renovar tu look?</h2>
      <p class="text-white-50 mb-4">Crea una cuenta o inicia sesión para gestionar tus reservas y ver tus citas anteriores.</p>
      <a href="page-login.php" class="btn btn-danger btn-lg px-5 fw-bold">Acceder al Portal del Cliente</a>
    </div>
  </section>
  <?php endif; ?>

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
  <script src="assets/js/JSHome.js?v=1<?php echo filemtime('assets/js/JSHome.js'); ?>"></script>
  <link rel="stylesheet" href="assets/css/tema-barberbox.css?v=14">
</body>

</html>
