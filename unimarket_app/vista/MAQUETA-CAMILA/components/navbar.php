<nav class="navbar uni-navbar">

  <div class="navbar-logo uni-brand" onclick="showView('home')">
    <img src="assets/img/unimagdalena-logo.png" alt="Universidad del Magdalena" class="brand-unimag-logo">
    <div>
      <strong>UniMarket</strong>
      <span>Universidad del Magdalena</span>
    </div>
  </div>

  <div class="navbar-search">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <circle cx="11" cy="11" r="8"/>
      <path d="m21 21-4.35-4.35"/>
    </svg>
    <input type="text" placeholder="Buscar productos, comida, papelería...">
  </div>

  <div class="navbar-actions">

    <?php if ($idRol == 1): ?>
      <button type="button" class="btn-nav-link" onclick="showView('publish')">Publicar</button>
    <?php endif; ?>

    <?php if ($idRol != 1): ?>
      <button type="button" class="btn-nav-link" onclick="showView('purchases')">Mis compras</button>
      <button type="button" class="btn-nav-link" onclick="showView('cart')">🛒 Carrito</button>
    <?php endif; ?>

    <?php if ($nombreUsuario): ?>
      <span class="btn-nav-link hello-user">👋 Hola, <?php echo htmlspecialchars($nombreUsuario); ?></span>
      <a href="../../controlador/UsuarioController.php?accion=logout" class="logout-pill">Salir</a>
    <?php else: ?>
      <button type="button" class="btn-primary" onclick="showView('auth')">Iniciar sesión</button>
    <?php endif; ?>

    <?php if ($nombreUsuario): ?>
    <div class="notifications-shell">
      <button type="button" class="nav-icon-btn notification-btn" id="btn-noti" onclick="toggleNotificacionesDropdown()">
        🔔
        <span id="noti-badge" class="noti-badge" style="<?php echo empty($notificacionesNoLeidas) ? 'display:none;' : ''; ?>">
          <?php echo (int)$notificacionesNoLeidas; ?>
        </span>
      </button>

      <div id="notificaciones-dropdown" class="notifications-dropdown" style="display:none;">
        <div class="notifications-head">
          <div>
            <strong>Notificaciones</strong>
            <span>Actividad reciente de UniMarket</span>
          </div>
          <button type="button" onclick="marcarTodasNotificaciones()">Leer todo</button>
        </div>

        <div class="noti-filters">
          <button type="button" class="active" data-tipo="Todas" onclick="filtrarNotificaciones('Todas', this)">Todas</button>
          <button type="button" data-tipo="Compras" onclick="filtrarNotificaciones('Compras', this)">Compras</button>
          <button type="button" data-tipo="Pedidos" onclick="filtrarNotificaciones('Pedidos', this)">Pedidos</button>
          <button type="button" data-tipo="Flash" onclick="filtrarNotificaciones('Flash', this)">Flash</button>
          <button type="button" data-tipo="Sistema" onclick="filtrarNotificaciones('Sistema', this)">Sistema</button>
        </div>

        <div id="notificaciones-lista" class="notifications-list">
          <p class="noti-empty">Cargando notificaciones...</p>
        </div>

        <button type="button" class="notifications-view-all" onclick="showView('notifications'); toggleNotificacionesDropdown(false);">
          Ver bandeja completa
        </button>
      </div>
    </div>
    <?php endif; ?>

    <button type="button" class="nav-icon-btn" onclick="showView('messages')">✉️</button>
    <button type="button" class="nav-icon-btn" onclick="showView('profile')">👤</button>
    <button type="button" class="hamburger nav-icon-btn" onclick="toggleMobileMenu()">☰</button>
  </div>

</nav>
