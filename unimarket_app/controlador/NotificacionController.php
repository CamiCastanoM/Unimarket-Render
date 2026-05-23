<?php
session_start();
require_once "../modelo/Notificacion.php";

$notificacionModel = new Notificacion();
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$id_usuario_actual = $_SESSION['id_usuario'] ?? null;

header('Content-Type: application/json; charset=utf-8');

if (!$id_usuario_actual) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit();
}

switch ($accion) {
    case 'listar':
        $notificaciones = $notificacionModel->listarNoLeidas($id_usuario_actual);
        echo json_encode(['status' => 'success', 'data' => $notificaciones, 'unread' => count($notificaciones)]);
        break;

    case 'listar_todas':
        $tipo = $_GET['tipo'] ?? null;
        $notificaciones = $notificacionModel->listarTodas($id_usuario_actual, $tipo);
        echo json_encode(['status' => 'success', 'data' => $notificaciones, 'unread' => $notificacionModel->contarNoLeidas($id_usuario_actual)]);
        break;

    case 'marcar_leida':
        $data = json_decode(file_get_contents("php://input"), true);
        $id_notificacion = $data['id_notificacion'] ?? ($_POST['id_notificacion'] ?? null);
        if ($id_notificacion && $notificacionModel->marcarLeida($id_notificacion)) {
            echo json_encode(['status' => 'success', 'message' => 'Marcada como leída']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Parámetro faltante']);
        }
        break;

    case 'marcar_todas':
        $notificacionModel->marcarTodasLeidas($id_usuario_actual);
        echo json_encode(['status' => 'success', 'message' => 'Todas marcadas como leídas']);
        break;

    case 'eliminar':
        $data = json_decode(file_get_contents("php://input"), true);
        $id_notificacion = $data['id_notificacion'] ?? ($_POST['id_notificacion'] ?? null);
        if ($id_notificacion && $notificacionModel->eliminar($id_notificacion, $id_usuario_actual)) {
            echo json_encode(['status' => 'success', 'message' => 'Notificación eliminada']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
}
?>
