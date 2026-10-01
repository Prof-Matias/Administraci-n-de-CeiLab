<?php
class Conexion {
    public static function conectar() {
        $servidor = "mysql:dbname=ceilab;host=localhost";
        $usuario = "root";
        $contrasena = "";

        try {
            $pdo = new PDO($servidor, $usuario, $contrasena, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            ));
            return $pdo;
        } catch (PDOException $error) {
            die("Error en la conexión: " . $error->getMessage());
        }
    }
}