<?php 
include "../Config/Conexion.php";
session_start();

// Verifica que exista la cédula temporal
if (!isset($_SESSION['cedula_temp'])) {
    header("Location: ../Index1.php");
    exit();
}

$cedula = $_SESSION['cedula_temp'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contraseña = $_POST["Contraseña"] ?? '';

    try {
        // Se reutiliza $pdo de Conexion.php
        $query = "SELECT * FROM usuario WHERE CI = :cedula";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":cedula", $cedula, PDO::PARAM_STR);
        $stmt->execute();

        //Toma la cedula ingresada anteriormente
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        //Verifica si la contraseña ingresada con la contraseña del usuario guardada
        if ($usuario && password_verify($contraseña, $usuario['Contraseña'])) {
            // Login correcto
            $_SESSION['cedula'] = $usuario['CI'];
            
            // Comprobar rol de Administrador
            $stmtAdmin = $pdo->prepare("SELECT * FROM administrador WHERE CI_Administrador = :cedula");
            $stmtAdmin->bindParam(":cedula", $cedula, PDO::PARAM_STR);
            $stmtAdmin->execute();

            if ($stmtAdmin->fetch()) {
                header("Location: ../CRUD_Usuarios/Controlador/Controlador_Usuarios.php");
                //unset($_SESSION['cedula_temp']); // Limpia el dato temporal
                exit();
            }

            // Comprobar rol de Cliente
            $stmtCliente = $pdo->prepare("SELECT * FROM cliente WHERE CI_Cliente = :cedula");
            $stmtCliente->bindParam(":cedula", $cedula, PDO::PARAM_STR);
            $stmtCliente->execute();

            if ($stmtCliente->fetch()) {
                header("Location: ../CRUD_Usuarios/Vista/Vista_Usuario.php");
                //unset($_SESSION['cedula_temp']); // Limpia el dato temporal
                exit();
            }

            header("Location: ../Index1.php?error=rol");
            exit();

        } else {
            // Contraseña o usuario incorrecto: permanece en el paso 2
            header("Location: ../Contraseña.php?error=contraseña");
            exit();
        }

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>