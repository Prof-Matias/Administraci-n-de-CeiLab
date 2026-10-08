<?php
class Conexion {
    public static function conectar() {
        $servidor = "mysql:dbname=ceilab;host=localhost;charset=utf8mb4";
        $usuario = "root";
        $contrasenia = "";

        try {
            $pdo = new PDO($servidor, $usuario, $contrasenia, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ));
            return $pdo;
        } catch (PDOException $error) {
            die("Error en la conexión a la base de datos: " . $error->getMessage());
        }
    }
}