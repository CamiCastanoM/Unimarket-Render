<?php
session_start();
require_once __DIR__ . '/../modelo/Reporte.php';

$accion = $_GET['accion'] ?? '';
$reporteModel = new Reporte();

if ($accion === 'registrar') {
    if (!isset($_SESSION['id_usuario'])) {
        $_SESSION['flash_message'] = 'Debes iniciar sesión para reportar un producto.';
        header('Location: ../vista/MAQUETA-CAMILA/index.php');
        exit();
    }

    $id_producto = $_POST['id_producto'] ?? null;
    $motivo = trim($_POST['motivo'] ?? '');

    if (!$id_producto || $motivo === '') {
        $_SESSION['flash_message'] = 'Debes seleccionar un motivo para el reporte.';
        header('Location: ../vista/MAQUETA-CAMILA/index.php?view=home');
        exit();
    }

    if ($reporteModel->registrar($_SESSION['id_usuario'], $id_producto, $motivo)) {
        $_SESSION['flash_message'] = 'Reporte enviado correctamente.';
    } else {
        $_SESSION['flash_message'] = 'No se pudo guardar el reporte.';
    }

    header('Location: ../vista/MAQUETA-CAMILA/index.php?view=home');
    exit();
}

$_SESSION['flash_message'] = 'Acción de reporte no válida.';
header('Location: ../vista/MAQUETA-CAMILA/index.php');
exit();
?>
