<?php 
session_start();

// Si ya tiene sesión activa o cookie "recuerdame", redirigir directamente a la página principal
if (isset($_SESSION['CI']) || isset($_COOKIE['usuario_recuerdame'])) {
    if (!isset($_SESSION['CI']) && isset($_COOKIE['usuario_recuerdame'])) {
        // Restaurar la sesión desde la cookie segura
        $_SESSION['CI'] = $_COOKIE['usuario_recuerdame'];
    }
    header("Location: Pagina_Principal.php");
    exit();
}

// Verificar que exista la cédula temporal de la etapa anterior
if (!isset($_SESSION['cedula_temp'])) {
    header("Location: index.php");
    exit();
}

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
        
        <!-- Opción Recuérdame -->
        <div class="form-check mt-2 mb-2" style="display: flex; align-items: center; gap: 8px;">
          <input type="checkbox" id="recuerdame" name="recuerdame" value="1">
          <label for="recuerdame" style="cursor: pointer;">Recuérdame en este dispositivo</label>
        </div>

        <button type="submit" class="btn btn-primary" id="iniciar">Iniciar Sesión</button>    

        <!-- Mensajes de alerta -->
        <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
          <div class="alert alert-danger mt-2" role="alert">Contraseña inválida. Intente nuevamente.</div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == "empty"): ?>
          <div class="alert alert-danger mt-2" role="alert">El campo de contraseña no puede estar vacío.</div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == "db"): ?>
          <div class="alert alert-danger mt-2" role="alert">Error de conexión con el servidor. Intente más tarde.</div>
        <?php endif; ?>
          
        <img src="Material_Visual/Logo2.png" alt="Logo CeiLab" class="Logo">
      </form>
  </div>

  <script>
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;

    if (localStorage.getItem('theme') === 'dark') {
      body.classList.add('dark-mode');
      themeToggle.checked = true;
    }

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