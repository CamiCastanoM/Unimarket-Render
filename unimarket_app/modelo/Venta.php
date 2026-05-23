<?php
require_once __DIR__ . "/../config/conexion.php";

class Venta {
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

    private function crearReferenciaPago() {
        return 'UM-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
    }

    private function notificacionesTiene($columna) {
        return $this->columnaExiste('notificaciones', $columna);
    }

    private function crearNotificacion($id_usuario, $mensaje, $url = null, $tipo = 'Sistema', $titulo = null) {
        try {
            $tieneTipo = $this->notificacionesTiene('tipo');
            $tieneTitulo = $this->notificacionesTiene('titulo');

            if ($tieneTipo && $tieneTitulo) {
                $stmt = $this->db->prepare("INSERT INTO notificaciones (id_usuario, titulo, mensaje, url, tipo, leida, fecha) VALUES (?, ?, ?, ?, ?, 0, NOW())");
                return $stmt->execute([$id_usuario, $titulo, $mensaje, $url, $tipo]);
            }

            $stmt = $this->db->prepare("INSERT INTO notificaciones (id_usuario, mensaje, url, leida, fecha) VALUES (?, ?, ?, 0, NOW())");
            return $stmt->execute([$id_usuario, $mensaje, $url]);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function listarPorComprador($id_comprador) {
        $estadoSql = $this->columnaExiste('ventas', 'estado')
            ? "COALESCE(sv.estado_actual, v.estado, 'Pendiente')"
            : "COALESCE(sv.estado_actual, 'Pendiente')";

        $metodoSql = $this->columnaExiste('ventas', 'metodo_pago')
            ? "v.metodo_pago"
            : "'Pago contra entrega' AS metodo_pago";

        $estadoPagoSql = $this->columnaExiste('ventas', 'estado_pago')
            ? "v.estado_pago"
            : "'No aplica' AS estado_pago";

        $pasarelaSql = $this->columnaExiste('ventas', 'pasarela')
            ? "v.pasarela"
            : "'local' AS pasarela";

        $referenciaSql = $this->columnaExiste('ventas', 'referencia_pago')
            ? "v.referencia_pago"
            : "NULL AS referencia_pago";

        $sql = "SELECT 
                    v.id_venta,
                    v.fecha,
                    v.total,
                    {$estadoSql} AS estado_actual,
                    {$metodoSql},
                    {$estadoPagoSql},
                    {$pasarelaSql},
                    {$referenciaSql},
                    pe.nombre_lugar AS punto_entrega,
                    GROUP_CONCAT(CONCAT(p.nombre, ' x', dv.cantidad) ORDER BY p.nombre SEPARATOR ', ') AS producto_nombre,
                    COUNT(dv.id_detalle) AS total_items
                FROM ventas v
                JOIN detalle_venta dv ON v.id_venta = dv.id_venta
                JOIN productos p ON dv.id_producto = p.id_producto
                LEFT JOIN puntos_encuentro pe ON v.id_punto_encuentro = pe.id_punto
                LEFT JOIN seguimiento_venta sv ON v.id_venta = sv.id_venta
                WHERE v.id_comprador = ?
                GROUP BY v.id_venta, v.fecha, v.total, estado_actual, metodo_pago, estado_pago, pasarela, referencia_pago, pe.nombre_lugar
                ORDER BY v.fecha DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_comprador]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPedidosVendedor($id_vendedor) {
        $estadoSql = $this->columnaExiste('ventas', 'estado')
            ? "COALESCE(sv.estado_actual, v.estado, 'Pendiente')"
            : "COALESCE(sv.estado_actual, 'Pendiente')";

        $metodoSql = $this->columnaExiste('ventas', 'metodo_pago')
            ? "v.metodo_pago"
            : "'Pago contra entrega' AS metodo_pago";

        $estadoPagoSql = $this->columnaExiste('ventas', 'estado_pago')
            ? "v.estado_pago"
            : "'No aplica' AS estado_pago";

        $pasarelaSql = $this->columnaExiste('ventas', 'pasarela')
            ? "v.pasarela"
            : "'local' AS pasarela";

        $sql = "SELECT
                    v.id_venta,
                    v.fecha,
                    v.total AS total_pedido,
                    SUM(dv.subtotal) AS total_vendedor,
                    SUM(dv.cantidad) AS cantidad_total,
                    GROUP_CONCAT(CONCAT(p.nombre, ' x', dv.cantidad) ORDER BY p.nombre SEPARATOR ', ') AS productos_resumen,
                    u.nombre AS comprador,
                    u.correo AS correo_comprador,
                    pe.nombre_lugar AS punto_entrega,
                    {$metodoSql},
                    {$estadoPagoSql},
                    {$pasarelaSql},
                    {$estadoSql} AS estado_actual
                FROM ventas v
                INNER JOIN detalle_venta dv ON dv.id_venta = v.id_venta
                INNER JOIN productos p ON p.id_producto = dv.id_producto
                INNER JOIN usuarios u ON u.id_usuario = v.id_comprador
                LEFT JOIN puntos_encuentro pe ON pe.id_punto = v.id_punto_encuentro
                LEFT JOIN seguimiento_venta sv ON sv.id_venta = v.id_venta
                WHERE p.id_usuario = ?
                GROUP BY v.id_venta, v.fecha, v.total, u.nombre, u.correo, pe.nombre_lugar, metodo_pago, estado_pago, pasarela, estado_actual
                ORDER BY v.fecha DESC, v.id_venta DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_vendedor]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerDetalleCompra($id_venta) {
        $estadoSql = $this->columnaExiste('ventas', 'estado')
            ? "COALESCE(sv.estado_actual, v.estado, 'Pendiente')"
            : "COALESCE(sv.estado_actual, 'Pendiente')";

        $metodoSql = $this->columnaExiste('ventas', 'metodo_pago')
            ? "v.metodo_pago"
            : "'Pago contra entrega' AS metodo_pago";

        $estadoPagoSql = $this->columnaExiste('ventas', 'estado_pago')
            ? "v.estado_pago"
            : "'No aplica' AS estado_pago";

        $referenciaSql = $this->columnaExiste('ventas', 'referencia_pago')
            ? "v.referencia_pago"
            : "NULL AS referencia_pago";

        $pasarelaSql = $this->columnaExiste('ventas', 'pasarela')
            ? "v.pasarela"
            : "'local' AS pasarela";

        $sql = "SELECT 
                    v.id_venta,
                    v.fecha,
                    v.total,
                    {$metodoSql},
                    {$estadoPagoSql},
                    {$referenciaSql},
                    {$pasarelaSql},
                    p.nombre,
                    p.imagen,
                    u.nombre AS vendedor,
                    dv.cantidad,
                    dv.subtotal,
                    pe.nombre_lugar AS punto_entrega,
                    pe.referencia,
                    {$estadoSql} AS estado_actual
                FROM ventas v
                JOIN detalle_venta dv ON v.id_venta = dv.id_venta
                JOIN productos p ON dv.id_producto = p.id_producto
                JOIN usuarios u ON p.id_usuario = u.id_usuario
                LEFT JOIN puntos_encuentro pe ON v.id_punto_encuentro = pe.id_punto
                LEFT JOIN seguimiento_venta sv ON v.id_venta = sv.id_venta
                WHERE v.id_venta = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_venta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerVenta($id_venta) {
        $stmt = $this->db->prepare("SELECT * FROM ventas WHERE id_venta = ? LIMIT 1");
        $stmt->execute([$id_venta]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerPorReferenciaPago($referencia) {
        if (!$this->columnaExiste('ventas', 'referencia_pago')) {
            return null;
        }
        $stmt = $this->db->prepare("SELECT * FROM ventas WHERE referencia_pago = ? LIMIT 1");
        $stmt->execute([$referencia]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crearDesdeCarrito($id_comprador, $id_punto_encuentro, $metodo_pago = 'Pago contra entrega') {
        try {
            $this->db->beginTransaction();

            $sqlCarrito = "SELECT
                            c.id_carrito,
                            c.cantidad,
                            p.id_producto,
                            p.nombre,
                            p.precio,
                            p.id_usuario AS id_vendedor
                          FROM carrito c
                          INNER JOIN productos p ON c.id_producto = p.id_producto
                          WHERE c.id_usuario = ?";

            $stmtCarrito = $this->db->prepare($sqlCarrito);
            $stmtCarrito->execute([$id_comprador]);
            $items = $stmtCarrito->fetchAll(PDO::FETCH_ASSOC);

            if (empty($items)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Tu carrito está vacío'];
            }

            $total = 0;
            foreach ($items as $item) {
                $total += ((float)$item['precio'] * (int)$item['cantidad']);
            }

            $esWompi = stripos($metodo_pago, 'wompi') !== false;
            $referenciaPago = $esWompi ? $this->crearReferenciaPago() : null;
            $estadoPago = $esWompi ? 'Pendiente' : 'No aplica';
            $pasarela = $esWompi ? 'wompi' : 'local';
            $estadoPedido = $esWompi ? 'Pendiente de pago' : 'Pendiente';

            $cols = ['fecha', 'total', 'id_comprador', 'id_punto_encuentro'];
            $vals = ['NOW()', '?', '?', '?'];
            $params = [$total, $id_comprador, $id_punto_encuentro];

            if ($this->columnaExiste('ventas', 'metodo_pago')) { $cols[] = 'metodo_pago'; $vals[] = '?'; $params[] = $metodo_pago; }
            if ($this->columnaExiste('ventas', 'estado')) { $cols[] = 'estado'; $vals[] = '?'; $params[] = $estadoPedido; }
            if ($this->columnaExiste('ventas', 'estado_pago')) { $cols[] = 'estado_pago'; $vals[] = '?'; $params[] = $estadoPago; }
            if ($this->columnaExiste('ventas', 'pasarela')) { $cols[] = 'pasarela'; $vals[] = '?'; $params[] = $pasarela; }
            if ($this->columnaExiste('ventas', 'referencia_pago')) { $cols[] = 'referencia_pago'; $vals[] = '?'; $params[] = $referenciaPago; }

            $stmtVenta = $this->db->prepare("INSERT INTO ventas (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")");
            $stmtVenta->execute($params);

            $id_venta = (int)$this->db->lastInsertId();

            $stmtDetalle = $this->db->prepare("INSERT INTO detalle_venta (id_venta, id_producto, cantidad, subtotal) VALUES (?, ?, ?, ?)");
            $stmtFlash = $this->db->prepare("UPDATE ventas_flash
                                             SET stock_flash = GREATEST(stock_flash - ?, 0)
                                             WHERE id_producto = ?
                                               AND NOW() BETWEEN hora_inicio AND hora_fin
                                               AND stock_flash > 0");

            foreach ($items as $item) {
                $cantidad = (int)$item['cantidad'];
                $subtotal = ((float)$item['precio'] * $cantidad);
                $stmtDetalle->execute([$id_venta, $item['id_producto'], $cantidad, $subtotal]);
                $stmtFlash->execute([$cantidad, $item['id_producto']]);
            }

            $stmtSeg = $this->db->prepare("INSERT INTO seguimiento_venta (id_venta, estado_actual, ultima_actualizacion) VALUES (?, ?, NOW())");
            $stmtSeg->execute([$id_venta, $estadoPedido]);

            $vendedores = [];
            foreach ($items as $item) {
                $vendedores[(int)$item['id_vendedor']] = true;
            }
            foreach (array_keys($vendedores) as $id_vendedor) {
                $this->crearNotificacion(
                    $id_vendedor,
                    $esWompi ? 'Tienes un nuevo pedido #' . $id_venta . ' pendiente de pago' : 'Tienes un nuevo pedido #' . $id_venta,
                    'pedido/' . $id_venta,
                    'Pedidos',
                    'Nuevo pedido'
                );
            }

            $stmtVaciar = $this->db->prepare("DELETE FROM carrito WHERE id_usuario = ?");
            $stmtVaciar->execute([$id_comprador]);

            $this->db->commit();
            return [
                'success' => true,
                'id_venta' => $id_venta,
                'referencia_pago' => $referenciaPago,
                'pasarela' => $pasarela,
                'estado_pago' => $estadoPago,
                'total' => $total
            ];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Error creando el pedido: ' . $e->getMessage()];
        }
    }

    public function actualizarPago($id_venta, $estado_pago, $transaction_id = null, $payload = null) {
        $permitidos = ['Pendiente', 'Aprobado', 'Rechazado', 'Anulado', 'Error', 'Contra entrega'];
        if (!in_array($estado_pago, $permitidos, true)) {
            $estado_pago = 'Pendiente';
        }

        $sets = [];
        $params = [];

        if ($this->columnaExiste('ventas', 'estado_pago')) { $sets[] = 'estado_pago = ?'; $params[] = $estado_pago; }
        if ($this->columnaExiste('ventas', 'wompi_transaction_id') && $transaction_id) { $sets[] = 'wompi_transaction_id = ?'; $params[] = $transaction_id; }
        if ($this->columnaExiste('ventas', 'payload_pago') && $payload !== null) { $sets[] = 'payload_pago = ?'; $params[] = is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE); }
        if ($this->columnaExiste('ventas', 'fecha_pago') && $estado_pago === 'Aprobado') { $sets[] = 'fecha_pago = NOW()'; }

        if ($this->columnaExiste('ventas', 'estado')) {
            if ($estado_pago === 'Aprobado') {
                $sets[] = 'estado = ?';
                $params[] = 'Pendiente';
            } elseif (in_array($estado_pago, ['Rechazado', 'Anulado', 'Error'], true)) {
                $sets[] = 'estado = ?';
                $params[] = 'Cancelado';
            }
        }

        if (!empty($sets)) {
            $params[] = $id_venta;
            $stmt = $this->db->prepare("UPDATE ventas SET " . implode(', ', $sets) . " WHERE id_venta = ?");
            $stmt->execute($params);
        }

        if ($estado_pago === 'Aprobado') {
            $this->actualizarEstado($id_venta, 'Pendiente', false);
        } elseif (in_array($estado_pago, ['Rechazado', 'Anulado', 'Error'], true)) {
            $this->actualizarEstado($id_venta, 'Cancelado', false);
        }

        try {
            $venta = $this->obtenerVenta($id_venta);
            if ($venta && !empty($venta['id_comprador'])) {
                $this->crearNotificacion(
                    $venta['id_comprador'],
                    'El pago del pedido #' . $id_venta . ' está: ' . $estado_pago,
                    'pedido/' . $id_venta,
                    'Compras',
                    'Pago ' . strtolower($estado_pago)
                );
            }
        } catch (Throwable $e) {}

        return true;
    }

    public function actualizarEstado($id_venta, $estado, $notificar = true) {
        $permitidos = ['Pendiente de pago', 'Pendiente', 'Aceptado', 'Preparando', 'En camino', 'Entregado', 'Cancelado'];
        if (!in_array($estado, $permitidos, true)) {
            return false;
        }

        $sqlExiste = "SELECT id_seguimiento FROM seguimiento_venta WHERE id_venta = ? LIMIT 1";
        $stmtExiste = $this->db->prepare($sqlExiste);
        $stmtExiste->execute([$id_venta]);
        $existe = $stmtExiste->fetch(PDO::FETCH_ASSOC);

        if ($existe) {
            $stmt = $this->db->prepare("UPDATE seguimiento_venta SET estado_actual = ?, ultima_actualizacion = NOW() WHERE id_venta = ?");
            $ok = $stmt->execute([$estado, $id_venta]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO seguimiento_venta (id_venta, estado_actual, ultima_actualizacion) VALUES (?, ?, NOW())");
            $ok = $stmt->execute([$id_venta, $estado]);
        }

        if ($this->columnaExiste('ventas', 'estado')) {
            $stmtVenta = $this->db->prepare("UPDATE ventas SET estado = ? WHERE id_venta = ?");
            $stmtVenta->execute([$estado, $id_venta]);
        }

        if ($notificar) {
            try {
                $stmtComprador = $this->db->prepare("SELECT id_comprador FROM ventas WHERE id_venta = ? LIMIT 1");
                $stmtComprador->execute([$id_venta]);
                $comprador = $stmtComprador->fetch(PDO::FETCH_ASSOC);
                if ($comprador && !empty($comprador['id_comprador'])) {
                    $this->crearNotificacion(
                        $comprador['id_comprador'],
                        'Tu pedido #' . $id_venta . ' cambió a: ' . $estado,
                        'pedido/' . $id_venta,
                        'Pedidos',
                        'Estado actualizado'
                    );
                }
            } catch (Throwable $e) {}
        }

        return $ok;
    }
}
?>
