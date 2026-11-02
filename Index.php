<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="Diseño.css">
  <title>Ingreso CeiLab - Cédula</title>
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
    <br>
    <form action="Login_Logica/verificar_cedula.php" method="post">
      <label for="cedula" class="form-label">Cédula de Identidad</label>
      <input type="text" 
             class="form-control" 
             id="cedula" 
             name="cedula" 
             placeholder="Ingrese su cédula (8 dígitos)" 
             inputmode="numeric" 
             maxlength="8" 
             required 
             autofocus>
             
      <button type="submit" class="btn btn-primary" id="Verificar">Verificar Cédula</button>    

      <!-- Mensajes de alerta según parámetros de respuesta -->
      <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
        <div class="alert alert-danger mt-2" role="alert">
          Cédula de identidad no registrada o inválida.
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['error']) && $_GET['error'] == "empty"): ?>
        <div class="alert alert-danger mt-2" role="alert">
          El campo de cédula no puede estar vacío.
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['error']) && $_GET['error'] == "desactivada"): ?>
        <div class="alert alert-danger mt-2" role="alert">
          La sesión ha expirado o la cuenta ya no existe.
        </div>
      <?php endif; ?>
      
      <img src="Material_Visual/Logo2.png" alt="Logo CeiLab" class="Logo">
      
    </form>
    
  </div>

  <script>
    // Permitir únicamente caracteres numéricos en el campo
    document.getElementById('cedula').addEventListener('input', function(e) {
      this.value = this.value.replace(/[^0-9]/g, '');
    });

    // SISTEMA DE MODO OSCURO (SWITCH ANIMADO)
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body;

    // Comprueba el modo guardado en el navegador del usuario
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