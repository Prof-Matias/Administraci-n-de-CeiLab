<?php 
include "../Config/Conexion.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cedula = isset($_POST["Cedula"]) ? trim($_POST["Cedula"]) : '';

    if (!empty($cedula)) {
        try {
            function verificarAdmin($pdo, $cedula) {
                $query = "SELECT usuario.CI FROM usuario 
                          INNER JOIN administrador ON usuario.CI = administrador.CI_Administrador 
                          WHERE usuario.CI = :cedula";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(":cedula", $cedula, PDO::PARAM_STR);
                $stmt->execute();
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            function verificarCliente($pdo, $cedula) {
                $query = "SELECT usuario.CI FROM usuario
                          INNER JOIN cliente ON usuario.CI = cliente.CI_Cliente 
                          WHERE usuario.CI = :cedula";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(":cedula", $cedula, PDO::PARAM_STR);
                $stmt->execute(); 
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if (verificarAdmin($pdo, $cedula) || verificarCliente($pdo, $cedula)) {
                // Guarda el dato temporal en sesión
                $_SESSION['cedula_temp'] = $cedula;
                
                // Redirige al segundo paso
                header("Location: ../Contraseña.php");
                exit();
            } else {
                header("Location: ../Index1.php?error=1");
                exit();
            }

        } catch (PDOException $e) {
            echo "Error en la base de datos: " . $e->getMessage();
        }
    } else {
        header("Location: ../Index1.php?error=empty");
        exit();
    }
}
?>