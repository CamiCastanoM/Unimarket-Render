<?php 

session_start();

require_once "../modelo/Carrito.php";
require_once "../modelo/Venta.php";

$carritoModel = new Carrito();

$accion = $_GET['accion'] ?? '';

/*
=====================================
AGREGAR AL CARRITO
=====================================
*/

if($accion == 'agregar') {

    $id_usuario =
        $_SESSION['id_usuario'] ?? null;

    if(!$id_usuario){

        echo json_encode([
            'success' => false,
            'message' => 'Debes iniciar sesión'
        ]);

        exit();
    }

    $id_producto =
        $_POST['id_producto'] ?? null;

    if(!$id_producto){

        echo json_encode([
            'success' => false,
            'message' => 'Producto inválido'
        ]);

        exit();
    }

    if(
        $carritoModel->agregar(
            $id_usuario,
            $id_producto
        )
    ){

        echo json_encode([
            'success' => true,
            'message' => 'Producto agregado al carrito'
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'message' => 'Error al agregar producto'
        ]);
    }

    exit();
}

/*
=====================================
ACTUALIZAR CANTIDAD
=====================================
*/

if($accion == 'actualizarCantidad') {

    $id_carrito =
        $_POST['id_carrito'] ?? null;

    $cantidad =
        $_POST['cantidad'] ?? 1;

    if($id_carrito && $cantidad >= 1){

        $carritoModel->actualizarCantidad(
            $id_carrito,
            $cantidad
        );

        echo json_encode([
            'success' => true
        ]);

    } else {

        echo json_encode([
            'success' => false
        ]);
    }

    exit();
}


/*
=====================================
FINALIZAR COMPRA / CREAR PEDIDO
=====================================
*/

if($accion == 'finalizar') {

    $id_usuario = $_SESSION['id_usuario'] ?? null;

    if(!$id_usuario){
        $_SESSION['flash_message'] = 'Debes iniciar sesión para finalizar la compra.';
        header("Location: ../vista/MAQUETA-CAMILA/index.php?view=auth");
        exit();
    }

    $id_punto = $_POST['id_punto_encuentro'] ?? null;
    $metodo_pago = $_POST['metodo_pago'] ?? 'Pago contra entrega';

    if(!$id_punto){
        $_SESSION['flash_message'] = 'Selecciona un punto de entrega.';
        header("Location: ../vista/MAQUETA-CAMILA/index.php?view=checkout");
        exit();
    }

    $ventaModel = new Venta();
    $resultado = $ventaModel->crearDesdeCarrito($id_usuario, $id_punto, $metodo_pago);

    if($resultado['success']){
        if (($resultado['pasarela'] ?? '') === 'wompi') {
            $_SESSION['flash_message'] = 'Pedido creado. Continúa con el pago seguro en Wompi.';
            header("Location: PagoController.php?accion=checkout&id_venta=" . urlencode($resultado['id_venta']));
        } else {
            $_SESSION['flash_message'] = 'Pedido creado correctamente. Estado: Pendiente.';
            header("Location: ../vista/MAQUETA-CAMILA/index.php?view=purchases");
        }
    } else {
        $_SESSION['flash_message'] = $resultado['message'] ?? 'No se pudo crear el pedido.';
        header("Location: ../vista/MAQUETA-CAMILA/index.php?view=cart");
    }

    exit();
}

/*
=====================================
ELIMINAR
=====================================
*/

if($accion == 'eliminar') {

    $id_carrito =
        $_GET['id'] ?? null;

    if($id_carrito){

        $carritoModel->eliminar(
            $id_carrito
        );
    }

    header(
        "Location: ../vista/MAQUETA-CAMILA/index.php?view=cart"
    );

    exit();
}

?>