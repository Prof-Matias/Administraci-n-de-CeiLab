<?php
// Conexion con la Base de datos
// (fix: el comentario estaba ANTES de "<?php" y se enviaba como texto al navegador,
//  lo que rompe session_start()/header() en las páginas que incluyen este archivo)

// fix: decía "mtysql". Además, "charset=utf8mb4" en el DSN (Data Source Name) ya configura la codificación (PHP y MySQL) de la
// conexión (igual que las tablas), por eso no hace falta "PDO::MYSQL_ATTR_INIT_COMMAND" (xq es decir 2 veces lo mismo y esa constante esta deprecada).
// "Deprecada" significa que la función o característica sigue funcionando, pero no se recomienda su uso y puede eliminarse en futuras versiones de PHP.

$servidor   = "mysql:dbname=ceilab;host=localhost;charset=utf8mb4";
$usuario    = "root";
$contraseña = "";

// Try para capturar errores
try {
    $pdo = new PDO($servidor, $usuario, $contraseña, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION // los errores de SQL lanzan excepción
    ));
} catch (PDOException $error) {
    echo "Error en la conexión: " . $error->getMessage(); //Mostrar el error en caso de que falle la conexión
}
?>
