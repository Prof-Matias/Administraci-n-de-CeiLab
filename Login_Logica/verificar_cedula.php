<?php 
require_once __DIR__ . '/../Config/Conexion.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $rawCedula = $_POST["cedula"] ?? $_POST["Cedula"] ?? '';
    $cedula = preg_replace('/[^0-9]/', '', trim($rawCedula));

    // Limpiar sesión temporal previa
    unset($_SESSION['cedula_temp']);

    if (!empty($cedula)) {
        try {
            $pdo = Conexion::conectar();
            // Traer también estado_cuenta
            $query = "SELECT ci, estado_cuenta FROM usuario WHERE ci = :cedula";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(":cedula", $cedula, PDO::PARAM_STR);
            $stmt->execute();

            $verificar = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$verificar) {
                // No existe
                header("Location: ../index.php?error=1");
                exit();
            }

            if (strtoupper(trim($verificar['estado_cuenta'])) !== 'ACTIVA') {
                // Cuenta inactiva: bloquear
                header("Location: ../index.php?error=desactivada");
                exit();
            }

            // Existe y está activa
            $_SESSION['cedula_temp'] = $cedula;
            header("Location: ../Contrasenia.php");
            exit();

        } catch (PDOException $e) {
            error_log($e->getMessage());
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