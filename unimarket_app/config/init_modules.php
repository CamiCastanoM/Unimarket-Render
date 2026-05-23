<?php
require_once __DIR__ . '/conexion.php';

try {
    $db = Conexion::conectar();

    // 1. Crear tabla de mensajes
    $sqlMensajes = "CREATE TABLE IF NOT EXISTS mensajes (
        id_mensaje INT AUTO_INCREMENT PRIMARY KEY,
        id_remitente INT NOT NULL,
        id_destinatario INT NOT NULL,
        contenido TEXT NOT NULL,
        fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sqlMensajes);

    // 2. Crear tabla de notificaciones
    $sqlNotificaciones = "CREATE TABLE IF NOT EXISTS notificaciones (
        id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        mensaje VARCHAR(255) NOT NULL,
        url VARCHAR(255) DEFAULT NULL,
        leida BOOLEAN DEFAULT FALSE,
        fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sqlNotificaciones);

    echo "Tablas creadas correctamente.<br>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
