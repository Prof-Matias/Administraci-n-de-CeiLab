<?php
// Utilizar ruta basada en la ubicación del archivo
require_once __DIR__ . '/../../Config/Conexion.php';

class Modelo_Usuario {
    private $conexion;

    public function __construct() {
        $this->conexion = Conexion::conectar();
    }

    public function obtenerUsuarios() {
        $stmt = $this->conexion->prepare("SELECT CI, Nombre, Apellido, Email, Rol, Especialidad FROM usuario");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerUsuarioPorCI($cedula) {
        $stmt = $this->conexion->prepare("SELECT CI, Nombre, Apellido, Email, Rol, Especialidad, Contraseña FROM usuario WHERE CI = ?");
        $stmt->execute([$cedula]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function guardarUsuario($cedula, $nombre, $apellido, $correo, $contrasena, $rol, $especialidad) {
        $stmt = $this->conexion->prepare("INSERT INTO usuario (CI, Nombre, Apellido, Email, Contraseña, Rol, Especialidad) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$cedula, $nombre, $apellido, $correo, $contrasena, $rol, $especialidad]);
    }

    public function actualizarUsuario($cedula, $nombre, $apellido, $correo, $contrasena, $rol, $especialidad) {
        $stmt = $this->conexion->prepare("UPDATE usuario SET Nombre = ?, Apellido = ?, Email = ?, Contraseña = ?, Rol = ?, Especialidad = ? WHERE CI = ?");
        return $stmt->execute([$nombre, $apellido, $correo, $contrasena, $rol, $especialidad, $cedula]);
    }

    public function eliminarUsuario($cedula) {
        $stmt = $this->conexion->prepare("DELETE FROM usuario WHERE CI = ?");
        return $stmt->execute([$cedula]);
    }
}