<?php 
require_once __DIR__ . '/../Config/Conexion.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Captura el parámetro tanto si viene como 'cedula' o 'Cedula'
    $rawCedula = $_POST["cedula"] ?? $_POST["Cedula"] ?? '';
    
    // Sanear: dejar solo dígitos numéricos
    $cedula = preg_replace('/[^0-9]/', '', trim($rawCedula));

    if (!empty($cedula)) {
        try {
            $pdo = Conexion::conectar();
            $query = "SELECT ci FROM usuario WHERE ci = :cedula";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":cedula", $cedula, PDO::PARAM_STR);
            $stmt->execute();

            $verificar = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($verificar) {
                $_SESSION['cedula_temp'] = $cedula;
                header("Location: ../Contrasenia.php");
                exit();
            } else {
                header("Location: ../index.php?error=1");
                exit();
            }      
    
        } catch (PDOException $e) {
            // Log de error o manejo estándar
            header("Location: ../index.php?error=db");
            exit();
        }   
    } else {
        header("Location: ../index.php?error=empty");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}