<?php 
// Iniciamos la sesión para poder acceder a la cédula
// que guardamos anteriormente en verificar_cedula.php.
session_start();

// Verificamos que exista una cédula guardada.
// Si alguien intenta entrar directamente sin haber ingresado su cédula, lo devolvemos al inicio.
if (!isset($_SESSION['cedula_temp'])) {
    header("Location: index.php");
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
  <link rel="stylesheet" href="Diseño.css">
  <title>Ingreso CeiLab - Contraseña</title>
</head>
<body>
  
  <!-- Switch animado para Modo Oscuro -->
  <label class="theme-switch" for="theme-toggle" title="Alternar Modo Oscuro">
    <input type="checkbox" id="theme-toggle">
    <span class="slider">
      <span class="slider-thumb">
        <span class="icon sun">☀️</span>
        <span class="icon moon">🌙</span>
      </span>
    </span>
  </label>

  <div class="mb-3">
    <img src="Material_Visual/itrio.png" alt="Logo ITRIO">
      <form action="Login_Logica/verificar_contrasenia.php" method="post">
        <label for="contrasenia" class="form-label">Contraseña</label>
        <input type="password" class="form-control" id="contrasenia" placeholder="Ingrese su contraseña" name="contrasenia" required autofocus>
        <button type="submit" class="btn btn-primary" id="iniciar">Iniciar Sesión</button>    

        <!-- Mensajes de alerta en la interfaz -->
        <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
          <div class="alert alert-danger mt-2" role="alert">
            Contraseña inválida. Intente nuevamente.
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == "empty"): ?>
          <div class="alert alert-danger mt-2" role="alert">
            El campo de contraseña no puede estar vacío.
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == "db"): ?>
          <div class="alert alert-danger mt-2" role="alert">
            Error de conexión con el servidor. Intente más tarde.
          </div>
        <?php endif; ?>
         
         <img src="Material_Visual/Logo2.png" alt="Logo CeiLab" class="Logo">
      </form>
  </div>

  <script>
    // SISTEMA DE MODO OSCURO (SWITCH ANIMADO)
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;

    // Comprueba el modo guardado en el navegador del usuario (LocalStorage)
    // Esto asegura que si lo activó en la pantalla anterior, siga activo aquí.
    if (localStorage.getItem('theme') === 'dark') {
      body.classList.add('dark-mode');
      themeToggle.checked = true; // Activa el switch visualmente
    }

    // Evento al cambiar el switch
    themeToggle.addEventListener('change', () => {
      if (themeToggle.checked) {
        body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
      } else {
        body.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
      }
    });
  </script>
</body>
</html>