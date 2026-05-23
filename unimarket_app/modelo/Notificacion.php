<?php
require_once __DIR__ . "/../config/conexion.php";

class Notificacion {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    private function columnaExiste($tabla, $columna) {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `$tabla` LIKE ?");
            $stmt->execute([$columna]);
            return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function crear($id_usuario, $mensaje, $url = null, $tipo = 'Sistema', $titulo = null) {
        if ($this->columnaExiste('notificaciones', 'tipo') && $this->columnaExiste('notificaciones', 'titulo')) {
            $sql = "INSERT INTO notificaciones (id_usuario, titulo, mensaje, url, tipo, leida, fecha) VALUES (?, ?, ?, ?, ?, 0, NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_usuario, $titulo, $mensaje, $url, $tipo]);
        }

        $sql = "INSERT INTO notificaciones (id_usuario, mensaje, url) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id_usuario, $mensaje, $url]);
    }

    public function listarNoLeidas($id_usuario) {
        $sql = "SELECT * FROM notificaciones WHERE id_usuario = ? AND leida = 0 ORDER BY fecha DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function contarNoLeidas($id_usuario) {
        $sql = "SELECT COUNT(*) AS total FROM notificaciones WHERE id_usuario = ? AND leida = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_usuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function listarTodas($id_usuario, $tipo = null) {
        $tieneTipo = $this->columnaExiste('notificaciones', 'tipo');
        if ($tipo && $tieneTipo && $tipo !== 'Todas') {
            $sql = "SELECT * FROM notificaciones WHERE id_usuario = ? AND tipo = ? ORDER BY fecha DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_usuario, $tipo]);
        } else {
            $sql = "SELECT * FROM notificaciones WHERE id_usuario = ? ORDER BY fecha DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_usuario]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marcarLeida($id_notificacion) {
        if ($this->columnaExiste('notificaciones', 'fecha_leida')) {
            $sql = "UPDATE notificaciones SET leida = 1, fecha_leida = NOW() WHERE id_notificacion = ?";
        } else {
            $sql = "UPDATE notificaciones SET leida = 1 WHERE id_notificacion = ?";
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id_notificacion]);
    }

    public function marcarTodasLeidas($id_usuario) {
        if ($this->columnaExiste('notificaciones', 'fecha_leida')) {
            $sql = "UPDATE notificaciones SET leida = 1, fecha_leida = NOW() WHERE id_usuario = ?";
        } else {
            $sql = "UPDATE notificaciones SET leida = 1 WHERE id_usuario = ?";
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id_usuario]);
    }

    public function eliminar($id_notificacion, $id_usuario) {
        $sql = "DELETE FROM notificaciones WHERE id_notificacion = ? AND id_usuario = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id_notificacion, $id_usuario]);
    }
}
?>
