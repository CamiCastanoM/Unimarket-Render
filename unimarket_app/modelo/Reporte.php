<?php
require_once __DIR__ . "/../config/conexion.php";

class Reporte {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    public function registrar($id_usuario_reportante, $id_producto_reportado, $motivo) {
        $sql = "INSERT INTO reportes (id_usuario_reportante, id_producto_reportado, motivo, estado)
                VALUES (?, ?, ?, 'Pendiente')";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id_usuario_reportante, $id_producto_reportado, $motivo]);
    }

    public function listar() {
        $sql = "SELECT r.*, u.nombre AS reportante, p.nombre AS producto
                FROM reportes r
                INNER JOIN usuarios u ON r.id_usuario_reportante = u.id_usuario
                INNER JOIN productos p ON r.id_producto_reportado = p.id_producto
                ORDER BY r.id_reporte DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
