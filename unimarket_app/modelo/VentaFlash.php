<?php
require_once __DIR__ . "/../config/conexion.php";

class VentaFlash {
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

    public function listarActivas() {
        $ahora = date("Y-m-d H:i:s");
        $sql = "SELECT vf.*, p.nombre, p.id_usuario, p.imagen, p.ubicacion, p.descripcion, u.nombre AS vendedor
                FROM ventas_flash vf
                JOIN productos p ON vf.id_producto = p.id_producto
                JOIN usuarios u ON p.id_usuario = u.id_usuario
                WHERE ? BETWEEN vf.hora_inicio AND vf.hora_fin
                AND vf.stock_flash > 0
                ORDER BY vf.hora_fin ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ahora]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorUsuario($id_usuario){
        $stockInicialSelect = $this->columnaExiste('ventas_flash', 'stock_inicial')
            ? "IFNULL(vf.stock_inicial, vf.stock_flash) AS stock_inicial,"
            : "vf.stock_flash AS stock_inicial,";

        $sql = "SELECT
                    vf.id_flash,
                    vf.id_producto,
                    vf.precio_oferta,
                    vf.stock_flash,
                    {$stockInicialSelect}
                    vf.hora_inicio,
                    vf.hora_fin,
                    p.nombre,
                    p.imagen,
                    p.ubicacion,
                    p.id_usuario,
                    p.descripcion,
                    u.nombre AS vendedor
                FROM ventas_flash vf
                INNER JOIN productos p ON vf.id_producto = p.id_producto
                INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                WHERE p.id_usuario = ?
                ORDER BY vf.id_flash DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrar($id_producto, $precio_oferta, $stock){
        $hora_inicio = date("Y-m-d H:i:s");
        $hora_fin = date("Y-m-d 23:59:59");

        if ($this->columnaExiste('ventas_flash', 'stock_inicial')) {
            $sql = "INSERT INTO ventas_flash (id_producto, precio_oferta, hora_inicio, hora_fin, stock_flash, stock_inicial)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_producto, $precio_oferta, $hora_inicio, $hora_fin, $stock, $stock]);
        }

        $sql = "INSERT INTO ventas_flash (id_producto, precio_oferta, hora_inicio, hora_fin, stock_flash)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id_producto, $precio_oferta, $hora_inicio, $hora_fin, $stock]);
    }

    public function obtenerPorId($id_flash){
        $sql = "SELECT vf.*, p.nombre, p.id_usuario, p.imagen, p.ubicacion, p.id_categoria
                FROM ventas_flash vf
                JOIN productos p ON vf.id_producto = p.id_producto
                WHERE vf.id_flash = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_flash]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function editar($id_flash, $nombre, $precio, $stock){
        try {
            if ($this->columnaExiste('ventas_flash', 'stock_inicial')) {
                $sql = "UPDATE ventas_flash
                        SET precio_oferta = ?,
                            stock_flash = ?,
                            stock_inicial = ?
                        WHERE id_flash = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$precio, $stock, $stock, $id_flash]);
            } else {
                $sql = "UPDATE ventas_flash
                        SET precio_oferta = ?, stock_flash = ?
                        WHERE id_flash = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$precio, $stock, $id_flash]);
            }

            if ($stock <= 0) {
                $sqlDelete = "DELETE FROM ventas_flash WHERE id_flash = ?";
                $stmtDelete = $this->db->prepare($sqlDelete);
                $stmtDelete->execute([$id_flash]);
                return true;
            }

            $sql2 = "UPDATE productos p
                    JOIN ventas_flash vf ON p.id_producto = vf.id_producto
                    SET p.nombre = ?, p.precio = ?
                    WHERE vf.id_flash = ?";
            $stmt2 = $this->db->prepare($sql2);
            return $stmt2->execute([$nombre, $precio, $id_flash]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function reactivar($id_flash){
        try {
            if ($this->columnaExiste('ventas_flash', 'stock_inicial')) {
                $sql = "UPDATE ventas_flash
                        SET hora_inicio = NOW(),
                            hora_fin = CONCAT(CURDATE(), ' 23:59:59'),
                            stock_flash = IF(stock_flash <= 0, IFNULL(stock_inicial, 1), stock_flash),
                            stock_inicial = IF(stock_inicial IS NULL OR stock_inicial <= 0, IF(stock_flash <= 0, 1, stock_flash), stock_inicial)
                        WHERE id_flash = ?";
            } else {
                $sql = "UPDATE ventas_flash
                        SET hora_inicio = NOW(),
                            hora_fin = CONCAT(CURDATE(), ' 23:59:59'),
                            stock_flash = IF(stock_flash <= 0, 1, stock_flash)
                        WHERE id_flash = ?";
            }
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_flash]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function eliminar($id_flash){
        try {
            $sql = "DELETE FROM ventas_flash WHERE id_flash = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_flash]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
