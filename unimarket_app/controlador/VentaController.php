<?php
session_start();
require_once __DIR__ . '/../modelo/Venta.php';

$ventaModel = new Venta();
$accion = $_GET['accion'] ?? '';

function esAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function responder($success, $message, $extra = []) {
    if (esAjax()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message
        ], $extra));
        exit();
    }

    $_SESSION['flash_message'] = $message;
    header('Location: ../vista/MAQUETA-CAMILA/index.php?view=profile&tab=pedidos');
    exit();
}

if ($accion === 'actualizar_estado') {
    if (!isset($_SESSION['id_usuario']) || ($_SESSION['id_rol'] ?? null) != 1) {
        responder(false, 'No tienes permiso para actualizar pedidos.');
    }

    $id_venta = $_POST['id_venta'] ?? null;
    $estado = $_POST['estado'] ?? 'Pendiente';

    if ($id_venta && $ventaModel->actualizarEstado($id_venta, $estado)) {
        responder(true, 'Estado del pedido actualizado.', ['estado' => $estado, 'id_venta' => $id_venta]);
    }

    responder(false, 'No se pudo actualizar el estado del pedido.');
}

$_SESSION['flash_message'] = 'Acción de pedido no válida.';
header('Location: ../vista/MAQUETA-CAMILA/index.php');
exit();
?>
