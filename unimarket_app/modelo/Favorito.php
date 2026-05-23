<?php
require_once __DIR__ . "/../config/conexion.php";

class Favorito {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    public function toggle($id_usuario, $id_producto) {
        // Verificar si ya existe
        $sql = "SELECT * FROM favoritos WHERE id_usuario = ? AND id_producto = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_usuario, $id_producto]);
        
        if ($stmt->fetch()) {
            // Si existe, lo quitamos
            $sql = "DELETE FROM favoritos WHERE id_usuario = ? AND id_producto = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_usuario, $id_producto]);
        } else {
            // Si no existe, lo agregamos
            $sql = "INSERT INTO favoritos (id_usuario, id_producto) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_usuario, $id_producto]);
        }
    }

    public function esFavorito($id_usuario, $id_producto) {
        $sql = "SELECT id_favorito FROM favoritos WHERE id_usuario = ? AND id_producto = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_usuario, $id_producto]);
        return $stmt->fetch() ? true : false;
    }

    public function listarPorUsuario($id_usuario) {
        $sql = "SELECT p.*, u.nombre as vendedor 
                FROM favoritos f
                JOIN productos p ON f.id_producto = p.id_producto
                JOIN usuarios u ON p.id_usuario = u.id_usuario
                WHERE f.id_usuario = ? 
                ORDER BY f.id_favorito DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
