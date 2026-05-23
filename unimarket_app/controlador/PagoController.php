<?php
session_start();
require_once __DIR__ . '/../modelo/Venta.php';
require_once __DIR__ . '/../config/app_config.php';

$accion = $_GET['accion'] ?? 'checkout';
$ventaModel = new Venta();
$appConfig = require __DIR__ . '/../config/app_config.php';
$wompi = require __DIR__ . '/../config/wompi_config.php';

function wompi_api_url($wompi) {
    return ($wompi['environment'] ?? 'sandbox') === 'production'
        ? $wompi['production_api_url']
        : $wompi['sandbox_api_url'];
}

function wompi_configurada($wompi) {
    return !empty($wompi['enabled'])
        && !empty($wompi['public_key'])
        && !empty($wompi['integrity_secret'])
        && strpos($wompi['public_key'], 'REEMPLAZA') === false
        && strpos($wompi['integrity_secret'], 'REEMPLAZA') === false;
}

function render_header($title = 'Pago UniMarket') {
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . htmlspecialchars($title) . '</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="../vista/MAQUETA-CAMILA/style/styles.css"></head><body class="payment-body">';
}

function render_footer() {
    echo '</body></html>';
}

function map_wompi_status($status) {
    $status = strtoupper((string)$status);
    if ($status === 'APPROVED') return 'Aprobado';
    if ($status === 'DECLINED') return 'Rechazado';
    if ($status === 'VOIDED') return 'Anulado';
    if ($status === 'ERROR') return 'Error';
    return 'Pendiente';
}

function consultar_transaccion_wompi($transactionId, $wompi, &$error = null) {
    if (!$transactionId || !function_exists('curl_init')) {
        $error = 'No hay ID de transacción o cURL no está activo.';
        return null;
    }

    $url = rtrim(wompi_api_url($wompi), '/') . '/transactions/' . urlencode($transactionId);
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    if (!empty($wompi['private_key'])) {
        $headers[] = 'Authorization: Bearer ' . $wompi['private_key'];
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $curlError) {
        $error = $curlError ?: 'Error consultando Wompi.';
        return null;
    }

    $json = json_decode($response, true);
    if ($statusCode >= 400 || !is_array($json)) {
        $error = 'Wompi respondió con error: ' . $response;
        return null;
    }
    return $json['data'] ?? $json;
}

if ($accion === 'checkout') {
    $idVenta = (int)($_GET['id_venta'] ?? 0);
    $venta = $ventaModel->obtenerVenta($idVenta);

    if (!$venta) {
        $_SESSION['flash_message'] = 'No se encontró el pedido para pagar.';
        header('Location: ../vista/MAQUETA-CAMILA/index.php?view=purchases');
        exit();
    }

    if (!isset($_SESSION['id_usuario']) || (int)$venta['id_comprador'] !== (int)$_SESSION['id_usuario']) {
        $_SESSION['flash_message'] = 'No tienes permiso para pagar este pedido.';
        header('Location: ../vista/MAQUETA-CAMILA/index.php?view=purchases');
        exit();
    }

    $referencia = $venta['referencia_pago'] ?? ('UM-' . $idVenta);
    $amountInCents = (int)round(((float)$venta['total']) * 100);
    $currency = $wompi['currency'] ?? 'COP';
    $signature = hash('sha256', $referencia . $amountInCents . $currency . ($wompi['integrity_secret'] ?? ''));
    $redirectUrl = rtrim($appConfig['base_url'], '/') . '/controlador/PagoController.php?accion=retorno&id_venta=' . $idVenta;
    $detalleUrl = rtrim($appConfig['base_url'], '/') . '/vista/MAQUETA-CAMILA/detalle_compra.php?id=' . $idVenta;

    render_header('Pago pedido #' . $idVenta);
    ?>
    <main class="payment-shell">
      <section class="payment-hero-card">
        <div class="payment-brand-row">
          <img src="../vista/MAQUETA-CAMILA/assets/img/unimagdalena-logo.png" alt="Universidad del Magdalena">
          <div>
            <strong>UniMarket Unimagdalena</strong>
            <span>Pago seguro para tu pedido universitario</span>
          </div>
        </div>
        <h1>Pedido #<?php echo (int)$idVenta; ?></h1>
        <p>Referencia: <strong><?php echo htmlspecialchars($referencia); ?></strong></p>
        <div class="payment-total">$<?php echo number_format((float)$venta['total'], 0, ',', '.'); ?></div>

        <?php if (wompi_configurada($wompi)): ?>
          <div class="payment-info-box success">Wompi está configurado. Puedes continuar en el checkout seguro.</div>
          <form class="wompi-form-box">
            <script
              src="<?php echo htmlspecialchars($wompi['checkout_widget_url']); ?>"
              data-render="button"
              data-public-key="<?php echo htmlspecialchars($wompi['public_key']); ?>"
              data-currency="<?php echo htmlspecialchars($currency); ?>"
              data-amount-in-cents="<?php echo $amountInCents; ?>"
              data-reference="<?php echo htmlspecialchars($referencia); ?>"
              data-signature:integrity="<?php echo htmlspecialchars($signature); ?>"
              data-redirect-url="<?php echo htmlspecialchars($redirectUrl); ?>"
              data-customer-data:email="<?php echo htmlspecialchars($_SESSION['correo'] ?? ''); ?>"
              data-customer-data:full-name="<?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Comprador UniMarket'); ?>">
            </script>
          </form>
        <?php else: ?>
          <div class="payment-info-box warning">
            Wompi sandbox aún no tiene llaves configuradas. El pedido quedará pendiente de pago hasta que configures las llaves y Wompi confirme la transacción.
          </div>
        <?php endif; ?>

        <div class="payment-actions-row">
          <a class="btn-cancel payment-link-btn" href="<?php echo htmlspecialchars($detalleUrl); ?>">Ver detalle del pedido</a>
          <a class="btn-cancel payment-link-btn" href="../vista/MAQUETA-CAMILA/index.php?view=purchases">Mis compras</a>
        </div>
      </section>
    </main>
    <?php
    render_footer();
    exit();
}

if ($accion === 'simular') {
    $_SESSION['flash_type'] = 'warning';
    $_SESSION['flash_message'] = 'La simulación de pago está desactivada. El pedido de Wompi debe esperar confirmación real.';
    header('Location: ../vista/MAQUETA-CAMILA/index.php?view=purchases');
    exit();
}

if ($accion === 'retorno') {
    $idVenta = (int)($_GET['id_venta'] ?? 0);
    $transactionId = $_GET['id'] ?? ($_GET['transaction_id'] ?? null);
    $error = null;

    if ($transactionId) {
        $transaction = consultar_transaccion_wompi($transactionId, $wompi, $error);
        if ($transaction && !empty($transaction['status'])) {
            $ventaModel->actualizarPago($idVenta, map_wompi_status($transaction['status']), $transactionId, $transaction);
            $_SESSION['flash_type'] = 'success';
            $_SESSION['flash_message'] = 'Wompi devolvió el estado del pago: ' . map_wompi_status($transaction['status']) . '.';
        } else {
            $_SESSION['flash_type'] = 'warning';
            $_SESSION['flash_message'] = 'Volviste de Wompi. El estado final se confirmará por webhook.' . ($error ? ' Detalle: ' . $error : '');
        }
    } else {
        $_SESSION['flash_type'] = 'info';
        $_SESSION['flash_message'] = 'Volviste de Wompi. El estado final se confirmará por webhook.';
    }

    header('Location: ../vista/MAQUETA-CAMILA/detalle_compra.php?id=' . $idVenta);
    exit();
}

if ($accion === 'webhook') {
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    $transaction = $payload['data']['transaction'] ?? $payload['data'] ?? [];
    $reference = $transaction['reference'] ?? null;
    $status = $transaction['status'] ?? null;
    $transactionId = $transaction['id'] ?? null;

    if ($reference && $status) {
        $venta = $ventaModel->obtenerPorReferenciaPago($reference);
        if ($venta) {
            $ventaModel->actualizarPago((int)$venta['id_venta'], map_wompi_status($status), $transactionId, $payload);
        }
    }

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit();
}

http_response_code(404);
echo 'Acción no válida';
