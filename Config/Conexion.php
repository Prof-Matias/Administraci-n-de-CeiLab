// Conexion con la Base de datos
<?php
$servidor="mtysql:dbname=ceilab;host=localhost";
$usuario="root";
$contraseña="";

// Try para capturar errores
try {
    $pdo = new PDO($servidor, $usuario, $contraseña, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
} catch (PDOException $error) {
    echo "Error en la conexión: " . $error->getMessage(); //Mostrar el error en caso de que falle la conexión
}
?>