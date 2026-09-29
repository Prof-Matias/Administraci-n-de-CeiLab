<?php 
    class UsuarioModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Obtener todos los usuarios de la base de datos
    public function obtenerUsuarios() {
        $query = "SELECT * FROM usuario";
        $statement = $this->pdo->prepare($query);
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    // Agregar un nuevo usuario a la base de datos
    public function agregarUsuario($nombre, $apellido, $correo,, $contrasena, $rol, $especialidad) {
        $query = "INSERT INTO usuario (Nombre, Apellido, Contraseña , Email, Rol, Especialidad) VALUES (?, ?, ?, ?, ?, ?)";
        $statement = $this->pdo->prepare($query);
        $statement->execute([$nombre, $apellido, $correo, $especialidad, $contrasena, $rol]);
    }

    // Obtener información de un usuario por su ID
    public function obtenerUsuarioPorID($cedula) {
        $query = "SELECT * FROM usuario WHERE CI = ?";
        $statement = $this->pdo->prepare($query);
        $statement->execute([$cedula]);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    // Actualizar la información de un usuario en la base de datos
    public function actualizarUsuario($cedula, $nombre, $apellido, $correo, $especialidad, $contrasena, $rol,) {
        try {
            $query = "UPDATE usuario SET Nombre = ?, Apellido = ?,  Contraseña  = ?, Email = ?, Rol = ?, Especialidad = ?  WHERE CI = ?";
            $statement = $this->pdo->prepare($query);
            $success = $statement->execute([$nombre, $apellido, $correo,, $contrasena, $rol,  $especialidad]);
    
            return $success;
        } catch (PDOException $e) {
            // Manejo de errores aquí (puedes registrar el error o devolver false)
            return false;
        }
    }
    
    // Eliminar un usuario de la base de datos
    public function eliminarUsuario($cedula) {
        // Ahora, eliminar al usuario
        $query = "DELETE FROM usuario WHERE CI = :Cedula";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':Cedula', $cedula);
        if ($stmt->execute()) {
            return true;
        } else {
            return false; 
        }
    }
    
}











?>