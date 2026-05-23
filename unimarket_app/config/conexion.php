<?php
require_once __DIR__ . '/app_config.php';

class Conexion {
    public static function conectar() {
        $host = env_value('DB_HOST', 'localhost');
        $db   = env_value('DB_NAME', 'unimarket');
        $user = env_value('DB_USER', 'root');
        $pass = env_value('DB_PASS', '');
        $port = env_value('DB_PORT', '3306');

        try {
            date_default_timezone_set('America/Bogota');
            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            $conexion = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $conexion->exec("SET time_zone = '-05:00'");
            return $conexion;
        } catch (PDOException $e) {
            die("Error al conectar con la base de datos: " . $e->getMessage());
        }
    }
}
?>
