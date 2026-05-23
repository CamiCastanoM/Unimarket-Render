<?php

require_once __DIR__ . "/../config/conexion.php";

class Producto
{
    private $db;

    public function __construct()
    {
        $this->db = Conexion::conectar();
    }

    // =========================================
    // REGISTRAR PRODUCTO
    // =========================================

    public function registrar(
        $nombre,
        $precio,
        $id_usuario,
        $id_categoria,
        $descripcion,
        $ubicacion,
        $imagen = null
    )
    {
        try {

            $sql = "INSERT INTO productos 
            (
                nombre,
                precio,
                id_usuario,
                id_categoria,
                descripcion,
                ubicacion,
                imagen
            ) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);

            $resultado = $stmt->execute([
                $nombre,
                $precio,
                $id_usuario,
                $id_categoria,
                $descripcion,
                $ubicacion,
                $imagen
            ]);

            if ($resultado) {

                return intval($this->db->lastInsertId());

            } else {

                echo "<pre>";
                print_r($stmt->errorInfo());
                echo "</pre>";
                exit();

            }

        } catch (PDOException $e) {

            die("ERROR PRODUCTO: " . $e->getMessage());

        }
    }

    // =========================================
    // LISTAR TODOS
    // =========================================

    public function listarSencillo()
    {

        $sql = "SELECT 
                    p.*, 
                    u.nombre AS vendedor, 
                    c.nombre AS categoria_nombre

                FROM productos p

                JOIN usuarios u 
                    ON p.id_usuario = u.id_usuario

                LEFT JOIN categorias c 
                    ON p.id_categoria = c.id_categoria

                WHERE 
                    (p.descripcion IS NULL OR p.descripcion != 'Oferta Flash')
                    AND (
                        p.estado IS NULL
                        OR p.estado != 'pausado'
                    )

                ORDER BY p.id_producto DESC";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================
    // LISTAR POR USUARIO
    // =========================================

    public function listarPorUsuario($id_usuario)
    {

        $sql = "SELECT 
                    p.*,

                    c.nombre AS categoria_nombre,

                    u.nombre AS vendedor,
                    u.telefono,
                    u.banner,
                    u.logo,
                    u.descripcion_tienda

                FROM productos p

                LEFT JOIN categorias c
                    ON p.id_categoria = c.id_categoria

                LEFT JOIN usuarios u
                    ON p.id_usuario = u.id_usuario

                WHERE 
                    p.id_usuario = ?
                    AND (p.descripcion IS NULL OR (p.descripcion IS NULL OR p.descripcion != 'Oferta Flash'))
                    AND (
                        p.estado IS NULL
                        OR p.estado != 'pausado'
                    )

                ORDER BY p.id_producto DESC";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$id_usuario]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================
    // OBTENER POR ID
    // =========================================

    public function obtenerPorId($id_producto)
    {

        $sql = "SELECT 
                    p.*, 
                    u.nombre AS vendedor,
                    c.nombre AS categoria_nombre

                FROM productos p

                JOIN usuarios u
                    ON p.id_usuario = u.id_usuario

                LEFT JOIN categorias c
                    ON p.id_categoria = c.id_categoria

                WHERE p.id_producto = ?

                LIMIT 1";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$id_producto]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // =========================================
    // PAUSAR PRODUCTO
    // =========================================

    public function pausarProducto($id_producto)
    {

        try {

            $sql = "UPDATE productos
                    SET estado = 'pausado'
                    WHERE id_producto = ?";

            $stmt = $this->db->prepare($sql);

            return $stmt->execute([$id_producto]);

        } catch (PDOException $e) {

            die("ERROR PAUSAR PRODUCTO: " . $e->getMessage());

        }
    }

    // =========================================
    // ACTIVAR PRODUCTO
    // =========================================

    public function activarProducto($id_producto)
    {

        try {

            $sql = "UPDATE productos
                    SET estado = 'activo'
                    WHERE id_producto = ?";

            $stmt = $this->db->prepare($sql);

            return $stmt->execute([$id_producto]);

        } catch (PDOException $e) {

            die("ERROR ACTIVAR PRODUCTO: " . $e->getMessage());

        }
    }

    // =========================================
    // ELIMINAR PRODUCTO
    // =========================================

    public function eliminarProducto($id_producto)
    {

        try {

            // eliminar ventas flash relacionadas

            $this->db
                ->prepare(
                    "DELETE FROM ventas_flash 
                     WHERE id_producto = ?"
                )
                ->execute([$id_producto]);

            // eliminar favoritos relacionados

            $this->db
                ->prepare(
                    "DELETE FROM favoritos 
                     WHERE id_producto = ?"
                )
                ->execute([$id_producto]);

            // eliminar producto

            $sql = "DELETE FROM productos
                    WHERE id_producto = ?";

            $stmt = $this->db->prepare($sql);

            return $stmt->execute([$id_producto]);

        } catch (PDOException $e) {

            die(
                "ERROR ELIMINAR PRODUCTO: " .
                $e->getMessage()
            );

        }
    }

    // =========================================
    // MÉTODO ANTIGUO COMPATIBLE
    // =========================================

    public function eliminar($id_producto)
    {
        return $this->eliminarProducto($id_producto);
    }

    public function actualizar(
    $id,
    $nombre,
    $precio,
    $descripcion,
    $ubicacion,
    $id_categoria
    ){

        $sql = "UPDATE productos
                SET nombre = ?,
                    precio = ?,
                    descripcion = ?,
                    ubicacion = ?,
                    id_categoria = ?
                WHERE id_producto = ?";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $nombre,
            $precio,
            $descripcion,
            $ubicacion,
            $id_categoria,
            $id
        ]);

    }
}

?>