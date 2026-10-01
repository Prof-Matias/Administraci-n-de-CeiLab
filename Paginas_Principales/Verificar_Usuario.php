<?php
session_start();
require_once "../Config/Conexion.php";

// 1. Si no existe la sesión, redirigir al login
if (!isset($_SESSION['CI'])) {
    header("Location: ../Index.php");
    exit();
}

// 2. Verificar en la base de datos si el usuario sigue existiendo
try {
    $pdo = Conexion::conectar();
    $stmt = $pdo->prepare("SELECT CI FROM usuario WHERE CI = :cedula");
    $stmt->execute([':cedula' => $_SESSION['CI']]);
    $usuarioExiste = $stmt->fetch();

    // Si el usuario ya NO existe en la base de datos
    if (!$usuarioExiste) {
        session_unset();     // Vacía todas las variables de sesión
        session_destroy();   // Destruye la sesión en el servidor
        header("Location: ../Index.php?error=usuario_eliminado");
        exit();
    }
} catch (PDOException $e) {
    // Si ocurre un error de conexión, por seguridad se corta la ejecución
    die("Error al verificar la sesión.");
}
?>