<?php ?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="CSS/Desing.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <title>Ingreso CeiLab</title>
</head>
<body>
  <div class="mb-3">
      <form action="Login_Logica/VerificarCedula.php" method="post">
        <label for="Cedula" class="form-label">Cedula</label>
        <input type="text" class="form-control" id="Cedula" placeholder="Ingrese su Cedula" name="Cedula">
        <button type="submit" class="btn btn-primary" id="Verificar">Verificar Cedula</button>     
           <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
      <div class="alert alert-danger mt-2" role="alert">
        Cedula invalida
      </div>
    <?php endif; ?>
  </form>
  </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>