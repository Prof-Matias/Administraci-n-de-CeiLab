<?php 
// Iniciamos la sesión para poder acceder a la cédula
// que guardamos anteriormente en VerificarCedula.php.
session_start();

// Verificamos que exista una cédula guardada.
// Si alguien intenta entrar directamente a Contraseña.php
// sin haber verificado la cédula, lo devolvemos al inicio.
if (!isset($_SESSION['cedula_temp'])) {
    header("Location: Index.php");
    exit();
}

// Recuperamos la cédula guardada en la sesión.
$cedula = $_SESSION['cedula_temp'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="Desing.css">
  <title>Ingreso CeiLab</title>
</head>
<body>
  <div class="mb-3">
      <form action="Login_Logica/VerificarContraseña.php" method="post">
        <label for="Contraseña" class="form-label">Contraseña</label>
        <input type="password" class="form-control" id="Contraseña" placeholder="Ingrese su contraseña" name="Contraseña">
        <button type="submit" class="btn btn-primary" id="iniciar">Iniciar Sesión</button>     
           <?php if (isset($_GET['error']) && $_GET['error'] == "contraseña"): ?>
      <div class="alert alert-danger mt-2" role="alert">
        Contraseña invalida
      </div>
    <?php endif; ?>
  </form>
  </div>
  <img src="Material_Visual/Logo2.png" alt="" class="Logo">
</body>
</html>