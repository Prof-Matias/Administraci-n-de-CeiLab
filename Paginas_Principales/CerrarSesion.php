<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Vaciar todas las variables de sesión
$_SESSION = array();

// 2. Destruir la cookie de sesión de PHP en el navegador si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// 3. Eliminar explícitamente la cookie del "Recuérdame"
if (isset($_COOKIE['usuario_recuerdame'])) {
    setcookie("usuario_recuerdame", "", time() - 3600, "/");
}

// 4. Destruir la sesión en el servidor
session_destroy();

// 5. Redirigir al usuario a la página de ingreso (index.php)
header("Location: ../Index.php");
exit();
?>