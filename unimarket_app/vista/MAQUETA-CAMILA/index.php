<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="es">

<?php
session_start();
$isLoggedIn = isset($_SESSION['id_usuario']);
$nombreUsuario = $isLoggedIn ? $_SESSION['nombre'] : null;
$idRol = isset($_SESSION['id_rol']) ? $_SESSION['id_rol'] : null;

$modoEditar = false;
$productoEditar = null;

if(isset($_GET['editar'])){

    $modoEditar = true;

    require_once "../../modelo/Producto.php";

    $productoModel = new Producto();

    $productoEditar =
        $productoModel->obtenerPorId($_GET['editar']);

}

// ==========================================
// VISTA INICIAL
// ==========================================

$defaultView = 'auth';

if ($isLoggedIn) {
    $defaultView = 'home';
}

// ==========================================
// SI ESTÁ EDITANDO PRODUCTO
// ==========================================

if (isset($_GET['token'])) {

    $defaultView = 'reset';

} elseif (isset($_GET['editar'])) {

    $defaultView = 'publish';

} else {

    // ==========================================
    // CAMBIO NORMAL DE VISTAS
    // ==========================================

    if (isset($_GET['view'])) {

        $allowedViews = [
            'home',
            'flash',
            'product',
            'store',
            'publish',
            'profile',
            'messages',
            'search',
            'purchases',
            'cart',
            'checkout',
            'forgot',
            'reset',
            'edit-profile',
            'notifications'
        ];

        if (in_array($_GET['view'], $allowedViews)) {
            $defaultView = $_GET['view'];
        }
    }
}

require_once __DIR__ . "/../../modelo/Favorito.php";
require_once __DIR__ . "/../../modelo/Usuario.php";
require_once __DIR__ . "/../../modelo/Producto.php";
require_once __DIR__ . "/../../modelo/Venta.php";
require_once __DIR__ . "/../../modelo/Notificacion.php";


$favModel = new Favorito();
$userFavs = $isLoggedIn ? array_column($favModel->listarPorUsuario($_SESSION['id_usuario']), 'id_producto') : [];

// Migración automática para la columna imagen
try {
    $db_migra = Conexion::conectar();
    $db_migra->exec("ALTER TABLE productos ADD COLUMN imagen VARCHAR(255) DEFAULT NULL AFTER ubicacion");
} catch (PDOException $e) {}

$usuarioModel = new Usuario();
$prodModel = new Producto();

$usuario = [];

if($isLoggedIn){

    $usuario =
        $usuarioModel->buscarPorId(
            $_SESSION['id_usuario']
        );

}

// Cargar un vendedor por defecto para la vista de la tienda dinámica
$vendedorDefault = ['nombre' => 'Tienda Unimagdalena', 'id_usuario' => 0];
$productosStore = [];
$todosLosUsuarios = $usuarioModel->listarTodos();
foreach ($todosLosUsuarios as $u) {
    if ($u['id_rol'] == 1) {
        $vendedorDefault = $u;
        $productosStore = $prodModel->listarPorUsuario($u['id_usuario']);
        break;
    }
}

// Cargar productos adicionales
$productosAdicionales = array_slice($prodModel->listarSencillo(), 0, 3);

$productosCarrito = [];
$misProductos = [];
$misFlash = [];
$favoritosUsuario = [];
$misCompras = [];
$pedidosVendedor = [];
$puntosEntrega = [];
$notificacionesUsuario = [];
$notificacionesNoLeidas = 0;

if ($isLoggedIn) {
    require_once __DIR__ . "/../../modelo/Carrito.php";
    $carritoModelGlobal = new Carrito();
    $productosCarrito = $carritoModelGlobal->listar($_SESSION['id_usuario']);

    $misProductos = $prodModel->listarPorUsuario($_SESSION['id_usuario']);

    require_once __DIR__ . "/../../modelo/VentaFlash.php";
    $vfModelGlobal = new VentaFlash();
    $misFlash = $vfModelGlobal->listarPorUsuario($_SESSION['id_usuario']);

    $favoritosUsuario = $favModel->listarPorUsuario($_SESSION['id_usuario']);

    $ventaModelGlobal = new Venta();
    $misCompras = $ventaModelGlobal->listarPorComprador($_SESSION['id_usuario']);
    if ($idRol == 1) {
        $pedidosVendedor = $ventaModelGlobal->listarPedidosVendedor($_SESSION['id_usuario']);
    }

    $notificacionModelGlobal = new Notificacion();
    $notificacionesUsuario = $notificacionModelGlobal->listarTodas($_SESSION['id_usuario']);
    $notificacionesNoLeidas = count($notificacionModelGlobal->listarNoLeidas($_SESSION['id_usuario']));
}

try {
    $dbPuntos = Conexion::conectar();
    $puntosEntrega = $dbPuntos->query("SELECT * FROM puntos_encuentro ORDER BY nombre_lugar ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $puntosEntrega = [];
$notificacionesUsuario = [];
$notificacionesNoLeidas = 0;
}

if (empty($puntosEntrega)) {
    $puntosEntrega = [
        ['id_punto' => 1, 'nombre_lugar' => 'Cafetería Central', 'referencia' => 'Mesas'],
        ['id_punto' => 2, 'nombre_lugar' => 'Zona de emprendimientos', 'referencia' => 'Campus'],
        ['id_punto' => 3, 'nombre_lugar' => 'Edificio Mar Caribe', 'referencia' => 'Entrada principal'],
        ['id_punto' => 4, 'nombre_lugar' => 'Edificio Ciénaga Grande', 'referencia' => 'Entrada principal'],
        ['id_punto' => 5, 'nombre_lugar' => 'Edificio Sierra Sur', 'referencia' => 'Entrada principal'],
    ];
}

$ventasFlashActivasInicio = [];
try {
    require_once __DIR__ . "/../../modelo/VentaFlash.php";
    $vfInicio = new VentaFlash();
    $ventasFlashActivasInicio = $vfInicio->listarActivas();
} catch (Throwable $e) {
    $ventasFlashActivasInicio = [];
}

$profileTab = $_GET['tab'] ?? (($idRol == 1) ? 'productos' : 'favoritos');
$authMode = (isset($_GET['mode']) && $_GET['mode'] === 'signup') ? 'signup' : 'signin';
?>



<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UniMarket — Universidad del Magdalena</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style/styles.css">
</head>

<body data-logged="<?php echo isset($_SESSION['id_usuario']) ? '1' : '0'; ?>">
<?php
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
$resetLinkLocal = $_SESSION['reset_link_local'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_type'], $_SESSION['reset_link_local']);
?>

<?php include 'components/navbar.php'; ?>

<?php if ($flashMessage): ?>
  <div class="um-toast um-toast-<?php echo htmlspecialchars($flashType); ?>">
    <div class="um-toast-title">UniMarket</div>
    <div><?php echo htmlspecialchars($flashMessage); ?></div>
    <?php if ($resetLinkLocal): ?>
      <a class="um-toast-link" href="<?php echo htmlspecialchars($resetLinkLocal); ?>">Abrir enlace de recuperación</a>
    <?php endif; ?>
    <button type="button" onclick="this.parentElement.remove()" class="um-toast-close">×</button>
  </div>
<?php endif; ?>
<div id="fav-update-trigger" style="<?php echo ($profileTab === 'flash') ? 'display:block;' : 'display:none;'; ?>"></div>

<div id="view-home" class="view <?php echo ($defaultView == 'home') ? 'active' : ''; ?>">
  <div class="container">

    <section class="unimag-hero">
      <div class="unimag-hero-bg"></div>
      <div class="unimag-hero-content">
        <div class="unimag-kicker">
          <img src="assets/img/unimagdalena-logo.png" alt="Logo Universidad del Magdalena">
          <span>Marketplace universitario · Campus Unimagdalena</span>
        </div>
        <h1>Compra, vende y emprende dentro de la <span class="gold">Universidad del Magdalena.</span></h1>
        <p>
          UniMarket conecta estudiantes para publicar productos, ventas flash, pedidos y pagos seguros en puntos de entrega del campus.
        </p>
        <div class="unimag-hero-actions">
          <?php if ($idRol == 1): ?>
            <button class="btn-primary btn-hero-gold" onclick="showView('publish')">Publicar producto</button>
            <button class="btn-hero-outline" onclick="showView('profile')">Ver mi tienda</button>
            <button class="btn-hero-outline" onclick="showView('flash')">Ventas flash</button>
          <?php else: ?>
            <button class="btn-primary btn-hero-gold" onclick="showView('flash')">Ver ofertas flash</button>
            <button class="btn-hero-outline" onclick="openFlashModal()">Publicar venta flash</button>
          <?php endif; ?>
        </div>
      </div>
      <div class="unimag-hero-panel">
        <div class="hero-banner-card">
          <img class="hero-banner-image" src="assets/img/home-hero-clean.png" alt="Banner principal de UniMarket Universidad del Magdalena">
        </div>
      </div>
    </section>


    <div class="home-layout">
      <div class="home-main">

        <div class="unimag-section-heading home-heading-redesign"><span>Explora el campus</span><h2>Productos destacados</h2><p>Encuentra artículos y servicios publicados por estudiantes y emprendedores Unimagdalena.</p></div>

        <div class="cat-pills">
          <button class="cat-pill active" data-target="all">Todo</button>
          <button class="cat-pill" data-target="comida">Comida / Snacks</button>
          <button class="cat-pill" data-target="tecnologia">Tecnología</button>
          <button class="cat-pill" data-target="ropa">Ropa</button>
          <button class="cat-pill" data-target="papeleria">Papelería</button>
          <button class="cat-pill" data-target="otros">Otros</button>
        </div>
        <?php
        // Usamos la ruta real para evitar fallos si el servidor tiene configuraciones de ruta estrictas
                  $rutaModelo = __DIR__ . "/../../modelo/Producto.php";
                  if (file_exists($rutaModelo)) {
                      require_once $rutaModelo;
                      $prodModel = new Producto();
                      if ($idRol == 1 && isset($_SESSION['id_usuario'])) {
                          $productosDB =
                              $prodModel->listarPorUsuario($_SESSION['id_usuario']);
                      } else {
                          $productosDB =
                              $prodModel->listarSencillo();
                      }
                  } else {
                      $productosDB = [];
                  }

        ?>
        <div class="toolbar">
          <div class="toolbar-left" id="resultados-count">
            Mostrando <?php echo count($productosDB); ?> productos disponibles
          </div>

          <select class="sort-select">
            <option>Más recientes</option>
            <option>Más vendidos</option>
            <option>Menor precio</option>
            <option>Mayor precio</option>
          </select>
        </div>

        <div class="products-grid" id="home-grid">
          <?php
          if ($productosDB):
            foreach ($productosDB as $p):
          ?>
          <?php
            $catMap = [
                1 => 'papeleria',
                2 => 'tecnologia',
                3 => 'comida',
                4 => 'ropa',
                5 => 'otros'
            ];
            $slug = $catMap[$p['id_categoria']] ?? 'otros';
          ?>

          <div class="product-card"
              style="position:relative;"
            onclick="verProducto(event, this)"

            data-cat="<?php echo $slug; ?>"

            data-id="<?php echo $p['id_producto'] ?? ''; ?>"

            data-id-vendedor="<?php echo $p['id_usuario'] ?? ''; ?>"

            data-ubicacion="<?php echo htmlspecialchars($p['ubicacion'] ?? 'Sin ubicación'); ?>"

            data-descripcion="<?php echo htmlspecialchars($p['descripcion'] ?? 'Sin descripción disponible'); ?>">

            <?php if($idRol != 1): ?>
              <div class="fav-btn <?php echo in_array($p['id_producto'], $userFavs) ? 'active' : ''; ?>"
                  onclick="event.stopPropagation();
                  toggleFavorito(<?php echo $p['id_producto']; ?>, this)">

                  <?php echo in_array($p['id_producto'], $userFavs) ? '❤️' : '🤍'; ?>

              </div>
            <?php endif; ?>

            <?php

                $imagenPath = !empty($p['imagen'])
                    ? 'uploads/productos/' . $p['imagen']
                    : 'uploads/productos/default.png';

            ?>

            <img class="card-img"
                src="<?php echo $imagenPath; ?>"
                alt="<?php echo htmlspecialchars($p['nombre']); ?>">

            <div class="card-body">

                <div class="card-title">

                    <?php echo htmlspecialchars($p['nombre']); ?>

                </div>

                <div class="card-price">

                    $<?php echo number_format($p['precio'], 0, ',', '.'); ?>

                </div>

                <div class="card-seller">

                    🌟 <?php echo htmlspecialchars($p['vendedor'] ?? 'Vendedor'); ?>

                </div>

                <?php if($idRol != 1): ?>

                    <button class="btn-aprovechar"
                        onclick="event.stopPropagation();
                        verProducto(event, this.closest('.product-card'))">

                        Aprovechar

                    </button>

                <?php endif; ?>

            </div>

        </div>



          <?php 
            endforeach;
          else:
          ?>
              <div style="
                  background: white;
                  border-radius: 20px;
                  padding: 50px;
                  text-align: center;
                  color: #6b7280;
                  width: 100%;
                  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
                  margin-top: 20px;
              ">

                  <div style="font-size: 60px; margin-bottom: 15px;">
                      📦
                  </div>

                  <h3 style="margin-bottom: 10px; color: var(--primary);">
                      Aún no hay productos publicados
                  </h3>

                  <p>
                      Sé el primero en publicar algo en UniMarket.
                  </p>

              </div>
          <?php 
          endif; 
          ?>
        </div>
      </div>
    <div class="home-sidebar">

      <div class="sidebar-title">¿Cómo funciona UniMarket?</div>

      <div class="how-step">
        <div class="step-icon yellow">🔍</div>

        <div class="step-text">
          <strong>Explora productos y ofertas flash</strong>
          <span>Encuentra artículos y promociones publicadas por estudiantes.</span>
        </div>
      </div>

      <div class="how-step">
        <div class="step-icon green">💬</div>

        <div class="step-text">
          <strong>Contacta directamente</strong>
          <span>Habla con el vendedor y coordina la entrega en el campus.</span>
        </div>
      </div>

      <div class="how-step">
        <div class="step-icon blue">⚡</div>

        <div class="step-text">
          <strong>Publica fácilmente</strong>
          <span>Vende productos o crea ventas flash rápidas para la comunidad universitaria.</span>
        </div>
      </div>

      <hr class="sidebar-divider">

      <div class="solo-hoy">
        <strong>⚡ Ventas flash activas</strong>
        <span>Las ofertas cambian constantemente. ¡Aprovecha antes de que terminen!</span>
      </div>

    </div>

    </div>
<?php include 'components/footer.php'; ?>
  
  </div>

</div>

<div id="view-flash" class="view <?php echo ($defaultView == 'flash') ? 'active' : ''; ?>">
  <div class="container">
    <div class="breadcrumb" style="padding-top: 20px;">
      <a href="#" onclick="showView('home')">Inicio</a> <span>›</span> <span>Ventas Flash</span>
    </div>

    <div class="flash-header flash-hero-banner" style="">
      <h2 style="font-family: var(--font-display); font-size: 1.8rem; margin-bottom: 5px;">⚡ ¡Aprovecha el momento!</h2>
      <p style="font-size: 0.9rem; opacity: 0.9;">Ofertas reales de estudiantes en el campus ahora mismo.</p>
      <?php if($isLoggedIn): ?>
      <button class="btn-publish-flash" onclick="openFlashModal()" style="margin-top: 15px; background: var(--text); color: white; border: none; padding: 10px 20px; border-radius: 50px; font-weight: 700; cursor: pointer;">
        + Publicar venta flash
      </button>
      <?php else: ?>
      <button class="btn-publish-flash" onclick="showView('auth')" style="margin-top: 15px; background: var(--text); color: white; border: none; padding: 10px 20px; border-radius: 50px; font-weight: 700; cursor: pointer;">
        Inicia sesión para publicar
      </button>
      <?php endif; ?>
    </div>

    <div id="flash-items-container" style="display: flex; flex-direction: column; gap: 20px; margin-bottom: 40px;">
      <?php
      require_once __DIR__ . "/../../modelo/VentaFlash.php";
      $vfModel = new VentaFlash();
      $ventasFlash = $vfModel->listarActivas();

      if (!empty($ventasFlash)):
        foreach ($ventasFlash as $vf):
          // Calcular minutos restantes para la vista inicial
          $segundosRestantes = strtotime($vf['hora_fin']) - time();
          $minutosRestantes = floor($segundosRestantes / 60);
      ?>
  
          <div class="flash-card-horizontal" 
          data-fin="<?php echo $vf['hora_fin']; ?>" 
          data-id-vendedor="<?php echo $vf['id_usuario']; ?>">

        <div class="flash-img-container">

          <div class="flash-timer">
            ⏱️ <span class="timer-display"><?php echo $minutosRestantes; ?>m</span>
          </div>

          <img src="<?php echo !empty($vf['imagen']) 
            ? 'uploads/productos/' . $vf['imagen'] 
            : 'uploads/productos/default.png'; ?>">

        </div>

        <div class="flash-info">

          <div style="display:flex; justify-content:space-between;">
            <h3 class="flash-title"><?php echo $vf['nombre']; ?></h3>

            <span class="flash-price">
              $<?php echo number_format($vf['precio_oferta'], 0, ',', '.'); ?>
            </span>
          </div>

          <p class="flash-location">📍 Disponible ahora</p>

          <?php
            $stockInicialFlash = !empty($vf['stock_inicial']) ? (int)$vf['stock_inicial'] : max((int)$vf['stock_flash'], 1);
            $stockActualFlash = max((int)$vf['stock_flash'], 0);
            $porcentajeRestanteFlash = $stockInicialFlash > 0 ? max(0, min(100, round(($stockActualFlash / $stockInicialFlash) * 100))) : 0;
          ?>
          <div class="flash-stock-bar" title="Stock restante: <?php echo $porcentajeRestanteFlash; ?>%">
            <div class="progress" style="width:<?php echo $porcentajeRestanteFlash; ?>%;"></div>
          </div>

          <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">

            <span style="font-size:0.8rem; color:var(--red); font-weight:bold;">
              ¡Solo quedan <?php echo $vf['stock_flash']; ?>!
            </span>

            <div style="display:flex; gap:10px; align-items:center;">

              <?php if (isset($_SESSION['id_usuario']) && $_SESSION['id_usuario'] == $vf['id_usuario']): ?>

                <a href="editar_venta_flash.php?id=<?php echo $vf['id_flash']; ?>">
                  <button class="btn-aprovechar">Editar</button>
                </a>

                <a href="../../controlador/FlashController.php?accion=eliminar&id=<?php echo $vf['id_flash']; ?>"
                  onclick="return confirm('¿Eliminar esta venta flash?')">

                  <button class="btn-eliminar">Eliminar</button>

                </a>

              <?php endif; ?>

              <?php if (!isset($_SESSION['id_usuario']) || $_SESSION['id_usuario'] != $vf['id_usuario']): ?>

              <button class="btn-contact-flash"
                      onclick="iniciarChatVendedor('<?php echo $vf['id_usuario']; ?>')">
                ¡Lo quiero!
              </button>

            <?php endif; ?>

            </div>

          </div>

        </div>

      </div>

        <?php 
          endforeach; 
        else:
        ?>

          <div style="text-align: center; padding: 40px; background: #f9fafb; border-radius: var(--radius); color: var(--gray-500); border: 2px dashed var(--gray-200);">
            <span style="font-size: 3rem;">😴</span>
            <h3 style="margin-top: 15px;">No hay ventas flash en este momento</h3>
            <p>¡Vuelve más tarde o publica una tú mismo!</p>
          </div>

        <?php endif; ?>

      </div>

    </div>

</div>

<div id="detail-view" class="view">

  <div class="container">

    <div class="breadcrumb">
      <a href="#" onclick="showView('home')">Inicio</a>
      <span>›</span>
      <span>Detalle</span>
      <span>›</span>
      <span id="breadcrumb-product-name">Producto</span>
    </div>

    <div class="product-detail-layout">

      <div class="product-gallery">
        <div class="gallery-main">
          <img id="main-gallery-img"
               src="uploads/productos/default.png"
               alt="Audífonos">
          <button class="fav-btn" id="detail-fav-btn" onclick="toggleFavorito(this.dataset.id, this)">🤍</button>

        </div>
        <div class="gallery-thumbs">
          <div class="thumb active" onclick="changeImg(this, this.querySelector('img').src)">
            <img src="uploads/productos/default.png" alt="">
          </div>
          <div class="thumb" onclick="changeImg(this, this.querySelector('img').src)">
            <img src="uploads/productos/default.png" alt="">
          </div>
        </div>
      </div>

      <div class="product-info-panel">
        <div class="product-info-title" id="detail-title">Producto</div>
        <div class="product-info-price" id="detail-price">$0</div>

        <div class="seller-card">
          <div class="seller-avatar">UM</div>
          <div>
            <div class="seller-name" id="detail-seller">Vendedor</div>
            <div class="stars">
              ★★★★<span style="color:var(--gray-200)">★</span>
              <span class="stars-text">(38 reseñas)</span>
            </div>
            <div class="seller-sub" id="detail-ver-tienda" style="cursor:pointer; color:var(--primary)" onclick="verTienda(this.dataset.id)">Ver tienda</div>
          </div>
        </div>

        <div class="product-desc" id="detail-descripcion">
          Descripción del producto
        </div>

        <div class="product-tags">
          <div class="product-tag">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
            Estado: Excelente
          </div>
          <div class="product-tag">
            <svg class="loc-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            <span id="detail-ubicacion">
              <?php 
              echo !empty($productoDetalle['ubicacion']) 
              ? $productoDetalle['ubicacion'] 
              : 'Universidad del Magdalena'; 
              ?>
            </span>
          </div>
        </div>

        <?php if ($isLoggedIn && isset($_SESSION['id_usuario'])) : ?>

          <div style="
              display:grid;
              grid-template-columns: repeat(3, 1fr);
              gap:12px;
              margin-top:22px;
          ">

              <?php if($idRol == 1): ?>

                  <div class="detail-actions">

                      <button class="seller-action-btn edit-btn"
                              onclick="event.stopPropagation(); editarProducto(document.getElementById('detail-view').dataset.id)">

                          ✏️
                          <span>Editar</span>

                      </button>

                      <button class="seller-action-btn delete-btn"
                              onclick="event.stopPropagation(); eliminarProducto(document.getElementById('detail-view').dataset.id)">

                          🗑
                          <span>Eliminar</span>

                      </button>

                  </div>

              <?php endif; ?>

          </div>

          <?php if($idRol != 1): ?>

            <div class="detail-actions-comprador">
                <button class="btn-comprar"
                        onclick="agregarAlCarrito(
                            document.getElementById('detail-view').dataset.id
                        )">

                    🛒 Agregar al carrito
                </button>
                <button class="btn-comprar"
                        id="detail-btn-contactar"
                        onclick="
                            event.stopPropagation();
                            iniciarChatVendedor(this.dataset.id)
                        ">
                    Contactar vendedor
                </button>
                <button class="btn-cancel"
                        onclick="openReportModal(document.getElementById('detail-view').dataset.id)">
                    🚩 Reportar
                </button>
            </div>
          <?php endif; ?>

      <?php endif; ?>


        <div class="trust-badges">
          <div class="trust-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            Compra segura
          </div>
          <div class="trust-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            Entrega en campus
          </div>
        </div>
      </div></div>
      <div class="more-products">

        <div class="section-title">
            Más opciones en UniMarket
        </div>

        <div class="mini-grid"
            id="mini-productos-grid">

        </div>

    </div>

  </div>
  <?php include 'components/footer.php'; ?>
</div>

<div id="view-store" class="view">

  <div class="container">

    <!-- BREADCRUMB -->
    <div class="breadcrumb">

      <a href="#"
         onclick="showView('home')">

         Inicio

      </a>

      <span>›</span>

      <span>Tiendas</span>

      <span>›</span>

      <span id="breadcrumb-store-name">

        Tienda

      </span>

    </div>

    <!-- BANNER -->
    <div class="store-hero"
         style="margin-top:12px">

      <img
        id="store-banner-img"
        src="uploads/tiendas/default-banner.jpg"
        alt="Banner tienda"
        style="
          width:100%;
          height:300px;
          object-fit:cover;
          border-radius:var(--radius);
          background:var(--gray-100);
        ">

    </div>

    <!-- INFO TIENDA -->
    <div class="store-info"
         style="
            margin-top:20px;
            display:flex;
            gap:24px;
            align-items:center;
            flex-wrap:wrap;
         ">

      <!-- LOGO -->
      <div class="store-logo"
           style="
              width:110px;
              height:110px;
              border-radius:24px;
              overflow:hidden;
              background:var(--gray-100);
              flex-shrink:0;
              box-shadow:var(--shadow);
           ">

        <img
          id="store-logo-img"
          src="uploads/tiendas/default-logo.png"
          alt="Logo tienda"
          style="
            width:100%;
            height:100%;
            object-fit:cover;
          ">

      </div>

      <!-- META -->
      <div class="store-meta"
           style="flex:1; min-width:250px;">

        <div class="store-name"
             id="store-name"
             style="
                font-size:2rem;
                font-family:var(--font-display);
                margin-bottom:8px;
             ">

          Tienda

        </div>

        <div class="store-cat"
             id="store-cat"
             style="
                color:var(--gray-500);
                margin-bottom:10px;
                font-weight:600;
             ">

          Tienda universitaria

        </div>

        <div class="store-desc"
             id="store-desc"
             style="
                color:var(--gray-600);
                line-height:1.6;
                margin-bottom:14px;
             ">

          Productos publicados por este vendedor.

        </div>

        <div class="store-status"
             style="
                display:flex;
                align-items:center;
                gap:10px;
                color:var(--gray-600);
             ">

          <div class="dot-green"></div>

          Disponible

        </div>

      </div>

      <!-- BOTÓN CONTACTAR -->
      <button class="btn-blue"
              id="btn-contact-store"
              style="
                margin-left:auto;
                min-width:180px;
              ">

        Contactar tienda

      </button>

    </div>

    <!-- LAYOUT -->
    <div class="store-layout"
         style="margin-top:30px;">

      <!-- SIDEBAR -->
      <div class="store-sidebar">

        <div class="filter-title">

          Filtros de categoría

        </div>

        <!-- TODO -->
        <div class="filter-item active-filter"
             data-target="all"
             style="
                background:var(--gray-100);
                color:var(--primary);
             ">

          <div class="fi-icon">▦</div>

          <span>Todo</span>

        </div>

        <!-- FILTROS DINÁMICOS -->
        <div id="store-filters"></div>

      </div>

      <!-- MAIN -->
      <div class="store-main">

        <!-- PILLS -->
        <div class="cat-pills"
             id="store-pills">

          <button class="cat-pill active"
                  data-target="all">

            Todo

          </button>

        </div>

        <!-- TITULO -->
        <div class="section-title"
             id="store-section-title">

          Productos de esta tienda

        </div>

        <!-- GRID PRODUCTOS -->
        <div class="products-grid"
             id="store-grid">

        </div>

      </div>

    </div>

  </div>

  <?php include 'components/footer.php'; ?>

</div>

<div id="view-publish" class="view <?php echo ($defaultView == 'publish') ? 'active' : ''; ?>">
  <div class="container">

    <div class="breadcrumb">
      <a href="#" onclick="showView('home')">Inicio</a>
      <span>›</span>
      <span>
      <?php echo $modoEditar
          ? 'Editar producto'
          : 'Registrar nuevo producto'; ?>

      </span>
    </div>

    <div class="form-header" style="padding-top:20px">
      <div class="form-main-title">
          <?php echo $modoEditar
              ? 'Editar producto'
              : 'Registrar nuevo producto'; ?>
      </div>
          <div class="form-subtitle">

            <?php echo $modoEditar
                ? 'Actualiza la información de tu producto.'
                : 'Publica un artículo en el mercado de la Universidad del Magdalena.'; ?>
        </div>
    </div>

    <div class="publish-layout">

      <div class="publish-form-col">
        <div class="form-card">

          <div class="tip-box">
            💡 <span><strong>Consejo: Buenas fotos</strong> y descripción detallada atraen más ventas en la U.</span>
          </div>

          <div class="steps-list">
            <div class="step-item active"><div class="step-num">1</div> Fotos</div>
            <div class="step-item"><div class="step-num">2</div> Detalles</div>
            <div class="step-item"><div class="step-num">3</div> Precio</div>
          </div>

          <form action="../../controlador/ProductoController.php"
            method="POST"
            enctype="multipart/form-data">
            <?php if($modoEditar): ?>

              <input type="hidden"
                    name="accion"
                    value="actualizar">

              <input type="hidden"
                    name="id_producto"
                    value="<?php echo $productoEditar['id_producto']; ?>">
          <?php else: ?>

              <input type="hidden"
                    name="accion"
                    value="registrar">

          <?php endif; ?>
            <div class="form-group">
              <label class="form-label">Foto del producto</label>
              <div class="upload-drop" onclick="document.getElementById('input-imagen').click()" style="cursor:pointer">
                <input type="file" name="imagen" id="input-imagen" style="display:none" accept="image/*" onchange="handleImagePreview(this)">
                <div id="preview-img-cont" style="margin-bottom:10px">

                  <?php if($modoEditar && !empty($productoEditar['imagen'])): ?>

                      <img src="uploads/productos/<?php echo $productoEditar['imagen']; ?>"
                          style="width:100%; max-height:220px; object-fit:cover; border-radius:12px;">

                  <?php else: ?>

                      <svg xmlns="http://www.w3.org/2000/svg"
                          width="32"
                          height="32"
                          fill="none"
                          stroke="currentColor"
                          stroke-width="1.5"
                          style="color:var(--primary)">

                          <rect x="3" y="3" width="18" height="18" rx="3"/>
                          <circle cx="8.5" cy="8.5" r="1.5"/>
                          <polyline points="21 15 16 10 5 21"/>

                      </svg>

                  <?php endif; ?>

                </div>

                <span id="file-name">Haz clic para subir imagen</span>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Título del producto</label>
              <input type="text"
                id="pub-title"
                name="nombre"
                class="form-input"
                placeholder="Ej. Libro de cálculo"
                value="<?php echo $modoEditar ? $productoEditar['nombre'] : ''; ?>"
                required>
            </div>

            <div class="form-group">
              <label class="form-label">Descripción</label>
              <textarea name="descripcion"
              class="form-input"
              placeholder="Describe tu producto, estado y por qué le interesa a otros estudiantes."
              required><?php echo $modoEditar ? $productoEditar['descripcion'] : ''; ?></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">Categoría</label>
              <select
                  id="publish-category"
                  name="id_categoria"
                  class="form-input"
                  required >
                <option value="1"
                  <?php echo ($modoEditar && $productoEditar['id_categoria'] == 1) ? 'selected' : ''; ?>>
                  Papelería
                </option>
                <option value="2"
                  <?php echo ($modoEditar && $productoEditar['id_categoria'] == 2) ? 'selected' : ''; ?>>
                  Tecnología
                </option>
                <option value="3"
                  <?php echo ($modoEditar && $productoEditar['id_categoria'] == 3) ? 'selected' : ''; ?>>
                  Comida
                </option>
                <option value="4"
                  <?php echo ($modoEditar && $productoEditar['id_categoria'] == 4) ? 'selected' : ''; ?>>
                  Ropa
                </option>
                <option value="5"
                  <?php echo ($modoEditar && $productoEditar['id_categoria'] == 5) ? 'selected' : ''; ?>>
                  Otros
                </option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Precio (COP)</label>
              <input type="number"
              id="pub-price"
              name="precio"
              class="form-input"
              placeholder="Ej. 25000"
              value="<?php echo $modoEditar ? $productoEditar['precio'] : ''; ?>"
              required>
            </div>


            <div class="form-group">
              <label class="form-label">Punto de entrega en campus</label>
              <select name="ubicacion" class="form-input" required>
                <option value="Cafetería Central">☕ Cafetería Central</option>
                <option value="Zona de emprendimientos">🚀 Zona de emprendimientos</option>
                <option value="Edificio Mar Caribe">🏢 Edificio Mar Caribe</option>
                <option value="Edificio Ciénaga Grande">🏛️ Edificio Ciénaga Grande</option>
                <option value="Edificio Sierra Sur">🌄 Edificio Sierra Sur</option>
                <option value="Bloque VIII">🏢 Edificio Bloque VIII</option>
                <option value="Zona de Hamacas">🌴 Zona de Hamacas</option>
                <option value="Biblioteca">📚 Biblioteca</option>
                <option value="Hemiciclo">🏛️ Hemiciclo Cultural</option>
                <option value="Canchas Deportivas">🎾 Canchas deportivas</option>
              </select>
            </div>


            <div class="form-actions">
              <button type="button" class="btn-cancel" onclick="showView('home')">Cancelar</button>
              <button type="submit" class="btn-siguiente">
                <?php echo $modoEditar ? 'Guardar Cambios →' : 'Publicar Producto →'; ?>
              </button>
            </div>
          </form>

        </div></div><div class="publish-preview-col">
        <div class="preview-card">
          <div class="section-subtitle" style="margin-bottom:12px">Vista previa</div>
          <div id="prev-img-display" style="background:var(--gray-100);border-radius:var(--radius-sm);height:160px;display:flex;align-items:center;justify-content:center;color:var(--gray-400);font-size:0.85rem;margin-bottom:12px;overflow:hidden">
            <?php if($modoEditar && !empty($productoEditar['imagen'])): ?>
                <img src="uploads/productos/<?php echo $productoEditar['imagen']; ?>"
                    style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
            <?php else: ?>
                Aquí aparecerá tu foto
            <?php endif; ?>
          </div>
          <div class="preview-title" id="prev-title">
            <?php
                echo $modoEditar
                    ? $productoEditar['nombre']
                    : 'Título del producto';
            ?>
        </div>
          <div class="preview-price" id="prev-price">
             <?php if($modoEditar): ?>
              $<?php echo number_format($productoEditar['precio']); ?>
            <?php else: ?>
              $0
             <?php endif; ?>
          </div>
          <div class="preview-seller">
            <div class="seller-avatar" style="width:34px;height:34px;font-size:0.85rem">
              <?php 
                if($isLoggedIn) {
                  $parts = explode(' ', $_SESSION['nombre']);
                  echo strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                } else {
                  echo 'TU';
                }
              ?>
            </div>
            <div>
              <div style="font-size:0.83rem;font-weight:600"><?php echo $nombreUsuario ?? 'Estudiante Unimagdalena'; ?></div>
            </div>
          </div>
          <button class="btn-comprar" style="margin-top:16px">Comprar ahora</button>
        </div>
      </div></div></div></div>
      
        <div id="view-search" class="view">
          <div class="container">
            <div class="breadcrumb" style="padding-top: 20px;">
              <a href="#" onclick="showView('home')">Inicio</a> <span>›</span> <span>Explorar</span>
            </div>

            <div class="search-page-header" style="margin-top: 20px;">
              <h1 style="font-family: var(--font-display); font-size: 1.8rem; margin-bottom: 15px;">¿Qué estás buscando?</h1>
              <div class="navbar-search" style="display: block; max-width: 100%; margin-bottom: 30px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="left: 16px;">
                  <circle cx="11" cy="11" r="8"/>
                  <path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" id="main-search-input" placeholder="Papeleria, comida, tecnología..." style="padding: 15px 15px 15px 50px; font-size: 1.1rem; border-radius: var(--radius);">
              </div>
            </div>

            <div class="explore-sections">
              <h3 class="section-subtitle">Explorar por categorías</h3>
              <div class="cat-pills" style="margin-top: 15px;">
                <button class="cat-pill active" data-target="all">Todo</button>
                <button class="cat-pill" data-target="comida">Comida / Snacks</button>
                <button class="cat-pill" data-target="tecnologia">Tecnología</button>
                <button class="cat-pill" data-target="papeleria">Papelería</button>
              </div>
            </div>

            <div class="search-results-container" style="margin-top: 20px;">
              <div class="toolbar-left" id="search-count" style="margin-bottom: 15px;">Escribe algo para empezar a buscar...</div>
              <div class="products-grid" id="search-grid">
                </div>
            </div>
          </div>
        </div>

<div id="view-messages" class="view">
  <div class="container">
    <div class="breadcrumb" style="padding-top: 20px;">
      <a href="#" onclick="showView('home')">Inicio</a> <span>›</span> <span>Mensajes</span>
    </div>
    <div style="background: white; border-radius: 8px; padding: 20px; margin-top: 20px; min-height: 400px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); display: flex; gap: 20px;">
      
      <div style="flex: 1; border-right: 1px solid #e5e7eb; padding-right: 20px;">
        <h2 style="font-family: 'Sora', sans-serif; color: #004a87; margin-bottom: 20px;">Conversaciones</h2>
        <select id="chat-user-select" onchange="fetchConversacion(this.value)" style="width: 100%; padding: 10px; border-radius: var(--radius-sm); border: 1px solid var(--gray-200); margin-bottom: 15px;">
           <option value="">Iniciar chat con...</option>
           <?php if($isLoggedIn): foreach($todosLosUsuarios as $u): if($u['id_usuario'] != $_SESSION['id_usuario']): ?>
               <option value="<?php echo $u['id_usuario']; ?>"><?php echo $u['nombre']; ?></option>
           <?php endif; endforeach; endif; ?>
        </select>
        <p style="font-size: 0.85rem; color: var(--gray-500);">Selecciona a un estudiante de UniMarket para iniciar o continuar un hilo de conversación.</p>
      </div>

      <div style="flex: 2; display: flex; flex-direction: column;">
        <div id="mensajes-lista" style="flex: 1; border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; height: 300px; overflow-y: auto; background: #fafafa; margin-bottom: 15px;">
          <p style="text-align: center; color: #9ca3af; margin-top: 50px;">Selecciona una conversación para empezar a hablar.</p>
        </div>
        <div style="display: flex; gap: 10px;">
           <input type="text" id="mensaje-input" placeholder="Escribe tu mensaje..." style="flex: 1; padding: 10px; border-radius: var(--radius-sm); border: 1px solid var(--gray-200);">
           <button onclick="enviarMensaje()" class="btn-primary" style="padding: 10px 20px;">Enviar</button>
        </div>
      </div>

    </div>
  </div>
</div>



<div id="view-profile" class="view">

  <?php if(isset($_SESSION['id_usuario'])): ?>

    <div class="container">

        <!-- BREADCRUMB -->
        <div class="breadcrumb" style="padding-top: 20px;">
            <a href="#" onclick="showView('home')">Inicio</a>
            <span>›</span>
            <span>Mi Perfil</span>
        </div>

        <!-- HEADER PERFIL -->
        <div style="
            background: var(--white);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
            margin-top: 20px;
        ">

            <div style="
                display:flex;
                align-items:center;
                gap:30px;
                flex-wrap:wrap;
            ">

                <!-- FOTO / LOGO -->
                <?php if($idRol == 1): ?>

                    <!-- LOGO TIENDA -->
                    <div style="
                        width:110px;
                        height:110px;
                        border-radius:24px;
                        overflow:hidden;
                        background:var(--gray-100);
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        box-shadow:var(--shadow);
                        flex-shrink:0;
                    ">

                        <?php if(!empty($usuario['logo'])): ?>

                            <img src="uploads/tiendas/<?php echo $usuario['logo']; ?>"
                                style="
                                    width:100%;
                                    height:100%;
                                    object-fit:cover;
                                ">

                        <?php else: ?>

                            <div style="
                                width:100%;
                                height:100%;
                                background:var(--primary);
                                color:white;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                font-size:2.2rem;
                                font-weight:700;
                            ">

                                <?php
                                    $p = explode(" ", $nombreUsuario);

                                    echo strtoupper(
                                        substr($p[0], 0, 1) .
                                        (isset($p[1]) ? substr($p[1], 0, 1) : '')
                                    );
                                ?>

                            </div>

                        <?php endif; ?>

                    </div>

                <?php else: ?>

                    <!-- FOTO PERFIL -->
                    <div style="
                        width:110px;
                        height:110px;
                        border-radius:50%;
                        overflow:hidden;
                        background:var(--primary);
                        color:white;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        font-size:2.5rem;
                        font-weight:700;
                        flex-shrink:0;
                    ">

                        <?php if(!empty($usuario['foto_perfil'])): ?>

                            <img src="uploads/perfiles/<?php echo $usuario['foto_perfil']; ?>"
                                style="
                                    width:100%;
                                    height:100%;
                                    object-fit:cover;
                                ">

                        <?php else: ?>

                            <?php
                                $p = explode(" ", $nombreUsuario);

                                echo strtoupper(
                                    substr($p[0], 0, 1) .
                                    (isset($p[1]) ? substr($p[1], 0, 1) : '')
                                );
                            ?>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

                <!-- INFO -->
                <div style="flex:1; min-width:250px;">

                    <h2 style="
                        font-family: var(--font-display);
                        font-size: 2rem;
                        margin-bottom: 10px;
                        line-height:1.2;
                    ">
                        <?php echo $nombreUsuario; ?>
                    </h2>

                    <p style="
                        color: var(--gray-600);
                        margin-bottom:12px;
                        line-height:1.6;
                        font-size:1rem;
                    ">

                        <?php if($idRol == 1): ?>

                            <?php echo !empty($usuario['descripcion_tienda'])
                                ? $usuario['descripcion_tienda']
                                : 'Tienda universitaria en UniMarket'; ?>

                        <?php else: ?>

                            Estudiante / Comprador • Unimagdalena

                        <?php endif; ?>

                    </p>

                    <div style="
                        display:flex;
                        gap:15px;
                        flex-wrap:wrap;
                        color:var(--gray-500);
                        font-size:0.95rem;
                    ">

                        <?php if($idRol == 1): ?>
                            <span>
                                ⭐ 5.0 (Nuevo)
                            </span>
                        <?php endif; ?>

                        <span>
                            📅 Miembro desde <?php echo date("M Y"); ?>
                        </span>

                    </div>

                </div>

                <!-- BOTON -->
                <div>

                    <button class="btn-cancel"
                            onclick="showView('edit-profile')">

                        Editar perfil

                    </button>

                </div>

            </div>

        </div>

        <?php

        // CONTADOR PRODUCTOS REALES
        $totalProductos = 0;

        foreach($misProductos as $prod){

            if($prod['descripcion'] != 'Oferta Flash'){
                $totalProductos++;
            }

        }

        ?>


        <!-- TABS PERFIL -->
        <div style="margin-top:30px;">

            <div class="more-tabs">

                <?php if($idRol == 1): ?>

                    <button class="more-tab <?php echo $profileTab === 'productos' ? 'active' : ''; ?>"
                            onclick="switchProfileTab(this, 'profile-pub')">

                        Productos (<?php echo $totalProductos; ?>)

                    </button>

                    <button class="more-tab <?php echo $profileTab === 'flash' ? 'active' : ''; ?>"
                            onclick="switchProfileTab(this, 'profile-flash')">

                        Ventas Flash (<?php echo count($misFlash); ?>)

                    </button>

                    <button class="more-tab <?php echo $profileTab === 'pedidos' ? 'active' : ''; ?>"
                            onclick="switchProfileTab(this, 'profile-pedidos')">

                        Pedidos (<?php echo count($pedidosVendedor); ?>)

                    </button>

                <?php else: ?>

                    <button class="more-tab <?php echo $profileTab === 'favoritos' ? 'active' : ''; ?>"
                            onclick="switchProfileTab(this, 'profile-fav')">

                        Favoritos ❤️

                    </button>

                    <button class="more-tab <?php echo $profileTab === 'flash' ? 'active' : ''; ?>"
                            onclick="switchProfileTab(this, 'profile-flash')">

                        Mis ventas flash (<?php echo count($misFlash); ?>)

                    </button>

                <?php endif; ?>

            </div>

            <!-- ================================= -->
            <!-- MIS PRODUCTOS -->
            <!-- ================================= -->

            <div id="profile-pub"
                class="profile-content-section"
                style="<?php echo ($idRol == 1 && $profileTab !== 'flash' && $profileTab !== 'pedidos') ? 'display:block;' : 'display:none;'; ?>">

                <?php if($idRol == 1): ?>

                    <div class="products-grid"
                        style="margin-top:20px;">

                        <?php if (!empty($misProductos)): ?>

                            <?php foreach ($misProductos as $mp): ?>

                                <?php
                                if($mp['descripcion'] == 'Oferta Flash'){
                                    continue;
                                }
                                ?>

                                <div class="product-card"
                                    style="position:relative;"
                                    onclick="verProducto(event, this)"
                                    data-id="<?php echo $mp['id_producto']; ?>"
                                    data-id-vendedor="<?php echo $mp['id_usuario']; ?>"
                                    data-ubicacion="<?php echo $mp['ubicacion']; ?>"
                                    data-descripcion="<?php echo htmlspecialchars($mp['descripcion']); ?>">

                                    <?php
                                    $imgMp = !empty($mp['imagen'])
                                        ? 'uploads/productos/' . $mp['imagen']
                                        : 'uploads/productos/default.png';
                                    ?>

                                    <img class="card-img"
                                        src="<?php echo $imgMp; ?>"
                                        alt="<?php echo $mp['nombre']; ?>">

                                    <div class="card-body">

                                        <div class="card-title">
                                            <?php echo $mp['nombre']; ?>
                                        </div>

                                        <div class="card-price">
                                            $<?php echo number_format($mp['precio'], 0, ',', '.'); ?>
                                        </div>

                                        <div style="display:flex; gap:10px; margin-top:15px;">

                                            <button class="btn-cancel"
                                                    style="flex:1; padding:8px;"
                                                    onclick="event.stopPropagation(); editarProducto(<?php echo $mp['id_producto']; ?>)">

                                                Editar

                                            </button>

                                            <button class="btn-aprovechar"
                                                style="flex:1; padding:8px; background:var(--red); color:white;"
                                                onclick="event.stopPropagation(); eliminarProducto(<?php echo $mp['id_producto']; ?>)">

                                                Eliminar

                                            </button>

                                        </div>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <p style="
                                padding:40px;
                                text-align:center;
                                color:var(--gray-500);
                                width:100%;
                            ">
                                Aún no tienes publicaciones activas.
                            </p>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>

            </div>

            <!-- ================================= -->
            <!-- VENTAS FLASH -->
            <!-- ================================= -->

            <div id="profile-flash"
                class="profile-content-section"
                style="<?php echo ($profileTab === 'flash') ? 'display:block;' : 'display:none;'; ?>">

                <?php if($isLoggedIn): ?>

                    <div class="profile-flash-toolbar">
                        <button class="btn-primary" onclick="openFlashModal()">
                            ⚡ Publicar nueva venta flash
                        </button>
                    </div>

                    <div class="products-grid profile-flash-grid">

                        <?php if (!empty($misFlash)): ?>

                            <?php foreach ($misFlash as $vf): ?>

                                <?php
                                    $stockActual = max(0, (int)($vf['stock_flash'] ?? 0));
                                    $stockInicial = (int)($vf['stock_inicial'] ?? $stockActual);
                                    if ($stockInicial <= 0) {
                                        $stockInicial = max(1, $stockActual);
                                    }
                                    if ($stockActual > $stockInicial) {
                                        $stockInicial = $stockActual;
                                    }
                                    $vendidos = max(0, $stockInicial - $stockActual);
                                    $porcentajeVendido = min(100, max(0, round(($vendidos / $stockInicial) * 100)));
                                    $flashActiva = (strtotime($vf['hora_inicio']) <= time() && strtotime($vf['hora_fin']) >= time() && $stockActual > 0);
                                    $imgFlash = !empty($vf['imagen'])
                                        ? 'uploads/productos/' . $vf['imagen']
                                        : 'uploads/productos/default.png';
                                ?>

                                <div class="product-card profile-flash-card"
                                    style="position:relative;"
                                    onclick="verProducto(event, this)"
                                    data-id="<?php echo $vf['id_producto']; ?>"
                                    data-id-vendedor="<?php echo $vf['id_usuario']; ?>"
                                    data-ubicacion="<?php echo htmlspecialchars($vf['ubicacion']); ?>"
                                    data-descripcion="<?php echo htmlspecialchars($vf['descripcion']); ?>">

                                    <div class="profile-flash-image-wrap">
                                        <span class="profile-flash-badge <?php echo $flashActiva ? 'active' : 'ended'; ?>">
                                            <?php echo $flashActiva ? 'Activa' : 'Finalizada'; ?>
                                        </span>
                                        <img class="card-img profile-flash-img" src="<?php echo $imgFlash; ?>" alt="<?php echo htmlspecialchars($vf['nombre']); ?>">
                                    </div>

                                    <div class="card-body profile-flash-body">

                                        <div class="card-title profile-flash-title">
                                            ⚡ <?php echo htmlspecialchars($vf['nombre']); ?>
                                        </div>

                                        <div class="card-price profile-flash-price">
                                            $<?php echo number_format($vf['precio_oferta'], 0, ',', '.'); ?>
                                        </div>

                                        <div class="profile-flash-meta-line">
                                            <span>🧺 Stock: <?php echo $stockActual; ?> de <?php echo $stockInicial; ?></span>
                                            <span>⏱️ Termina: <?php echo date('d/m/Y H:i', strtotime($vf['hora_fin'])); ?></span>
                                            <span class="um-flash-countdown" data-fin="<?php echo htmlspecialchars($vf['hora_fin']); ?>">⏳ Calculando tiempo...</span>
                                        </div>

                                        <div class="profile-flash-progress" title="<?php echo $porcentajeVendido; ?>% vendido">
                                            <div style="width:<?php echo $porcentajeVendido; ?>%;"></div>
                                        </div>
                                        <div class="profile-flash-progress-text">
                                            <?php echo $porcentajeVendido; ?>% vendido · quedan <?php echo $stockActual; ?>
                                        </div>

                                        <div class="profile-flash-actions-inline">
                                            <button class="btn-primary" onclick="event.stopPropagation(); reactivarFlash(<?php echo $vf['id_flash']; ?>)">
                                                Volver a publicar
                                            </button>
                                            <button class="btn-cancel" onclick="event.stopPropagation(); editarFlash(<?php echo $vf['id_flash']; ?>)">
                                                Editar
                                            </button>
                                            <button class="btn-aprovechar danger" onclick="event.stopPropagation(); eliminarFlash(<?php echo $vf['id_flash']; ?>)">
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <p class="empty-state-inline">
                                Aún no tienes ventas flash publicadas.
                            </p>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>


            </div>

            <!-- ================================= -->
            <!-- PEDIDOS RECIBIDOS VENDEDOR -->
            <!-- ================================= -->

            <div id="profile-pedidos"
                class="profile-content-section"
                style="<?php echo ($idRol == 1 && $profileTab === 'pedidos') ? 'display:block;' : 'display:none;'; ?>">

                <?php if($idRol == 1): ?>

                    <?php if(!empty($pedidosVendedor)): ?>

                        <div class="seller-orders-list">

                            <?php foreach($pedidosVendedor as $pedido): ?>

                                <?php
                                    $estadoActualPedido = $pedido['estado_actual'] ?? 'Pendiente';
                                    $productosPedido = array_filter(array_map('trim', explode(',', $pedido['productos_resumen'] ?? '')));
                                ?>

                                <div class="seller-order-card" data-order-id="<?php echo $pedido['id_venta']; ?>">
                                    <div class="seller-order-main">
                                        <div class="seller-order-title">
                                            Pedido #<?php echo $pedido['id_venta']; ?> · <?php echo htmlspecialchars($pedido['comprador']); ?>
                                        </div>

                                        <div class="seller-order-meta">
                                            Fecha: <?php echo htmlspecialchars($pedido['fecha']); ?> · Punto: <?php echo htmlspecialchars($pedido['punto_entrega'] ?? 'Pendiente'); ?>
                                        </div>

                                        <div class="seller-order-products">
                                            <strong>Productos del pedido:</strong>
                                            <ul>
                                                <?php foreach($productosPedido as $prodPedido): ?>
                                                    <li><?php echo htmlspecialchars($prodPedido); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>

                                        <div class="seller-order-meta">
                                            Método de pago: <?php echo htmlspecialchars($pedido['metodo_pago'] ?? 'Pago contra entrega'); ?> · Pago: <strong><?php echo htmlspecialchars($pedido['estado_pago'] ?? 'No aplica'); ?></strong>
                                        </div>
                                    </div>

                                    <div class="seller-order-total">
                                        $<?php echo number_format($pedido['total_vendedor'], 0, ',', '.'); ?>
                                        <div class="pedido-estado-badge">
                                            Estado: <?php echo htmlspecialchars($estadoActualPedido); ?>
                                        </div>
                                    </div>

                                    <form class="pedido-estado-form seller-order-actions" action="../../controlador/VentaController.php?accion=actualizar_estado" method="POST">
                                        <input type="hidden" name="id_venta" value="<?php echo $pedido['id_venta']; ?>">
                                        <select name="estado" class="form-input">
                                            <?php foreach(['Pendiente de pago','Pendiente','Aceptado','Preparando','En camino','Entregado','Cancelado'] as $estadoPedido): ?>
                                                <option value="<?php echo $estadoPedido; ?>" <?php echo ($estadoActualPedido == $estadoPedido) ? 'selected' : ''; ?>>
                                                    <?php echo $estadoPedido; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn-primary" type="submit">
                                            Guardar
                                        </button>
                                    </form>
                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <p style="padding:40px; text-align:center; color:var(--gray-500); width:100%;">
                            Aún no tienes pedidos recibidos.
                        </p>

                    <?php endif; ?>

                <?php endif; ?>

            </div>

            <!-- ================================= -->
            <!-- FAVORITOS -->
            <!-- ================================= -->

            <div id="profile-fav"
                class="profile-content-section"
                style="<?php echo ($idRol != 1 || $profileTab === 'favoritos') ? 'display:block;' : 'display:none;'; ?>">

                <?php if(!empty($favoritosUsuario)): ?>

                    <div class="products-grid"
                        id="profile-fav-grid"
                        style="margin-top:20px;">

                        <?php foreach($favoritosUsuario as $fav): ?>

                            <div class="product-card"
                                style="position:relative;"
                                onclick="verProducto(event, this)"

                                data-id="<?php echo $fav['id_producto']; ?>"
                                data-id-vendedor="<?php echo $fav['id_usuario']; ?>"
                                data-ubicacion="<?php echo $fav['ubicacion']; ?>"
                                data-descripcion="<?php echo htmlspecialchars($fav['descripcion']); ?>">

                                <?php
                                $imgFav = !empty($fav['imagen'])
                                    ? 'uploads/productos/' . $fav['imagen']
                                    : 'uploads/productos/default.png';
                                ?>

                                <img class="card-img"
                                    src="<?php echo $imgFav; ?>"
                                    alt="<?php echo $fav['nombre']; ?>">

                                <div class="card-body">

                                    <div class="card-title">
                                        <?php echo $fav['nombre']; ?>
                                    </div>

                                    <div class="card-price">
                                        $<?php echo number_format($fav['precio'], 0, ',', '.'); ?>
                                    </div>

                                    <div class="card-seller">
                                        🌟 <?php echo isset($fav['vendedor']) ? $fav['vendedor'] : 'Vendedor'; ?>
                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="products-grid" id="profile-fav-grid" style="margin-top:20px;">
                        <div style="padding:40px; text-align:center; color:var(--gray-500); width:100%;">
                            No tienes favoritos guardados.
                        </div>
                    </div>

                <?php endif; ?>

            </div>

            <!-- ================================= -->
            <!-- MIS COMPRAS -->
            <!-- ================================= -->

            <div id="profile-compras"
                class="profile-content-section"
                style="display:none;">

                <?php if(!empty($misCompras)): ?>

                    <div class="products-grid"
                        style="margin-top:20px;">

                        <?php foreach($misCompras as $compra): ?>

                            <div class="purchase-card">
                                <div class="card-body">

                                    <div class="card-title">
                                        <?php echo $compra['producto_nombre']; ?>
                                    </div>

                                    <div class="card-price">
                                        $<?php echo number_format($compra['total'], 0, ',', '.'); ?>
                                    </div>

                                    <div style="
                                        font-size:0.9rem;
                                        color:var(--gray-500);
                                        margin-top:10px;
                                    ">
                                        Fecha:
                                        <?php echo $compra['fecha']; ?>
                                    </div>

                                    <div style="font-size:0.9rem; color:var(--primary); font-weight:700; margin-top:8px;">
                                        Estado: <?php echo $compra['estado_actual'] ?? 'Pendiente'; ?>
                                    </div>

                                    <button class="btn-aprovechar"
                                        style="
                                            width:100%;
                                            margin-top:15px;
                                        "
                                       onclick="verDetalleCompra(<?php echo $compra['id_venta']; ?>)">

                                      Ver detalle

                                    </button>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <p style="
                        padding:40px;
                        text-align:center;
                        color:var(--gray-500);
                        width:100%;
                    ">
                        Aún no tienes compras registradas.
                    </p>

                <?php endif; ?>

            </div>

        </div>

    </div>

  <?php else: ?>

    <div style="padding:40px; text-align:center; color:var(--gray-500);">
        Inicia sesión para ver esta sección.
    </div>

  <?php endif; ?>

</div>

<div id="view-edit-profile" class="view">

  <div class="container">

    <div class="breadcrumb" style="padding-top: 20px;">

      <a href="#"
         onclick="showView('home')">

         Inicio

      </a>

      <span>›</span>

      <a href="#"
         onclick="showView('profile')">

         Perfil

      </a>

      <span>›</span>

      <span>Configuración</span>

    </div>

    <div class="form-header" style="padding-top:20px">

      <div class="form-main-title">
        Configuración del Perfil
      </div>

      <div class="form-subtitle">
        Administra tu cuenta, seguridad y tienda en UniMarket.
      </div>

    </div>

    <div style="
        max-width: 700px;
        margin: 30px auto;
        background: white;
        padding: 35px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    ">

      <form action="../../controlador/UsuarioController.php?accion=actualizar"
            method="POST"
            enctype="multipart/form-data">

        <!-- ========================= -->
        <!-- DATOS PERSONALES -->
        <!-- ========================= -->

        <div class="section-title"
             style="margin-bottom:25px">

          👤 Información personal

        </div>

        <div class="form-group" style="margin-bottom: 20px;">

          <label class="form-label">
            Nombre completo
          </label>

          <input type="text"
                 name="nombre"
                 class="form-input"
                 value="<?php echo $nombreUsuario; ?>"
                 required>

        </div>

        <div class="form-group" style="margin-bottom: 20px;">

          <label class="form-label">
            Correo electrónico
          </label>

          <input type="email"
                 class="form-input"
                 value="<?php echo $isLoggedIn ? ($_SESSION['correo'] ?? '') : ''; ?>"
                 disabled
                 style="background:#f9fafb;">

        </div>

        <div class="form-group" style="margin-bottom: 20px;">

          <label class="form-label">
            Teléfono / WhatsApp
          </label>

          <input type="text"
                 name="telefono"
                 class="form-input"
                 placeholder="3001234567"
                 value="<?php echo $usuario['telefono'] ?? ''; ?>">

        </div>

        <!-- FOTO PERFIL SOLO COMPRADOR -->

        <?php if($idRol != 1): ?>

        <div class="form-group" style="margin-bottom:35px;">

          <label class="form-label">
            Foto de perfil (opcional)
          </label>

          <input type="file"
                 name="foto_perfil"
                 class="form-input"
                 accept="image/*">

        </div>

        <?php endif; ?>

        <!-- ========================= -->
        <!-- SEGURIDAD -->
        <!-- ========================= -->

        <div class="section-title"
             style="margin-bottom:25px">

          🔒 Seguridad

        </div>

        <div class="form-group" style="margin-bottom: 20px;">

          <label class="form-label">
            Contraseña actual
          </label>

          <input type="password"
                 name="contrasena_actual"
                 class="form-input"
                 placeholder="Ingresa tu contraseña actual">

        </div>

        <div class="form-group" style="margin-bottom: 20px;">

          <label class="form-label">
            Nueva contraseña
          </label>

          <input type="password"
                 name="contrasena"
                 class="form-input"
                 placeholder="Mínimo 6 caracteres">

        </div>

        <div class="form-group" style="margin-bottom: 10px;">

          <label class="form-label">
            Confirmar nueva contraseña
          </label>

          <input type="password"
                 name="confirmar_contrasena"
                 class="form-input"
                 placeholder="Repite la nueva contraseña">

        </div>

        <div style="
            margin-bottom:35px;
            font-size:.92rem;
            color:var(--gray-500);
        ">

          Para cambiar tu contraseña debes confirmar
          tu contraseña actual por seguridad.

        </div>

        <!-- ========================= -->
        <!-- TIENDA SOLO VENDEDOR -->
        <!-- ========================= -->

        <?php if($idRol == 1): ?>

        <div class="section-title"
             style="margin-bottom:25px">

          🏪 Configuración de tienda

        </div>

        <div class="form-group" style="margin-bottom:20px;">

          <label class="form-label">
            Descripción de la tienda
          </label>

          <textarea name="descripcion_tienda"
                    class="form-input"
                    rows="4"
                    placeholder="Describe tu tienda o productos"><?php echo $usuario['descripcion_tienda'] ?? ''; ?></textarea>

        </div>

        <div class="form-group" style="margin-bottom:20px;">

          <label class="form-label">
            Logo de la tienda
          </label>

          <input type="file"
                 name="logo"
                 class="form-input"
                 accept="image/*">

        </div>

        <div class="form-group" style="margin-bottom:35px;">

          <label class="form-label">
            Banner de la tienda
          </label>

          <input type="file"
                 name="banner"
                 class="form-input"
                 accept="image/*">

        </div>

        <?php endif; ?>

        <!-- ========================= -->
        <!-- SEGURIDAD EXTRA -->
        <!-- ========================= -->

        <div style="
            background:#fff8e7;
            border:1px solid #ffe2a8;
            padding:18px;
            border-radius:14px;
            margin-bottom:35px;
        ">

          <div style="
              font-weight:700;
              margin-bottom:8px;
              color:#b7791f;
          ">

            🔐 Seguridad de la cuenta

          </div>

          <div style="
              color:#8a6d1d;
              line-height:1.5;
              font-size:.95rem;
          ">

            Mantén tu contraseña segura y evita compartir
            tu acceso con otras personas.

          </div>

        </div>

        <!-- ========================= -->
        <!-- BOTONES -->
        <!-- ========================= -->

        <div style="
            display:flex;
            gap:15px;
            margin-top:10px;
        ">

          <button type="button"
                  class="btn-cancel"
                  style="flex:1;"
                  onclick="showView('profile')">

            Cancelar

          </button>

          <button type="submit"
                  class="btn-primary"
                  style="
                    flex:1;
                    padding:12px;
                    background:var(--primary);
                    color:white;
                    border:none;
                    border-radius:var(--radius-sm);
                    font-weight:600;
                    cursor:pointer;
                  ">

            Guardar cambios

          </button>

        </div>

      </form>

    </div>

  </div>

</div>

<div id="view-auth" class="view <?php echo ($defaultView == 'auth') ? 'active' : ''; ?>">
    <div class="full-screen-auth <?php echo $authMode === 'signup' ? 'right-panel-active' : ''; ?>" id="auth-container">
        
        <div class="form-container sign-up-container">
            <form action="../../controlador/UsuarioController.php?accion=registrar" method="POST">
                <h1 class="auth-title">Crea tu Cuenta</h1>
                <p style="color: var(--gray-400); margin-bottom: 20px;">Usa tu email como registro</p>
                <input type="text" name="nombre" class="modern-input" placeholder="Nombre completo" required />
                <input type="email" name="correo" class="modern-input" placeholder="Correo institucional" required />
                <input type="password" name="contrasena" class="modern-input" placeholder="Contraseña" required />
                <select name="id_rol" class="modern-input">
                    <option value="2">Quiero comprar</option>
                    <option value="1">Quiero vender</option>
                </select>
                <button type="submit" class="btn-auth">REGISTRARSE</button>
                <a href="../../controlador/GoogleAuthController.php?accion=iniciar" class="google-auth-btn">🔵 Continuar con Google</a>
            </form>
        </div>

        <div class="form-container sign-in-container">
            <form action="../../controlador/UsuarioController.php?accion=login" method="POST">
                <h1 class="auth-title">Iniciar Sesión</h1>
                <p style="color: var(--gray-400); margin-bottom: 20px;">o usa tu email</p>
                <input type="email" name="correo" class="modern-input" placeholder="Email" required />
                <input type="password" name="contrasena" class="modern-input" placeholder="Password" required />
                <a href="#" onclick="showView('forgot')" style="color: var(--primary); margin: 15px 0; text-decoration: none;">¿Olvidaste tu contraseña?</a>
                <button type="submit" class="btn-auth" style="background-color: var(--primary);">INICIAR SESIÓN</button>
                <a href="../../controlador/GoogleAuthController.php?accion=iniciar" class="google-auth-btn">🔵 Continuar con Google</a>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <div class="auth-overlay-kicker">🔐 Acceso UniMarket</div>
                    <h1>¡Bienvenido otra vez!</h1>
                    <div class="auth-overlay-divider"></div>
                    <p>Inicia sesión para seguir comprando, vendiendo y administrando tus publicaciones dentro del campus.</p>
                    <div class="auth-overlay-features">
                        <span>🛡️ Seguridad</span>
                        <span>💬 Comunidad</span>
                        <span>📍 Entrega en campus</span>
                    </div>
                    <button type="button" class="ghost-btn" id="signInBtn">INICIAR SESIÓN</button>
                </div>
                <div class="overlay-panel overlay-right">
                  <div class="auth-overlay-kicker">🛒 Marketplace universitario</div>
                  <h1>¿Eres nuevo?</h1>
                  <div class="auth-overlay-divider"></div>
                  <p>Únete a UniMarket y empieza a comprar, vender y publicar ventas flash con toda la comunidad de la Unimagdalena.</p>
                  <div class="auth-overlay-features">
                      <span>🛡️ Seguridad</span>
                      <span>👥 Comunidad</span>
                      <span>⚡ Venta flash</span>
                  </div>
                  <button type="button" class="ghost-btn" id="signUpBtn">REGISTRARSE</button>
                </div>
            </div>
        </div>

    </div>
</div>


<div id="view-forgot" class="view <?php echo ($defaultView == 'forgot') ? 'active' : ''; ?>">
  <div class="container" style="max-width:520px; padding-top:40px;">
    <div style="background:white; padding:32px; border-radius:20px; box-shadow:var(--shadow);">
      <h2 style="font-family:var(--font-display); margin-bottom:10px;">Recuperar contraseña</h2>
      <p style="color:var(--gray-500); margin-bottom:22px;">Escribe tu correo y te enviaremos un enlace temporal de recuperación.</p>
      <form action="../../controlador/UsuarioController.php?accion=solicitar_recuperacion" method="POST">
        <div class="form-group">
          <label class="form-label">Correo electrónico</label>
          <input type="email" name="correo" class="form-input" required placeholder="correo@ejemplo.com">
        </div>
        <button type="submit" class="btn-primary" style="width:100%; padding:14px; border:none; border-radius:14px; cursor:pointer;">Enviar enlace de recuperación</button>
        <button type="button" class="btn-cancel" onclick="showView('auth')" style="width:100%; margin-top:10px; padding:14px; border-radius:14px;">Volver al login</button>
      </form>
    </div>
  </div>
</div>

<div id="view-reset" class="view <?php echo ($defaultView == 'reset') ? 'active' : ''; ?>">
  <div class="container" style="max-width:520px; padding-top:40px;">
    <div style="background:white; padding:32px; border-radius:20px; box-shadow:var(--shadow);">
      <h2 style="font-family:var(--font-display); margin-bottom:10px;">Nueva contraseña</h2>
      <p style="color:var(--gray-500); margin-bottom:22px;">Ingresa y confirma tu nueva contraseña.</p>
      <form action="../../controlador/UsuarioController.php?accion=restablecer_contrasena" method="POST">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
        <div class="form-group">
          <label class="form-label">Nueva contraseña</label>
          <input type="password" name="contrasena" class="form-input" required minlength="6">
        </div>
        <div class="form-group">
          <label class="form-label">Confirmar contraseña</label>
          <input type="password" name="confirmar_contrasena" class="form-input" required minlength="6">
        </div>
        <button type="submit" class="btn-primary" style="width:100%; padding:14px; border:none; border-radius:14px; cursor:pointer;">Guardar nueva contraseña</button>
      </form>
    </div>
  </div>
</div>

<div id="view-purchases" class="view">
  <div class="container">
    <div class="breadcrumb" style="padding-top: 20px;">
      <a href="#" onclick="showView('home')">Inicio</a> <span>›</span> <span>Mis Compras</span>
    </div>

    <div class="form-header" style="padding-top:20px">
      <div class="form-main-title">🛍️ Mi historial de compras</div>
      <div class="form-subtitle">Aquí puedes ver los artículos que has adquirido en la comunidad Unimagdalena.</div>
    </div>

    <div style="margin-top: 30px;">
      <?php
      require_once __DIR__ . "/../../modelo/Venta.php";
require_once __DIR__ . "/../../modelo/Notificacion.php";
      $ventaModel = new Venta();
      $misCompras = [];
      if ($isLoggedIn) {
          $misCompras = $ventaModel->listarPorComprador($_SESSION['id_usuario']);
      }
      ?>

      <?php if (!empty($misCompras)): ?>
        <div style="display: grid; gap: 15px;">
          <?php foreach ($misCompras as $compra): ?>
            <div style="background: white; padding: 20px; border-radius: var(--radius-sm); border: 1px solid var(--gray-100); display: flex; justify-content: space-between; align-items: center;">
              <div>
                <h3 style="margin-bottom: 5px;"><?php echo $compra['producto_nombre']; ?></h3>
                <p style="font-size: 0.85rem; color: var(--gray-500);">Comprado el: <?php echo date("d/m/Y", strtotime($compra['fecha'])); ?></p>
              </div>
              <div style="text-align: right;">
                <span style="font-weight: 700; color: var(--primary); font-size: 1.1rem;">$<?php echo number_format($compra['total'], 0, ',', '.'); ?></span>
                <div style="font-size: 0.75rem; color: var(--primary); font-weight: 700; margin-top: 5px;">Estado: <?php echo $compra['estado_actual'] ?? 'Pendiente'; ?></div>
                <div style="font-size: 0.72rem; color: var(--gray-600); font-weight: 700; margin-top: 3px;">Pago: <?php echo htmlspecialchars($compra['estado_pago'] ?? 'No aplica'); ?></div>
                <button class="btn-aprovechar" style="margin-top:10px; padding:8px 12px;" onclick="verDetalleCompra(<?php echo $compra['id_venta']; ?>)">Ver detalle</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: var(--radius); border: 2px dashed var(--gray-200);">
          <div style="font-size: 4rem; margin-bottom: 20px;">🛒</div>
          <h2 style="font-family: var(--font-display); color: var(--gray-600);">Aún no has comprado nada</h2>
          <p style="color: var(--gray-400); margin-top: 10px;">Cuando compres un producto aparecerá en esta sección.</p>
          <button class="btn-primary" onclick="showView('home')" style="margin-top: 25px;">Ir al mercado</button>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>


<div id="view-notifications" class="view <?php echo ($defaultView == 'notifications') ? 'active' : ''; ?>">
  <div class="container notifications-page-container">
    <div class="breadcrumb" style="padding-top:20px;">
      <a href="#" onclick="showView('home')">Inicio</a> <span>›</span> <span>Notificaciones</span>
    </div>

    <div class="unimag-section-heading">
      <span>Bandeja UniMarket</span>
      <h2>🔔 Notificaciones</h2>
      <p>Consulta pedidos, compras, ventas flash, mensajes y avisos del sistema.</p>
    </div>

    <div class="notifications-page-card">
      <div class="noti-filters page-filters">
        <button type="button" class="active" onclick="filtrarNotificaciones('Todas', this, true)">Todas</button>
        <button type="button" onclick="filtrarNotificaciones('Compras', this, true)">Compras</button>
        <button type="button" onclick="filtrarNotificaciones('Pedidos', this, true)">Pedidos</button>
        <button type="button" onclick="filtrarNotificaciones('Flash', this, true)">Flash</button>
        <button type="button" onclick="filtrarNotificaciones('Mensajes', this, true)">Mensajes</button>
        <button type="button" onclick="filtrarNotificaciones('Sistema', this, true)">Sistema</button>
      </div>

      <div class="notifications-page-actions">
        <button type="button" class="btn-primary" onclick="marcarTodasNotificaciones()">Marcar todas como leídas</button>
      </div>

      <div id="notificaciones-pagina-lista" class="notifications-page-list">
        <?php if (!empty($notificacionesUsuario)): ?>
          <?php foreach($notificacionesUsuario as $n): ?>
            <article class="noti-page-item <?php echo empty($n['leida']) ? 'unread' : ''; ?>" data-tipo="<?php echo htmlspecialchars($n['tipo'] ?? 'Sistema'); ?>" data-id="<?php echo (int)$n['id_notificacion']; ?>">
              <div class="noti-page-icon"><?php echo empty($n['leida']) ? '🔵' : '⚪'; ?></div>
              <div>
                <strong><?php echo htmlspecialchars($n['titulo'] ?? ($n['tipo'] ?? 'Notificación')); ?></strong>
                <p><?php echo htmlspecialchars($n['mensaje']); ?></p>
                <span><?php echo htmlspecialchars($n['tipo'] ?? 'Sistema'); ?> · <?php echo date('d/m/Y h:i a', strtotime($n['fecha'])); ?></span>
              </div>
              <div class="noti-page-actions">
                <?php if (!empty($n['url']) && str_starts_with($n['url'], 'pedido/')): ?>
                  <button type="button" onclick="handleNotificacionClick(<?php echo (int)$n['id_notificacion']; ?>, '<?php echo htmlspecialchars($n['url']); ?>')">Abrir</button>
                <?php endif; ?>
                <button type="button" onclick="marcarNotificacionLeida(<?php echo (int)$n['id_notificacion']; ?>)">Leída</button>
                <button type="button" onclick="eliminarNotificacion(<?php echo (int)$n['id_notificacion']; ?>)">Eliminar</button>
              </div>
            </article>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="noti-empty-state">No tienes notificaciones todavía.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div id="view-cart" class="view">

    <div class="container" style="max-width:1100px;">

        <div class="breadcrumb"
             style="padding-top:20px;">

            <a href="#"
               onclick="showView('home')">

               Inicio

            </a>

            <span>›</span>

            <span>Carrito</span>

        </div>

        <div class="section-title"
             style="
                margin-top:20px;
                margin-bottom:25px;
             ">

            Mi carrito

        </div>

        <?php if(!empty($productosCarrito)): ?>

            <?php $totalCarrito = 0; ?>

            <div
                style="
                    display:flex;
                    flex-direction:column;
                    gap:20px;
                "
            >

                <?php foreach($productosCarrito as $c):

                    $subtotal =
                        $c['precio'] *
                        $c['cantidad'];

                    $totalCarrito += $subtotal;

                    $imgCarrito =
                        !empty($c['imagen'])
                        ? 'uploads/productos/' . $c['imagen']
                        : 'uploads/productos/default.png';

                ?>

                <div
                  data-cart-id="<?php echo $c['id_carrito']; ?>"
                    style="
                        background:white;
                        border-radius:20px;
                        padding:20px;
                        display:flex;
                        gap:20px;
                        align-items:center;
                        box-shadow:var(--shadow);
                    "
                >

                    <img
                        src="<?php echo $imgCarrito; ?>"
                        style="
                            width:140px;
                            height:140px;
                            object-fit:cover;
                            border-radius:18px;
                            background:#f3f4f6;
                            flex-shrink:0;
                        "
                    >

                    <div style="flex:1;">

                        <div
                            style="
                                font-size:1.25rem;
                                font-weight:700;
                                margin-bottom:8px;
                            "
                        >
                            <?php echo $c['nombre']; ?>
                        </div>

                        <div
                            style="
                                color:var(--primary);
                                font-size:1.4rem;
                                font-weight:800;
                                margin-bottom:18px;
                            "
                        >
                            $<?php echo number_format($c['precio'], 0, ',', '.'); ?>
                        </div>

                        <div
                            style="
                                display:flex;
                                align-items:center;
                                gap:14px;
                                margin-bottom:18px;
                            "
                        >

                            <span style="color:var(--gray-500);">
                                Cantidad
                            </span>

                            <div
                                style="
                                    display:flex;
                                    align-items:center;
                                    background:#f3f4f6;
                                    border-radius:999px;
                                    padding:5px;
                                    gap:10px;
                                "
                            >

                                <button
                                    onclick="cambiarCantidad(
                                        <?php echo $c['id_carrito']; ?>,
                                        -1
                                    )"
                                    style="
                                        width:32px;
                                        height:32px;
                                        border:none;
                                        border-radius:50%;
                                        background:white;
                                        cursor:pointer;
                                        font-size:18px;
                                        font-weight:bold;
                                    "
                                >
                                    −
                                </button>

                               <span
                                  class="cart-cantidad"
                                  style="
                                      min-width:20px;
                                      text-align:center;
                                      font-weight:700;
                                  "
                              >
                                  <?php echo $c['cantidad']; ?>
                              </span>
                                <button
                                    onclick="cambiarCantidad(
                                        <?php echo $c['id_carrito']; ?>,
                                        1
                                    )"
                                    style="
                                        width:32px;
                                        height:32px;
                                        border:none;
                                        border-radius:50%;
                                        background:white;
                                        cursor:pointer;
                                        font-size:18px;
                                        font-weight:bold;
                                    "
                                >
                                    +
                                </button>

                            </div>

                        </div>

                        <div
                            class="cart-subtotal"
                            data-precio="<?php echo (float)$c['precio']; ?>"
                            style="
                                font-size:1rem;
                                font-weight:700;
                            "
                        >
                            Subtotal:
                            $<?php echo number_format($subtotal, 0, ',', '.'); ?>
                        </div>

                    </div>

                    <a
                        href="../../controlador/CarritoController.php?accion=eliminar&id=<?php echo $c['id_carrito']; ?>"
                    >

                        <button
                            class="btn-cancel"
                            style="
                                padding:12px 18px;
                                border-radius:12px;
                            "
                        >
                            Eliminar
                        </button>

                    </a>

                </div>

                <?php endforeach; ?>

            </div>

            <div
                style="
                    margin-top:30px;
                    background:white;
                    border-radius:20px;
                    padding:25px;
                    box-shadow:var(--shadow);
                "
            >

                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        margin-bottom:20px;
                    "
                >

                    <span
                        style="
                            font-size:1.3rem;
                            font-weight:700;
                        "
                    >
                        Total
                    </span>

                    <span
                        id="cart-total-value"
                        style="
                            font-size:2rem;
                            font-weight:800;
                            color:var(--primary);
                        "
                    >
                        $<?php echo number_format($totalCarrito, 0, ',', '.'); ?>
                    </span>

                </div>

                <button
                    class="btn-primary"
                    onclick="showView('checkout')"
                    style="
                        width:100%;
                        padding:18px;
                        border:none;
                        border-radius:16px;
                        font-size:1rem;
                        font-weight:700;
                        cursor:pointer;
                    "
                >
                    Finalizar compra
                </button>

            </div>

        <?php else: ?>

            <div
                style="
                    background:white;
                    border-radius:20px;
                    padding:60px 30px;
                    text-align:center;
                    box-shadow:var(--shadow);
                "
            >

                <div
                    style="
                        font-size:1.2rem;
                        color:var(--gray-500);
                    "
                >
                    Tu carrito está vacío
                </div>

            </div>

        <?php endif; ?>

    </div>

</div>





<div id="view-checkout" class="view <?php echo ($defaultView == 'checkout') ? 'active' : ''; ?>">
    <div class="container" style="max-width:1000px;">
        <div class="breadcrumb" style="padding-top:20px;">
            <a href="#" onclick="showView('cart')">Carrito</a>
            <span>›</span>
            <span>Checkout</span>
        </div>

        <div class="section-title" style="margin-top:20px; margin-bottom:25px;">
            Finalizar compra
        </div>

        <?php if(!empty($productosCarrito)): ?>
            <?php $totalCheckout = 0; ?>
            <form action="../../controlador/CarritoController.php?accion=finalizar" method="POST" style="display:grid; grid-template-columns:1.2fr .8fr; gap:24px; align-items:start;">
                <div style="background:white; border-radius:20px; padding:24px; box-shadow:var(--shadow);">
                    <h3 style="margin-bottom:18px;">📍 Punto de entrega</h3>
                    <div class="form-group">
                        <label class="form-label">Selecciona dónde quieres recibir tu compra</label>
                        <select name="id_punto_encuentro" class="form-input" required>
                            <?php foreach($puntosEntrega as $punto): ?>
                                <option value="<?php echo $punto['id_punto']; ?>">
                                    <?php echo htmlspecialchars($punto['nombre_lugar']); ?><?php echo !empty($punto['referencia']) ? ' — ' . htmlspecialchars($punto['referencia']) : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <h3 style="margin:26px 0 18px;">💳 Método de pago</h3>
                    <div class="payment-method-grid">
                        <label class="payment-method-card">
                            <input type="radio" name="metodo_pago" value="Pago contra entrega" checked>
                            <span>🤝</span>
                            <strong>Pago contra entrega</strong>
                            <small>Coordina con el vendedor y paga al recibir.</small>
                        </label>
                        <label class="payment-method-card featured">
                            <input type="radio" name="metodo_pago" value="Wompi sandbox">
                            <span>💳</span>
                            <strong>Wompi sandbox</strong>
                            <small>Checkout seguro listo para Render y pruebas.</small>
                        </label>
                    </div>
                </div>

                <div style="background:white; border-radius:20px; padding:24px; box-shadow:var(--shadow);">
                    <h3 style="margin-bottom:18px;">Resumen</h3>
                    <?php foreach($productosCarrito as $c): ?>
                        <?php $subtotalCheckout = $c['precio'] * $c['cantidad']; $totalCheckout += $subtotalCheckout; ?>
                        <div style="display:flex; justify-content:space-between; gap:12px; border-bottom:1px solid var(--gray-100); padding:12px 0;">
                            <div>
                                <div style="font-weight:700;"><?php echo htmlspecialchars($c['nombre']); ?></div>
                                <div style="color:var(--gray-500); font-size:.85rem;">Cantidad: <?php echo $c['cantidad']; ?></div>
                            </div>
                            <div style="font-weight:800; color:var(--primary);">
                                $<?php echo number_format($subtotalCheckout, 0, ',', '.'); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; font-size:1.2rem; font-weight:800;">
                        <span>Total</span>
                        <span style="color:var(--primary);">$<?php echo number_format($totalCheckout, 0, ',', '.'); ?></span>
                    </div>

                    <button type="submit" class="btn-primary" style="width:100%; margin-top:22px; padding:16px; border:none; border-radius:16px; font-weight:800; cursor:pointer;">
                        Confirmar pedido / continuar pago
                    </button>

                    <button type="button" class="btn-cancel" onclick="showView('cart')" style="width:100%; margin-top:10px; padding:14px; border-radius:16px;">
                        Volver al carrito
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div style="background:white; border-radius:20px; padding:50px; text-align:center; box-shadow:var(--shadow); color:var(--gray-500);">
                Tu carrito está vacío.
                <br><br>
                <button class="btn-primary" onclick="showView('home')" style="border:none; padding:12px 18px; border-radius:14px; cursor:pointer;">Ir al mercado</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const signUpButton = document.getElementById('signUpBtn');
    const signInButton = document.getElementById('signInBtn');
    const authContainer = document.getElementById('auth-container');

    if (signUpButton && signInButton && authContainer) {
        signUpButton.addEventListener('click', () => {
            authContainer.classList.add("right-panel-active");
        });

        signInButton.addEventListener('click', () => {
            authContainer.classList.remove("right-panel-active");
        });
    }
</script>


<script>
    window.initialProfileTab = <?php echo json_encode($_GET['tab'] ?? ''); ?>;
</script>

<script>
    // Sincronizamos la hora del servidor con el cliente
    window.serverTime = <?php echo time() * 1000; ?>;
    window.clientTime = Date.now();
    window.serverOffset = window.serverTime - window.clientTime;
</script>


<div id="flash-modal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(5px);">
  <div class="modal-content" style="background:white; padding:30px; border-radius:15px; width:90%; max-width:500px; position:relative;">
    <button onclick="closeFlashModal()" style="position:absolute; top:15px; right:15px; border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
    <div style="font-family:var(--font-display); font-size:1.5rem; margin-bottom:10px;">⚡ Publicar Oferta Flash</div>
    <p style="color:var(--gray-500); margin-bottom:20px; font-size:0.9rem;">Tu oferta estará activa hasta finalizar el día y aparecerá en las ventas flash del campus.</p>
    
    <form action="../../controlador/FlashController.php?accion=registrar" method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label class="form-label">Foto de la oferta</label>
        <div class="upload-drop" onclick="document.getElementById('flash-input-imagen').click()" style="cursor:pointer; padding: 15px; border: 2px dashed var(--gray-200); text-align:center;">
          <input type="file" name="imagen" id="flash-input-imagen" style="display:none" accept="image/*" onchange="handleFlashImagePreview(this)">
          <div id="flash-preview-cont" style="color:var(--gray-400); font-size:0.85rem;">
            📷 Haz clic para subir foto
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">¿Qué estás vendiendo? (Nombre rápido)</label>
        <input type="text" name="nombre" class="form-input" placeholder="Ej. 3 Donas frescas" required>
      </div>

      <div class="form-group">
        <label class="form-label">Categoría</label>
            <select
            id="publish-category"
            name="id_categoria"
            class="form-input"
            required
        >
          <option value="3">Comida / Snacks</option>
          <option value="1">Papelería</option>
          <option value="2">Tecnología</option>
          <option value="4">Ropa</option>
          <option value="5">Otros</option>
        </select>
      </div>
      
      <div style="display:flex; gap:15px;">
        <div class="form-group" style="flex:1;">
          <label class="form-label">Precio Oferta (COP)</label>
          <input type="number" name="precio_flash" class="form-input" placeholder="Ej. 5000" required>
        </div>
        <div class="form-group" style="flex:1;">
          <label class="form-label">Stock</label>
          <input type="number" name="stock_flash" class="form-input" placeholder="Ej. 10" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">¿Dónde estás ahora? (Punto de entrega)</label>
        <select name="ubicacion" class="form-input" required>
          <option value="Cafetería Central"
              <?php if($modoEditar && $productoEditar['ubicacion']=='Cafetería Central') echo 'selected'; ?>>
              ☕ Cafetería Central
          </option>
          <option value="Zona de emprendimientos"
              <?php if($modoEditar && $productoEditar['ubicacion']=='Zona de emprendimientos') echo 'selected'; ?>>
              🚀 Zona de emprendimientos
          </option>
          <option value="Edificio Mar Caribe"
              <?php if($modoEditar && $productoEditar['ubicacion']=='Edificio Mar Caribe') echo 'selected'; ?>>
              🏢 Edificio Mar Caribe
          </option>
          <option value="Edificio Ciénaga Grande"
              <?php if($modoEditar && $productoEditar['ubicacion']=='Edificio Ciénaga Grande') echo 'selected'; ?>>
              🏛️ Edificio Ciénaga Grande
          </option>
          <option value="Edificio Sierra Sur"
              <?php if($modoEditar && $productoEditar['ubicacion']=='Edificio Sierra Sur') echo 'selected'; ?>>
              🌄 Edificio Sierra Sur
          </option>
          <option value="Bloque VIII"
              <?php if($modoEditar && $productoEditar['ubicacion']=='Bloque VIII') echo 'selected'; ?>>
              🏢 Edificio Bloque VIII
          </option>
          <option value="Zona de Hamacas"
              <?php if($modoEditar && $productoEditar['ubicacion']=='Zona de Hamacas') echo 'selected'; ?>>
              🌴 Zona de Hamacas
          </option>
          <option value="Biblioteca"
              <?php if($modoEditar && $productoEditar['ubicacion']=='Biblioteca') echo 'selected'; ?>>
              📚 Biblioteca
          </option>
          <option value="Hemiciclo"
              <?php if($modoEditar && $productoEditar['ubicacion']=='Hemiciclo') echo 'selected'; ?>>
              🏛️ Hemiciclo Cultural
          </option>
          <option value="Canchas Deportivas"
              <?php if($modoEditar && $productoEditar['ubicacion']=='Canchas Deportivas') echo 'selected'; ?>>
              🎾 Canchas deportivas
          </option>
      </select>
      </div>

      <button type="submit" class="btn-primary" style="width:100%; margin-top:10px; padding:15px; background:linear-gradient(to right, #FFD700, #FFA500); color:var(--text); border:none; font-weight:bold;">
        🚀 ¡Lanzar Oferta Flash en el Campus!
      </button>
    </form>

  </div>
</div>



<div id="report-modal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.55); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(4px);">
  <div class="modal-content" style="background:white; padding:28px; border-radius:18px; width:90%; max-width:480px; position:relative; box-shadow:var(--shadow-lg);">
    <button onclick="closeReportModal()" style="position:absolute; top:12px; right:15px; border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
    <h2 style="font-family:var(--font-display); margin-bottom:8px;">🚩 Reportar producto</h2>
    <p style="color:var(--gray-500); margin-bottom:18px; font-size:.92rem;">Cuéntanos qué ocurre con este producto. El reporte quedará registrado para revisión.</p>
    <form action="../../controlador/ReporteController.php?accion=registrar" method="POST">
      <input type="hidden" name="id_producto" id="report-product-id">
      <div class="form-group">
        <label class="form-label">Motivo</label>
        <select name="motivo" class="form-input" required>
          <option value="Producto falso o engañoso">Producto falso o engañoso</option>
          <option value="Contenido inapropiado">Contenido inapropiado</option>
          <option value="Precio sospechoso">Precio sospechoso</option>
          <option value="Producto duplicado">Producto duplicado</option>
          <option value="Otro">Otro</option>
        </select>
      </div>
      <button type="submit" class="btn-primary" style="width:100%; padding:14px; border:none; border-radius:14px; margin-top:12px; cursor:pointer;">
        Enviar reporte
      </button>
    </form>
  </div>
</div>

<script src="scripts/main.js?v=fase4-pagos-unimag"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const botonRegistro = document.getElementById('signUpBtn');
        const botonLogin = document.getElementById('signInBtn');
        const contenedorPrincipal = document.getElementById('auth-container');

        if (botonRegistro && botonLogin && contenedorPrincipal) {
            botonRegistro.addEventListener('click', () => {
                contenedorPrincipal.classList.add("right-panel-active");
            });

            botonLogin.addEventListener('click', () => {
                contenedorPrincipal.classList.remove("right-panel-active");
            });
        }
    });
</script>


</body>
</html>