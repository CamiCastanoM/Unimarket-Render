<?php
require_once "../modelo/Usuario.php";
require_once "../modelo/Producto.php";

session_start();

$accion = isset($_GET['accion']) ? $_GET['accion'] : '';

// Control de acceso para administradores (id_rol = 3)
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 3) {
    header("HTTP/1.1 403 Forbidden");
    echo "Acceso denegado. Solo administradores.";
    exit();
}

$usuarioModel = new Usuario();
$productoModel = new Producto();

switch ($accion) {
    case 'listar_usuarios':
        $usuarios = $usuarioModel->listarTodos();
        echo json_encode(['status' => 'success', 'data' => $usuarios]);
        break;

    case 'eliminar_usuario':
        $id = $_POST['id_usuario'] ?? null;
        if ($id && $usuarioModel->eliminar($id)) {
            $_SESSION['flash_message'] = 'Usuario eliminado';
        } else {
            $_SESSION['flash_message'] = 'Error al eliminar usuario';
        }
        header("Location: AdminController.php?accion=ver_dashboard");
        break;

    case 'listar_productos':
        $productos = $productoModel->listarSencillo();
        echo json_encode(['status' => 'success', 'data' => $productos]);
        break;

    case 'eliminar_producto':
        $id = $_POST['id_producto'] ?? null;
        if ($id && $productoModel->eliminar($id)) {
            $_SESSION['flash_message'] = 'Producto eliminado por administrador.';
        } else {
            $_SESSION['flash_message'] = 'Error al eliminar producto.';
        }
        header("Location: AdminController.php?accion=ver_dashboard");
        break;

    case 'ver_dashboard':
        $usuarios = $usuarioModel->listarTodos();
        $productos = $productoModel->listarSencillo();
        include "../vista/MAQUETA-CAMILA/admin_dashboard.php";
        break;
    
    default:
        header("Location: AdminController.php?accion=ver_dashboard");
        break;
}
?>
