<?php
require_once __DIR__ . "/../modelo/VentaFlash.php";

session_start();

$vfModel = new VentaFlash();
$accion = $_GET['accion'] ?? '';

function redirectFlash(string $url): void {
    header("Location: " . $url);
    exit();
}

function flashPerteneceAlUsuario(VentaFlash $vfModel, $idFlash, $idUsuario) {
    if (!$idFlash || !$idUsuario) {
        return false;
    }

    $flash = $vfModel->obtenerPorId($idFlash);
    if (!$flash) {
        return false;
    }

    return (int)$flash['id_usuario'] === (int)$idUsuario ? $flash : false;
}

if ($accion == 'registrar') {

    if (!isset($_SESSION['id_usuario'])) {
        $_SESSION['flash_message'] = 'Debes iniciar sesión para publicar una venta flash.';
        redirectFlash("../vista/MAQUETA-CAMILA/index.php?view=auth");
    }

    require_once __DIR__ . "/../modelo/Producto.php";
    $prodModel = new Producto();

    $nombre = $_POST['nombre'];
    $id_categoria = $_POST['id_categoria'];
    $precio_oferta = $_POST['precio_flash'];
    $stock = $_POST['stock_flash'];
    $ubicacion = $_POST['ubicacion'];

    $nombreImagen = null;

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $extension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($extension, $extensionesPermitidas)) {
            $nombreImagen = time() . "_" . bin2hex(random_bytes(5)) . "." . $extension;
            $basePath = dirname(__DIR__);
            $rutaDestino = $basePath . "/vista/MAQUETA-CAMILA/uploads/productos/" . $nombreImagen;
            $directorioDestino = dirname($rutaDestino);

            if (!is_dir($directorioDestino)) {
                mkdir($directorioDestino, 0777, true);
            }

            if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                $nombreImagen = null;
            }
        }
    }

    $id_producto = $prodModel->registrar(
        $nombre,
        $precio_oferta,
        $_SESSION['id_usuario'],
        $id_categoria,
        "Oferta Flash",
        $ubicacion,
        $nombreImagen
    );

    if ($id_producto) {
        if ($vfModel->registrar($id_producto, $precio_oferta, $stock)) {
            $_SESSION['flash_message'] = '¡Venta flash publicada exitosamente!';
        } else {
            $_SESSION['flash_message'] = 'Error al activar la oferta flash.';
        }
    } else {
        $_SESSION['flash_message'] = 'Error al registrar el producto de la oferta.';
    }

    redirectFlash("../vista/MAQUETA-CAMILA/index.php?view=profile&tab=flash");
}

if ($accion == 'editar') {

    if (!isset($_SESSION['id_usuario'])) {
        $_SESSION['flash_message'] = 'Debes iniciar sesión para editar tu venta flash.';
        redirectFlash("../vista/MAQUETA-CAMILA/index.php?view=auth");
    }

    $id_flash = $_POST['id_flash'] ?? null;
    $nombre = $_POST['nombre'] ?? '';
    $precio = $_POST['precio_flash'] ?? 0;
    $stock = $_POST['stock_flash'] ?? 0;
    $returnTab = $_POST['return_tab'] ?? 'flash';

    if (!flashPerteneceAlUsuario($vfModel, $id_flash, $_SESSION['id_usuario'])) {
        $_SESSION['flash_message'] = 'No tienes permiso para editar esa venta flash.';
        redirectFlash("../vista/MAQUETA-CAMILA/index.php?view=profile&tab=flash");
    }

    if ($vfModel->editar($id_flash, $nombre, $precio, $stock)) {
        $_SESSION['flash_message'] = "Venta flash actualizada";
    } else {
        $_SESSION['flash_message'] = "Error al actualizar";
    }

    redirectFlash("../vista/MAQUETA-CAMILA/index.php?view=profile&tab=" . urlencode($returnTab));
}

if ($accion == 'reactivar') {

    if (!isset($_SESSION['id_usuario'])) {
        $_SESSION['flash_message'] = "Debes iniciar sesión para reactivar una venta flash.";
        redirectFlash("../vista/MAQUETA-CAMILA/index.php?view=auth");
    }

    $id_flash = $_GET['id'] ?? null;

    if (!flashPerteneceAlUsuario($vfModel, $id_flash, $_SESSION['id_usuario'])) {
        $_SESSION['flash_message'] = "No tienes permiso para reactivar esa venta flash.";
        redirectFlash("../vista/MAQUETA-CAMILA/index.php?view=profile&tab=flash");
    }

    if ($id_flash && $vfModel->reactivar($id_flash)) {
        $_SESSION['flash_message'] = "Venta flash publicada nuevamente hasta las 11:59 p.m.";
    } else {
        $_SESSION['flash_message'] = "No se pudo volver a publicar la venta flash";
    }

    redirectFlash("../vista/MAQUETA-CAMILA/index.php?view=profile&tab=flash");
}

if ($accion == 'eliminar') {

    if (!isset($_SESSION['id_usuario'])) {
        $_SESSION['flash_message'] = 'Debes iniciar sesión para eliminar una venta flash.';
        redirectFlash("../vista/MAQUETA-CAMILA/index.php?view=auth");
    }

    $id_flash = $_GET['id'] ?? null;

    if (!flashPerteneceAlUsuario($vfModel, $id_flash, $_SESSION['id_usuario'])) {
        $_SESSION['flash_message'] = "No tienes permiso para eliminar esa venta flash.";
        redirectFlash("../vista/MAQUETA-CAMILA/index.php?view=profile&tab=flash");
    }

    if ($vfModel->eliminar($id_flash)) {
        $_SESSION['flash_message'] = "Venta flash eliminada";
    } else {
        $_SESSION['flash_message'] = "Error al eliminar";
    }

    redirectFlash("../vista/MAQUETA-CAMILA/index.php?view=profile&tab=flash");
}
?>
