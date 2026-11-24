<?php
require_once __DIR__ . "/../../config/auth.php";
$currentPage = $currentPage ?? "";
$esCliente = esCliente();
$nombreSidebar = $_SESSION["usuario"] ?? "Invitado";
$rolSidebar = $_SESSION["rol_nombre"] ?? "";
?>

<!-- ==========================================
     START: Sidebar Component
     Highly polished, dark-green sticky navigation
     ========================================== -->
<div class="sidebar-wrapper" id="sidebar">
  <!-- Brand Logo / Identity -->
  <a href="<?= $esCliente ? "/barberBox/views/cliente-panel.php" : (esEmpleado() ? "/barberBox/views/panel-agendar-cita-admin.php" : "/barberBox/home.php"); ?>" class="sidebar-brand">
    <i class="bi bi-asterisk"></i>
    <span>BARBER-BOX</span>
  </a>

  <!-- Navigation Menu -->
  <div class="flex-grow-1 overflow-y-auto">
    <?php if ($esCliente): ?>
    <!-- Menú del cliente: agendar y ver historial -->
    <div class="sidebar-menu-section">
      <div class="sidebar-menu-title">Mi cuenta</div>
      <ul class="sidebar-menu-list">
        <li class="sidebar-menu-item">
          <a href="/barberBox/home.php" class="sidebar-menu-link <?= ($currentPage === 'home') ? 'active' : ''; ?>" id="menu-home">
            <i class="bi bi-house"></i>
            <span>Home</span>
          </a>
        </li>
        <li class="sidebar-menu-item">
          <a href="/barberBox/views/cliente-panel.php#agendar" class="sidebar-menu-link <?= ($currentPage === 'cliente-panel') ? 'active' : ''; ?>" data-tab="agendar">
            <i class="bi bi-calendar-plus"></i>
            <span>Agendar cita</span>
          </a>
        </li>
        <li class="sidebar-menu-item">
          <a href="/barberBox/views/cliente-panel.php#historial" class="sidebar-menu-link" data-tab="historial">
            <i class="bi bi-clock-history"></i>
            <span>Mi historial</span>
          </a>
        </li>
      </ul>
    </div>
    <?php elseif (esEmpleado()): ?>
    <!-- Menú del empleado (administrador / barbero) -->

    <!-- SECCIÓN 1: PRINCIPAL -->
    <div class="sidebar-menu-section">
      <div class="sidebar-menu-title">Principal</div>
      <ul class="sidebar-menu-list">
        <li class="sidebar-menu-item">
          <a href="/barberBox/home.php" class="sidebar-menu-link <?= ($currentPage === 'home') ? 'active' : ''; ?>" id="menu-home">
            <i class="bi bi-house"></i>
            <span>Home</span>
          </a>
        </li>
        <li class="sidebar-menu-item">
          <a href="/barberBox/views/panel-agendar-cita-admin.php" class="sidebar-menu-link <?= ($currentPage === 'agendar-admin') ? 'active' : ''; ?>" id="menu-agendar-admin">
            <i class="bi bi-calendar-plus"></i>
            <span>Agendar cita</span>
          </a>
        </li>
      </ul>
    </div>

    <!-- SECCIÓN 2: GESTIÓN -->
    <div class="sidebar-menu-section">
      <div class="sidebar-menu-title">Gestión</div>
      <ul class="sidebar-menu-list">
        <li class="sidebar-menu-item">
          <a href="/barberBox/views/tabla-clientes.php" class="sidebar-menu-link <?= ($currentPage === 'clientes') ? 'active' : ''; ?>" id="menu-clientes" title="Overview">
            <i class="bi bi-grid-fill"></i>
            <span>Clientes</span>
          </a>
        </li>
        <?php if (esAdmin()): ?>
        <li class="sidebar-menu-item">
          <a href="/barberBox/views/tabla-empleados.php" class="sidebar-menu-link <?= ($currentPage === 'empleados') ? 'active' : ''; ?>" id="menu-empleados" title="Overview">
            <i class="bi bi-grid-fill"></i>
            <span>Empleados</span>
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- SECCIÓN 3: CONFIGURACIÓN (SOLO ADMIN) -->
    <?php if (esAdmin()): ?>
    <div class="sidebar-menu-section">
      <div class="sidebar-menu-title">Configuración</div>
      <ul class="sidebar-menu-list">
        <li class="sidebar-menu-item">
          <a href="/barberBox/views/tabla-servicios.php" class="sidebar-menu-link <?= ($currentPage === 'servicios') ? 'active' : ''; ?>" id="menu-servicios" title="Overview">
            <i class="bi bi-grid-fill"></i>
            <span>Servicios</span>
          </a>
        </li>
        <li class="sidebar-menu-item">
          <a href="/barberBox/views/comision.php" class="sidebar-menu-link <?= ($currentPage === 'comision') ? 'active' : ''; ?>" id="menu-comision" title="Comisión">
            <i class="bi bi-percent"></i>
            <span>Comisión</span>
          </a>
        </li>
      </ul>
    </div>
    <?php endif; ?>

    <!-- SECCIÓN 4: REPORTES -->
    <div class="sidebar-menu-section">
      <div class="sidebar-menu-title">Reportes</div>
      <ul class="sidebar-menu-list">
        <?php if (esAdmin()): ?>
        <li class="sidebar-menu-item">
          <a href="/barberBox/views/reportes-cortes.php" class="sidebar-menu-link <?= ($currentPage === 'reportes') ? 'active' : ''; ?>" id="menu-reportes" title="Reporte de Cortes">
            <i class="bi bi-file-earmark-bar-graph-fill"></i>
            <span>Reporte de Cortes</span>
          </a>
        </li>
        <?php endif; ?>

        <li class="sidebar-menu-item">
          <a href="/barberBox/views/tabla-citas.php" class="sidebar-menu-link <?= ($currentPage === 'citas') ? 'active' : ''; ?>" id="menu-citas">
            <i class="bi bi-calendar-check"></i>
            <span><?= esAdmin() ? 'Historial de citas' : 'Mis citas'; ?></span>
          </a>
        </li>
      </ul>
    </div>

    <?php else: ?>
    <!-- Visitante sin sesión: solo Home -->
    <div class="sidebar-menu-section">
      <div class="sidebar-menu-title">Menu</div>
      <ul class="sidebar-menu-list">
        <li class="sidebar-menu-item">
          <a href="/barberBox/home.php" class="sidebar-menu-link <?= ($currentPage === 'home') ? 'active' : ''; ?>" id="menu-home">
            <i class="bi bi-house"></i>
            <span>Home</span>
          </a>
        </li>
      </ul>
    </div>
    <?php endif; ?>
  </div>

  <!-- Sidebar Profile Card (Dynamic Footer) -->
  <div class="sidebar-profile">
    <img src="/barberBox/assets/images/avatar.png" alt="Administrator" class="sidebar-profile-img"
      onerror="this.src='https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=256&auto=format&fit=crop'">
    <div class="sidebar-profile-info">
      <div class="sidebar-profile-name"><?= htmlspecialchars($nombreSidebar); ?></div>
      <div class="sidebar-profile-email"><?= htmlspecialchars($rolSidebar); ?></div>
    </div>
  </div>
</div>
<!-- ==========================================
     END: Sidebar Component
     ========================================== -->