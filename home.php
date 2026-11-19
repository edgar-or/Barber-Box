<?php
require_once __DIR__ . '/config/conexion.php';

// Servicios activos desde la BD
$servicios = [];
$res = $conexion->query(
    "SELECT id_servicio, nombre_servicio, descripcion, precio, duracion_estimada
     FROM servicio WHERE estado_servicio = 'A' ORDER BY nombre_servicio"
);
if ($res) {
    while ($f = $res->fetch_assoc()) { $f['imagen'] = null; $servicios[$f['id_servicio']] = $f; }
    $res->free();
}

// Primera imagen de cada servicio (la tabla puede no existir aún)
try {
    $resImg = $conexion->query("SELECT id_servicio, MIN(ruta_imagen) AS ruta FROM servicio_imagen GROUP BY id_servicio");
    if ($resImg) {
        while ($i = $resImg->fetch_assoc()) {
            if (isset($servicios[$i['id_servicio']])) { $servicios[$i['id_servicio']]['imagen'] = $i['ruta']; }
        }
    }
} catch (Throwable $e) { /* sin imágenes */ }
$conexion->close();
?><!DOCTYPE html>
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
  <link rel="stylesheet" href="assets/css/registro.css">
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
  <section class="home-hero text-center position-relative border-bottom border-secondary">
    <div class="container py-5">
      <span class="badge bg-danger text-uppercase px-3 py-2 mb-3">La experiencia que mereces</span>
      <h1 class="display-3 mb-3 text-white">CORTE &amp; <span>ESTILO</span> CLÁSICO</h1>
      <p class="lead text-light mx-auto mb-4" style="max-width: 620px;">
        Reserva tu cita en línea en segundos, elige a tu barbero preferido y disfruta de una atención de primera clase.
      </p>
      <div class="d-flex flex-wrap justify-content-center gap-3 mb-5">
        <a href="page-login.php" class="btn btn-danger btn-lg px-4 fw-bold">
          <i class="bi bi-calendar-check me-2"></i>Agendar Cita Ahora
        </a>
        <a href="#servicios" class="btn btn-outline-light btn-lg px-4 fw-bold">Ver Servicios</a>
      </div>
      <div class="home-stats row g-3 justify-content-center">
        <div class="col-6 col-md-3"><div class="stat"><strong class="text-danger"><?= count($servicios); ?></strong><small class="text-white-50">Servicios</small></div></div>
        <div class="col-6 col-md-3"><div class="stat"><strong class="text-danger">100%</strong><small class="text-white-50">Reserva en línea</small></div></div>
      </div>
    </div>
  </section>

  <!-- ==========================================
       Nuestros Servicios (dinámicos desde la BD)
       ========================================== -->
  <section id="servicios" class="py-5 border-bottom border-secondary">
    <div class="container py-4">
      <div class="text-center mb-5">
        <h2 class="section-title text-white"><i class="bi bi-scissors text-danger me-2"></i>Nuestros Servicios</h2>
        <p class="text-white-50 mt-3">Servicios profesionales para mantener tu estilo al día.</p>
      </div>

      <div class="row g-4 justify-content-center">
        <?php if (empty($servicios)): ?>
          <div class="col-12 text-center text-white-50">
            <i class="bi bi-info-circle fs-3 d-block mb-2"></i>Pronto publicaremos nuestros servicios.
          </div>
        <?php endif; ?>

        <?php foreach ($servicios as $s): ?>
          <div class="col-12 col-sm-6 col-lg-4">
            <div class="service-card h-100 d-flex flex-column">
              <div class="service-media">
                <?php if (!empty($s['imagen'])): ?>
                  <img src="<?= htmlspecialchars($s['imagen']); ?>" alt="<?= htmlspecialchars($s['nombre_servicio']); ?>" loading="lazy">
                <?php else: ?>
                  <i class="bi bi-scissors"></i>
                <?php endif; ?>
              </div>
              <div class="p-4 d-flex flex-column flex-grow-1 text-center">
                <h4 class="fw-bold text-light mb-2"><?= htmlspecialchars($s['nombre_servicio']); ?></h4>
                <p class="text-white-50 small flex-grow-1"><?= htmlspecialchars($s['descripcion'] ?? ''); ?></p>
                <div class="service-meta mb-2"><i class="bi bi-clock me-1"></i><?= (int)$s['duracion_estimada']; ?> min</div>
                <div class="service-price">$<?= number_format((float)$s['precio'], 2); ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ==========================================
       Llamado a la Acción
       ========================================== -->
  <section class="cta-section py-5 border-bottom border-secondary text-center">
    <div class="container py-4">
      <h2 class="fw-bold text-white mb-3">¿Listo para renovar tu look?</h2>
      <p class="text-white-50 mb-4">Crea una cuenta o inicia sesión para gestionar tus reservas y ver tus citas anteriores.</p>
      <a href="page-login.php" class="btn btn-danger btn-lg px-5 fw-bold">Acceder al Portal del Cliente</a>
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