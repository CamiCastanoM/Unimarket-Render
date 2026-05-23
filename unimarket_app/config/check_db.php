<?php
require_once __DIR__ . '/conexion.php';
try {
    $db = Conexion::conectar();
    $stmt = $db->query("DESCRIBE productos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
