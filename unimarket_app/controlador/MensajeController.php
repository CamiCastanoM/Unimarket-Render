<?php
session_start();
require_once "../modelo/Mensaje.php";

$mensajeModel = new Mensaje();
$accion = isset($_GET['accion']) ? $_GET['accion'] : '';
$id_usuario_actual = $_SESSION['id_usuario'] ?? null;

if (!$id_usuario_actual) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit();
}

try {
    switch ($accion) {
        case 'enviar':
            $id_destinatario = $_POST['id_destinatario'] ?? null;
            $contenido = $_POST['contenido'] ?? null;
            if ($id_destinatario && $contenido && $mensajeModel->enviar($id_usuario_actual, $id_destinatario, $contenido)) {
                // Trigger notification
                require_once "../modelo/Notificacion.php";
                $notiModel = new Notificacion();
                // We pass the sender ID in the URL to easily open the chat
                $notiModel->crear($id_destinatario, "Tienes un nuevo mensaje de " . $_SESSION['nombre'], "chat/" . $id_usuario_actual);
                
                echo json_encode(['status' => 'success', 'message' => 'Mensaje enviado']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al enviar mensaje']);
            }
            break;
        case 'listar':
            $id_destinatario = $_GET['id_destinatario'] ?? null;
            if ($id_destinatario) {
                $mensajes = $mensajeModel->listarConversacion($id_usuario_actual, $id_destinatario);
                echo json_encode(['status' => 'success', 'data' => $mensajes]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Parámetros insuficientes']);
            }
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Excepción del servidor: ' . $e->getMessage()]);
}
?>
