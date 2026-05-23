<?php
require_once __DIR__ . "/../config/conexion.php";

class Mensaje {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
        $this->asegurarSchema();
    }

    private function asegurarSchema() {
        try {
            // Check if the id_remitente column exists
            $this->db->query("SELECT id_remitente FROM mensajes LIMIT 1");
        } catch (PDOException $e) {
            // Column does not exist, or table does not exist.
            // Let's drop and recreate to match our exact required structure
            try {
                $this->db->exec("DROP TABLE IF EXISTS mensajes");
                $sqlMensajes = "CREATE TABLE mensajes (
                    id_mensaje INT AUTO_INCREMENT PRIMARY KEY,
                    id_remitente INT NOT NULL,
                    id_destinatario INT NOT NULL,
                    contenido TEXT NOT NULL,
                    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $this->db->exec($sqlMensajes);
            } catch (PDOException $e2) {
                // Ignore if it fails to drop/create
            }
        }
    }

    // Enviar un mensaje de un usuario a otro
    public function enviar($id_remitente, $id_destinatario, $contenido) {
        $sql = "INSERT INTO mensajes (id_remitente, id_destinatario, contenido) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id_remitente, $id_destinatario, $contenido]);
    }

    // Obtener la conversación entre dos usuarios
    public function listarConversacion($id_usuario1, $id_usuario2) {
        $sql = "SELECT * FROM mensajes 
                WHERE (id_remitente = ? AND id_destinatario = ?) 
                   OR (id_remitente = ? AND id_destinatario = ?)
                ORDER BY fecha_envio ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_usuario1, $id_usuario2, $id_usuario2, $id_usuario1]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
