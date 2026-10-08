<?php
session_start();
require_once __DIR__ . "/../Config/Conexion.php";

// Capturar la cédula guardada en la sesión (soporta minúsculas y mayúsculas)
$cedulaSesion = $_SESSION['ci'] ?? $_SESSION['CI'] ?? null;

// 1. Si no existe la sesión de usuario, redirigir al login
if (!$cedulaSesion) {
    header("Location: ../index.php");
    exit();
}

// 2. Verificar en la base de datos si el usuario sigue existiendo
try {
    $pdo = Conexion::conectar();
    $stmt = $pdo->prepare("SELECT ci FROM usuario WHERE ci = :cedula");
    $stmt->execute([':cedula' => $cedulaSesion]);
    $usuarioExiste = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si el usuario ya NO existe en la base de datos (ej: fue eliminado por un admin)
    if (!$usuarioExiste) {
        session_unset();     // Vacía todas las variables de sesión
        session_destroy();   // Destruye la sesión activa
        header("Location: ../index.php?error=usuario_eliminado");
        exit();
    }
} catch (PDOException $e) {
    // En caso de error técnico en la BD, se interrumpe por seguridad
    die("Error al verificar la sesión del usuario.");
}
?>