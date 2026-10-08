<?php 
require_once __DIR__ . '/../Config/Conexion.php';
session_start();

// Verifica que exista la cédula temporal
if (!isset($_SESSION['cedula_temp'])) {
    header("Location: ../index.php");
    exit();
}

$cedula = $_SESSION['cedula_temp'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Captura el parámetro de contraseña (soporta varias nomenclaturas comunes)
    $contrasena = $_POST["contrasenia"] ?? $_POST["contrasena"] ?? $_POST["Contraseña"] ?? '';
    
    if (!empty($contrasena)) {
        try {
            $pdo = Conexion::conectar();
            $query = "SELECT ci, nombre, apellido, email, rol, especialidad, contrasenia FROM usuario WHERE ci = :cedula";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":cedula", $cedula, PDO::PARAM_STR);
            $stmt->execute();

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($contrasena, $usuario['contrasenia'])) {
                // Regenerar el ID de sesión por seguridad al autenticar correctamente
                session_regenerate_id(true);

                // Cargar datos del usuario en la sesión usando las claves normalizadas en minúsculas/ASCII
                $_SESSION['ci']           = $usuario['ci']; 
                $_SESSION['nombre']       = $usuario['nombre']; 
                $_SESSION['apellido']     = $usuario['apellido'];
                $_SESSION['email']        = $usuario['email'];
                $_SESSION['rol']          = $usuario['rol'];
                $_SESSION['especialidad'] = $usuario['especialidad'];

                // Para mantener compatibilidad con código existente que use claves en mayúsculas:
                $_SESSION['CI']           = $usuario['ci']; 
                $_SESSION['Nombre']       = $usuario['nombre']; 
                $_SESSION['Apellido']     = $usuario['apellido'];
                $_SESSION['Email']        = $usuario['email'];
                $_SESSION['Rol']          = $usuario['rol'];
                $_SESSION['Especialidad'] = $usuario['especialidad'];

                // Limpiar la cédula temporal
                unset($_SESSION['cedula_temp']);

                // Normalizar el rol a mayúsculas ("ADMINISTRADOR" / "CLIENTE")
                $rol = strtoupper(trim($usuario['rol']));

                if ($rol === "ADMINISTRADOR" || $rol === "CLIENTE") {
                    header("Location: ../Paginas_Principales/Pagina_Principal.php");
                    exit();
                } else {
                    header("Location: ../index.php?error=rol");
                    exit();
                }

            } else {
                // Contraseña incorrecta
                header("Location: ../Contrasenia.php?error=1");
                exit();
            }

        } catch (PDOException $e) {
            header("Location: ../Contrasenia.php?error=db");
            exit();
        }
    } else {
        header("Location: ../Contrasenia.php?error=empty");
        exit();
    }
} else {
    header("Location: ../Contrasenia.php");
    exit();
}