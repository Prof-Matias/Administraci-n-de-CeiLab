<?php 
require_once "../Config/Conexion.php";
session_start();

// Verifica que exista la cédula temporal
if (!isset($_SESSION['cedula_temp'])) {
    header("Location: ../Index1.php");
    exit();
}

$cedula = $_SESSION['cedula_temp'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contrasena = $_POST["Contraseña"] ?? '';
    
    if(!empty($contrasena)){try {
        // Se reutiliza $pdo de Conexion.php
        $query = "SELECT * FROM usuario WHERE CI = :cedula";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(":cedula", $cedula, PDO::PARAM_STR);
        $stmt->execute();

        //Toma la cedula ingresada anteriormente
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if($usuario && password_verify($contrasena,$usuario['Contraseña'])){
            $_SESSION['Nombre'] = $usuario['Nombre']; 
            $_SESSION['Apellido'] = $usuario['Apellido'];
            $_SESSION['Email'] = $usuario['Email'];
            $_SESSION['Rol'] = $usuario['Rol'];
            $_SESSION['Especialidad'] = $usuario['Especialidad'];

            if($usuario['Rol'] == "ADMINISTRADOR"){
                header("Location: ../Paginas_Principales/Pagina_Principal.php");
                exit();
            }elseif($usuario['Rol'] == "CLIENTE"){
                header("Location: ../Paginas_Principales/Pagina_Principal.php");
                exit();
            }
        }else{
            header("Location: ../Contraseña.php?error=1");
        }

        
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    
        
    }
    
}else{
        header("Location: ../Contraseña.php?error=empty");
    }
}
?>