<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php?view=auth");
    exit();
}

require_once __DIR__ . "/../../modelo/VentaFlash.php";

$vfModel = new VentaFlash();

if (!isset($_GET['id'])) {
    echo "Venta no encontrada";
    exit();
}

$id_flash = $_GET['id'];
$venta = $vfModel->obtenerPorId($id_flash);

if (!$venta) {
    echo "Venta no existe";
    exit();
}

if ($venta['id_usuario'] != $_SESSION['id_usuario']) {
    echo "No tienes permiso";
    exit();
}

$img = !empty($venta['imagen']) ? 'uploads/productos/' . $venta['imagen'] : 'uploads/productos/default.png';
$stockInicial = !empty($venta['stock_inicial']) ? (int)$venta['stock_inicial'] : max(1, (int)$venta['stock_flash']);
$stockActual = max(0, (int)$venta['stock_flash']);
$vendidos = max(0, $stockInicial - $stockActual);
$porcentaje = $stockInicial > 0 ? min(100, round(($vendidos / $stockInicial) * 100)) : 0;
$returnTab = $_GET['returnTab'] ?? 'flash';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Venta Flash | UniMarket</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/styles.css">
</head>
<body class="um-standalone-body">
  <main class="um-form-shell">
    <header class="um-form-navbar">
      <a class="um-form-brand" href="index.php?view=profile&tab=<?php echo urlencode($returnTab); ?>">
        <img src="assets/img/unimagdalena-logo.png" alt="Universidad del Magdalena">
        <div>UniMarket<span>Universidad del Magdalena</span></div>
      </a>
      <a class="btn-cancel" href="index.php?view=profile&tab=<?php echo urlencode($returnTab); ?>">Volver al perfil</a>
    </header>

    <div class="breadcrumb" style="margin-bottom:18px;">
      <a href="index.php?view=home">Inicio</a><span>›</span>
      <a href="index.php?view=profile&tab=<?php echo urlencode($returnTab); ?>">Ventas Flash</a><span>›</span><span>Editar</span>
    </div>

    <section class="um-form-layout">
      <div class="um-form-card">
        <h1>⚡ Editar Venta Flash</h1>
        <p>Actualiza tu oferta, mantén el diseño atractivo y mejora tus oportunidades de venta dentro del campus.</p>

        <form action="../../controlador/FlashController.php?accion=editar" method="POST">
          <input type="hidden" name="id_flash" value="<?= htmlspecialchars($venta['id_flash']) ?>">
          <input type="hidden" name="return_tab" value="<?= htmlspecialchars($returnTab) ?>">

          <div class="um-form-grid">
            <div class="form-group">
              <label class="form-label">Nombre de la venta flash *</label>
              <input class="form-input" type="text" name="nombre" value="<?= htmlspecialchars($venta['nombre']) ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label">Precio oferta (COP) *</label>
              <input class="form-input" type="number" name="precio_flash" value="<?= htmlspecialchars($venta['precio_oferta']) ?>" required>
            </div>

            <div class="form-group">
              <label class="form-label">Stock disponible *</label>
              <input class="form-input" type="number" name="stock_flash" value="<?= htmlspecialchars($venta['stock_flash']) ?>" required min="0">
            </div>

            <div class="form-group">
              <label class="form-label">Estado de venta</label>
              <input class="form-input" type="text" value="<?= $stockActual > 0 ? 'Activa o disponible' : 'Sin stock' ?>" disabled>
            </div>
          </div>

          <div class="form-actions">
            <a class="btn-cancel" href="index.php?view=profile&tab=<?php echo urlencode($returnTab); ?>">Cancelar</a>
            <button type="submit" class="btn-primary">Guardar cambios</button>
          </div>
        </form>
      </div>

      <aside class="um-preview-card">
        <h3>Vista previa</h3>
        <div class="um-preview-box">
          <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($venta['nombre']) ?>">
          <div class="um-preview-content">
            <span class="profile-flash-badge active" style="position:static;display:inline-block;margin-bottom:10px;">Activa</span>
            <strong><?= htmlspecialchars($venta['nombre']) ?></strong>
            <div class="price">$<?= number_format($venta['precio_oferta'], 0, ',', '.') ?></div>
            <div class="profile-flash-progress" title="<?= $porcentaje ?>% vendido"><div style="width:<?= $porcentaje ?>%;"></div></div>
            <div class="profile-flash-progress-text"><?= $porcentaje ?>% vendido · quedan <?= $stockActual ?></div>
          </div>
        </div>
        <div class="um-tip">Consejo UniMarket: las ventas flash con fotos claras, nombre corto y stock actualizado se entienden más rápido.</div>
      </aside>
    </section>
  </main>
</body>
</html>
