<?php
session_start();

require_once '../../config/conexion.php';
require_once '../../modelo/Venta.php';

if(!isset($_GET['id'])){
    die("Compra no encontrada");
}

$idVenta = $_GET['id'];
$ventaModel = new Venta();
$detalleCompra = $ventaModel->obtenerDetalleCompra($idVenta);

if(empty($detalleCompra)){
    die("No existe esta compra");
}

$estado = $detalleCompra[0]['estado_actual'] ?? 'Pendiente';
// Corrección visual/lógica: los pedidos de pago contra entrega no deben verse como pendientes de pago.
$metodoTmp = $detalleCompra[0]['metodo_pago'] ?? 'Pago contra entrega';
$pasarelaTmp = $detalleCompra[0]['pasarela'] ?? 'local';
if (stripos($metodoTmp, 'wompi') === false && $pasarelaTmp !== 'wompi' && $estado === 'Pendiente de pago') {
    $estado = 'Pendiente';
}
$total = $detalleCompra[0]['total'] ?? 0;
$fecha = $detalleCompra[0]['fecha'] ?? '';
$punto = $detalleCompra[0]['punto_entrega'] ?? 'Pendiente';
$referencia = $detalleCompra[0]['referencia'] ?? '';
$metodoPago = $detalleCompra[0]['metodo_pago'] ?? 'Pago contra entrega';
$estadoPago = $detalleCompra[0]['estado_pago'] ?? 'No aplica';
$pasarela = $detalleCompra[0]['pasarela'] ?? 'local';
if (stripos($metodoPago, 'wompi') === false && $pasarela !== 'wompi') {
    $estadoPago = 'No aplica';
}
$referenciaPago = $detalleCompra[0]['referencia_pago'] ?? null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Pedido</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/styles.css">
</head>
<body style="background:#f5f7fb;">

<nav class="navbar uni-navbar">
    <a href="index.php?view=home" class="navbar-logo uni-brand" style="text-decoration:none; color:inherit;">
        <img src="assets/img/unimagdalena-logo.png" alt="Universidad del Magdalena" class="brand-unimag-logo">
        <div><strong>UniMarket</strong><span>Universidad del Magdalena</span></div>
    </a>

    <div class="navbar-actions">
        <a href="index.php?view=purchases" class="btn-nav-link" style="text-decoration:none;">Mis compras</a>
        <a href="index.php?view=cart" class="btn-nav-link" style="text-decoration:none;">🛒 Carrito</a>
        <a href="index.php?view=profile" class="nav-icon-btn" style="text-decoration:none; display:flex; align-items:center; justify-content:center;">👤</a>
    </div>
</nav>

<div class="container" style="max-width:980px; padding-top:28px;">
    <div class="breadcrumb">
        <a href="index.php?view=home">Inicio</a>
        <span>›</span>
        <a href="index.php?view=purchases">Mis compras</a>
        <span>›</span>
        <span>Pedido #<?php echo htmlspecialchars($idVenta); ?></span>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; gap:15px; margin-top:18px; flex-wrap:wrap;">
        <div>
            <h1 style="font-family:var(--font-display); font-size:2rem; margin-bottom:8px;">🛒 Detalle del Pedido</h1>
            <p style="color:var(--gray-500);">Estado: <strong style="color:var(--primary);"><?php echo htmlspecialchars($estado); ?></strong> · Fecha: <?php echo htmlspecialchars($fecha); ?></p>
            <p style="color:var(--gray-500); margin-top:4px;">Punto de entrega: <?php echo htmlspecialchars($punto); ?><?php echo $referencia ? ' — ' . htmlspecialchars($referencia) : ''; ?></p>
            <p style="color:var(--gray-500); margin-top:4px;">Método de pago: <?php echo htmlspecialchars($metodoPago); ?> · Pago: <strong><?php echo htmlspecialchars($estadoPago); ?></strong></p>
            <?php if($referenciaPago): ?>
              <p style="color:var(--gray-500); margin-top:4px;">Referencia de pago: <strong><?php echo htmlspecialchars($referenciaPago); ?></strong></p>
            <?php endif; ?>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <?php if($pasarela === 'wompi' && $estadoPago !== 'Aprobado'): ?>
              <a href="../../controlador/PagoController.php?accion=checkout&id_venta=<?php echo (int)$idVenta; ?>" class="btn-primary" style="text-decoration:none; padding:12px 16px; border-radius:14px;">Pagar con Wompi</a>
            <?php endif; ?>
            <a href="index.php?view=purchases" class="btn-primary" style="text-decoration:none; padding:12px 16px; border-radius:14px;">← Volver a mis compras</a>
            <a href="index.php?view=home" class="btn-cancel" style="text-decoration:none; padding:12px 16px; border-radius:14px;">Ir al inicio</a>
        </div>
    </div>

    <div style="background:white; padding:30px; border-radius:20px; box-shadow:var(--shadow); margin-top:26px;">
        <?php foreach($detalleCompra as $item): ?>
            <?php
            $img = !empty($item['imagen'])
                ? 'uploads/productos/' . $item['imagen']
                : 'uploads/productos/default.png';
            ?>

            <div style="display:flex; gap:22px; margin-bottom:25px; padding-bottom:25px; border-bottom:1px solid var(--gray-100); align-items:center; flex-wrap:wrap;">
                <img src="<?php echo $img; ?>" style="width:130px; height:130px; object-fit:cover; border-radius:16px; background:var(--gray-100);">

                <div style="flex:1; min-width:240px;">
                    <h2 style="font-family:var(--font-display); font-size:1.35rem; margin-bottom:8px;">
                        <?php echo htmlspecialchars($item['nombre']); ?>
                    </h2>
                    <p>Cantidad: <strong><?php echo (int)$item['cantidad']; ?></strong></p>
                    <p>Subtotal: <strong>$<?php echo number_format($item['subtotal'], 0, ',', '.'); ?></strong></p>
                    <p>Vendedor: <?php echo htmlspecialchars($item['vendedor'] ?? 'Vendedor'); ?></p>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="margin-top:20px; text-align:right; font-size:1.3rem; font-weight:800; color:var(--primary);">
            Total: $<?php echo number_format($total, 0, ',', '.'); ?>
        </div>
    </div>
</div>

</body>
</html>
