<?php
require_once __DIR__ . '/conexion.php';

try {
    $db = Conexion::conectar();

    // Agregar columna imagen a la tabla productos
    $sql = "ALTER TABLE productos ADD COLUMN imagen VARCHAR(255) DEFAULT NULL AFTER ubicacion";
    $db->exec($sql);

    echo "Columna 'imagen' agregada correctamente a la tabla 'productos'.<br>";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "La columna 'imagen' ya existe en la tabla 'productos'.<br>";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
