<?php 
require_once "../Config/Conexion.php";
session_start();

// Verifica que exista la cédula temporal
if (!isset($_SESSION['cedula_temp'])) {
    header("Location: ../Index.php");
    exit();
}

$cedula = $_SESSION['cedula_temp'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contrasena = $_POST["Contraseña"] ?? '';
    
    if (!empty($contrasena)) {
        try {
            $pdo = Conexion::conectar();
            $query = "SELECT * FROM usuario WHERE CI = :cedula";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":cedula", $cedula, PDO::PARAM_STR);
            $stmt->execute();

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($contrasena, $usuario['Contraseña'])) {
                // 1. CLAVE: Guardar la CI en la sesión para que "verificar_sesion.php" no te rebote
                $_SESSION['CI'] = $usuario['CI']; 
                $_SESSION['Nombre'] = $usuario['Nombre']; 
                $_SESSION['Apellido'] = $usuario['Apellido'];
                $_SESSION['Email'] = $usuario['Email'];
                $_SESSION['Rol'] = $usuario['Rol'];
                $_SESSION['Especialidad'] = $usuario['Especialidad'];

                // Limpiar la cédula temporal
                unset($_SESSION['cedula_temp']);

                // 2. Normalizar el rol a mayúsculas ("Administrador" -> "ADMINISTRADOR")
                $rol = strtoupper(trim($usuario['Rol']));

                if ($rol == "ADMINISTRADOR" || $rol == "CLIENTE") {
                    header("Location: ../Paginas_Principales/Pagina_Principal.php");
                    exit();
                }

            } else {
                // Contraseña incorrecta
                header("Location: ../Contraseña.php?error=1");
                exit();
            }

        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    } else {
        header("Location: ../Contraseña.php?error=empty");
        exit();
    }
}
?>