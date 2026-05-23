<?php
require_once __DIR__ . '/conexion.php';
try {
    $db = Conexion::conectar();
    $stmt = $db->query("DESCRIBE mensajes");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($cols);
    echo "</pre>";
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
