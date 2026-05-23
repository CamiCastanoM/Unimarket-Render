<?php

require_once __DIR__ . "/../config/conexion.php";

class Carrito {

    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    /*
    =====================================
    AGREGAR PRODUCTO
    =====================================
    */
    public function agregar($id_usuario, $id_producto) {
        $sql = "SELECT * FROM carrito
                WHERE id_usuario = ?
                AND id_producto = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_usuario, $id_producto]);
        $existe = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existe) {
            $sqlUpdate = "UPDATE carrito
                          SET cantidad = cantidad + 1
                          WHERE id_carrito = ?";

            $stmtUpdate = $this->db->prepare($sqlUpdate);
            return $stmtUpdate->execute([$existe['id_carrito']]);
        }

        $sqlInsert = "INSERT INTO carrito
                        (id_usuario, id_producto, cantidad)
                      VALUES (?, ?, 1)";

        $stmtInsert = $this->db->prepare($sqlInsert);
        return $stmtInsert->execute([$id_usuario, $id_producto]);
    }

    /*
    =====================================
    LISTAR CARRITO
    =====================================
    */
    public function listar($id_usuario) {
        $sql = "SELECT
                    c.id_carrito,
                    c.cantidad,
                    p.id_producto,
                    p.nombre,
                    p.precio,
                    p.imagen,
                    p.descripcion,
                    u.nombre AS vendedor
                FROM carrito c
                INNER JOIN productos p
                    ON c.id_producto = p.id_producto
                INNER JOIN usuarios u
                    ON p.id_usuario = u.id_usuario
                WHERE c.id_usuario = ?
                ORDER BY c.id_carrito DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_usuario]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
    =====================================
    ACTUALIZAR CANTIDAD
    =====================================
    */
    public function actualizarCantidad($id_carrito, $cantidad) {
        $cantidad = max(1, (int)$cantidad);

        $sql = "UPDATE carrito
                SET cantidad = ?
                WHERE id_carrito = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$cantidad, $id_carrito]);
    }

    /*
    =====================================
    ELIMINAR PRODUCTO
    =====================================
    */
    public function eliminar($id_carrito) {
        $sql = "DELETE FROM carrito
                WHERE id_carrito = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id_carrito]);
    }

    /*
    =====================================
    VACIAR CARRITO
    =====================================
    */
    public function vaciar($id_usuario) {
        $sql = "DELETE FROM carrito
                WHERE id_usuario = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id_usuario]);
    }
}

?>
