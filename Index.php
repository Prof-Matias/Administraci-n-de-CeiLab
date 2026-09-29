<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="Diseño.css">
  <title>Ingreso CeiLab</title>
</head>
<body>
  
  <div class="mb-3">
    <br>
      <form action="Login_Logica/VerificarCedula.php" method="post">
        <label for="Cedula" class="form-label">Cedula</label>
        <input type="text" class="form-control" id="Cedula" placeholder="Ingrese su Cedula" name="Cedula">
        <button type="submit" class="btn btn-primary" id="Verificar">Verificar Cedula</button>     
           <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
      <div class="alert alert-danger mt-2" role="alert">
        Cedula invalida
      </div>
    <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == "empty"): ?>
      <div class="alert alert-danger mt-2" role="alert">
        Campo vacio
      </div>
    <?php endif; ?>
  </form>
  </div>
  <img src="Material_Visual/Logo2.png" alt="" class="Logo">
</body>
</html>