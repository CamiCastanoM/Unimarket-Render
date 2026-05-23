<?php
require_once __DIR__ . "/../config/conexion.php";

class Usuario {
    private $db;

    public function __construct() {
        // CAquí se conecta a la base de datos
        $this->db = Conexion::conectar();
    }

    // --- FUNCIÓN 1: REGISTRAR ---
    public function registrar($nombre, $correo, $password, $id_rol) {
        // Se encripta la contraseña
        $password_encriptada = password_hash($password, PASSWORD_DEFAULT);

        // Le decimos a MySQL en qué columnas exactas guardar la información
        $sql = "INSERT INTO usuarios (nombre, correo, contraseña, id_rol) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        // Ejecutamos el guardado enviando los datos reales
        return $stmt->execute([$nombre, $correo, $password_encriptada, $id_rol]);
    }

    // --- FUNCIÓN 2: INICIAR SESIÓN ---
    public function buscarPorCorreo($correo) {
        // Buscamos si el correo escrito por el estudiante ya existe
        $sql = "SELECT * FROM usuarios WHERE correo = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$correo]);
        
        // Devolvemos todos los datos de ese usuario si lo encuentra
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function buscarPorId($id) {

        $sql = "SELECT 
            id_usuario,
            nombre,
            correo,
            id_rol,
            telefono,
            foto_perfil,
            banner,
            logo,
            descripcion_tienda
        FROM usuarios
        WHERE id_usuario = ?";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- MÉTODOS PARA ADMINISTRADOR ---
    public function listarTodos() {

        $sql = "SELECT 
                    id_usuario,
                    nombre,
                    correo,
                    id_rol,
                    telefono,
                    foto_perfil,
                    banner,
                    logo,
                    descripcion_tienda
                FROM usuarios";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    public function eliminar($id) {
        $sql = "DELETE FROM usuarios WHERE id_usuario = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function actualizar($id, $nombre, $password = null) {
        if ($password) {
            $password_encriptada = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET nombre = ?, contraseña = ? WHERE id_usuario = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre, $password_encriptada, $id]);
        } else {
            $sql = "UPDATE usuarios SET nombre = ? WHERE id_usuario = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre, $id]);
        }
    }

    public function obtenerPorId($id){
        $sql = "SELECT * FROM usuarios WHERE id_usuario = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>