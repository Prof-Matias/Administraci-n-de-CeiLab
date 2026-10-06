<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="Diseño.css">
  <title>Ingreso CeiLab - Cédula</title>
</head>
<body>
  
  <div class="mb-3">
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

      <?php if (isset($_GET['error']) && $_GET['error'] == "usuario_eliminado"): ?>
        <div class="alert alert-danger mt-2" role="alert">
          La sesión ha expirado o la cuenta ya no existe.
        </div>
      <?php endif; ?>
    </form>
  </div>

  <img src="Material_Visual/Logo2.png" alt="Logo CeiLab" class="Logo">

  <script>
    // Permitir únicamente caracteres numéricos en el campo
    document.getElementById('cedula').addEventListener('input', function(e) {
      this.value = this.value.replace(/[^0-9]/g, '');
    });
  </script>
</body>
</html>