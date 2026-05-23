<?php

require_once __DIR__ . "/../modelo/Producto.php";

session_start();

$productoModel = new Producto();

$accion = $_REQUEST['accion'] ?? '';

switch($accion) {

    // ======================================
    // REGISTRAR
    // ======================================

    case 'registrar':

        if (!isset($_SESSION['id_usuario'])) {

            $_SESSION['flash_message'] =
                'Debes iniciar sesión para publicar.';

            header("Location: ../vista/MAQUETA-CAMILA/index.php");
            exit();

        }

        $nombre = $_POST['nombre'];
        $precio = $_POST['precio'];
        $id_usuario = $_SESSION['id_usuario'];

        $id_categoria = isset($_POST['id_categoria'])
            ? $_POST['id_categoria']
            : 1;

        $descripcion = $_POST['descripcion'];
        $ubicacion = $_POST['ubicacion'];

        // ======================================
        // MANEJO DE IMAGEN
        // ======================================

        $nombreImagen = null;
        $uploadError = "";

        if (
            isset($_FILES['imagen']) &&
            $_FILES['imagen']['error'] == 0
        ) {

            $extension = strtolower(
                pathinfo(
                    $_FILES['imagen']['name'],
                    PATHINFO_EXTENSION
                )
            );

            $extensionesPermitidas = [
                'jpg',
                'jpeg',
                'png',
                'webp'
            ];

            if (in_array($extension, $extensionesPermitidas)) {

                $nombreImagen =
                    time() .
                    "_" .
                    bin2hex(random_bytes(5)) .
                    "." .
                    $extension;

                $directorioDestino =
                    __DIR__ .
                    "/../vista/MAQUETA-CAMILA/uploads/productos";

                if (!is_dir($directorioDestino)) {

                    mkdir($directorioDestino, 0777, true);

                }

                $rutaDestino =
                    $directorioDestino .
                    "/" .
                    $nombreImagen;

                if (
                    !move_uploaded_file(
                        $_FILES['imagen']['tmp_name'],
                        $rutaDestino
                    )
                ) {

                    $nombreImagen = null;

                    $errorPhp = error_get_last();

                    $uploadError =
                        " - Error al mover archivo. " .
                        ($errorPhp
                            ? $errorPhp['message']
                            : "");

                }

            } else {

                $uploadError =
                    " - Extensión no permitida ($extension).";

            }

        }

        // ======================================
        // REGISTRAR PRODUCTO
        // ======================================

        if (
            $productoModel->registrar(
                $nombre,
                $precio,
                $id_usuario,
                $id_categoria,
                $descripcion,
                $ubicacion,
                $nombreImagen
            )
        ) {

            $_SESSION['flash_message'] =
                '¡Producto publicado con éxito!' .
                $uploadError;

        } else {

            $_SESSION['flash_message'] =
                'Error al publicar el producto';

        }

        header("Location: ../vista/MAQUETA-CAMILA/index.php");
        exit();


    // ======================================
    // LISTAR PRODUCTOS POR USUARIO
    // ======================================

    case 'listar_por_usuario':

        $id_usuario = $_GET['id_usuario'] ?? null;

        if($id_usuario) {

            $productos =
                $productoModel->listarPorUsuario($id_usuario);

            echo json_encode([
                'status' => 'success',
                'data' => $productos
            ]);

        } else {

            echo json_encode([
                'status' => 'error',
                'message' => 'Usuario no proporcionado'
            ]);

        }

        exit();


    // ======================================
    // EDITAR
    // ======================================

    case 'editar':

        $id = $_GET['id'] ?? null;

        if(!$id){

            die("Producto no encontrado");

        }

        $producto =
            $productoModel->obtenerPorId($id);

        if(!$producto){

            die("Producto no existe");

        }

        require_once
            "../vista/MAQUETA-CAMILA/editar_producto.php";

        break;


    // ======================================
    // ACTUALIZAR
    // ======================================

    case 'actualizar':

        $id = $_POST['id_producto'];

        $nombre = $_POST['nombre'];
        $precio = $_POST['precio'];
        $descripcion = $_POST['descripcion'];
        $ubicacion = $_POST['ubicacion'];
        $id_categoria = $_POST['id_categoria'];

        $productoModel->actualizar(
            $id,
            $nombre,
            $precio,
            $descripcion,
            $ubicacion,
            $id_categoria
        );

        $_SESSION['flash_message'] =
            'Producto actualizado correctamente';

        header("Location: ../vista/MAQUETA-CAMILA/index.php?view=home");
        exit();


    // ======================================
    // ELIMINAR
    // ======================================

    case 'eliminar':

        $id = $_GET['id'] ?? 0;

        if($id > 0){

            if($productoModel->eliminar($id)){

                $_SESSION['flash_message'] =
                    'Producto eliminado correctamente';

            } else {

                $_SESSION['flash_message'] =
                    'Error al eliminar producto';

            }

        } else {

            $_SESSION['flash_message'] =
                'Producto inválido';

        }

        header("Location: ../vista/MAQUETA-CAMILA/index.php");
        exit();


    // ======================================
    // DEFAULT
    // ======================================

    default:

        echo "Acción inválida";
        break;
}

?>