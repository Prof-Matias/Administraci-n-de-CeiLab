<?php 
//Llamamos al archivo que configura la base de datos
    require_once "../Config/Conexion.php";
    session_start();//iniciamos sesion

    // Verifica si el usuario no esta registrado. 
    // Si no hay un rol en la sesión, el usuario no debería estar aquí
    if (!isset($_SESSION['Rol'])) {
        header("Location: ../Index.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Diseño_Principal.css">
    <title>CeiLab</title>
</head>
<body>
    <!-- barra de navegacion -->
    <header class="Barra">
        <a href="Pagina_Principal.php"><img src="../Material_Visual/Logo2.png" alt=""></a>
        <!-- Menú Desplegable -->
        <div class="dropdown">
            <button class="dropbtn">Mi Perfil</button>
            <div class="dropdown-content">
                <a href="">Ver Perfil</a>
                <a href="ayuda.php">Ayuda</a>
                <!-- Asegúrate de poner la ruta correcta a tu Logout.php -->
                <a href="../Config/Logout.php" class="logout-link">Cerrar Sesión</a>
            </div>
        </div>
    </header>
<div>
<!-- Mensaje de bienvenida -->
    <h1><?php echo $_SESSION['Nombre']; ?></h1>
    <h2>Bienviendo al sistema de prestaciones del CeiLab del Cerp del Este</h2>
</div>
<!-- Botones para admin (al cargar el rol dentro de la pagina podemos usarlo para ocultar y mostrar secciones de la pagina independientemente del rol
 en la linea php se carga una comparacion donde se compara el rol cargado en la sesion del usuario que ingreso a la pagina
 y el rol que deberia tener para poder ver esa seccion-->
<?php if($_SESSION['Rol'] == "ADMINISTRADOR"):?>
<a href=""><input type="button" id="Usuarios" name="Usuarios" value="Gestionar Usuarios"></a>
<a href=""><input type="button" id="Productos" name="Productos" value="Gestionar Productos"></a>
<a href=""><input type="button" id="Prestamo" name="Prestamo" value="Gestionar Préstamos"></a>
<a href=""><input type="button" id="Usuarios" name="Usuarios" value="Gestionar Reservas"></a>
<a href=""><input type="button" id="Solicitud" name="Solicitud" value="Ver Solicitudes"></a>
<a href=""><input type="button" id="Usuarios" name="Usuarios" value="Historial de solicitudes"></a>
<?php elseif($_SESSION['Rol'] == "CLIENTE"): ?>
<a href=""><input type="button" id="SolicitarPres" name="SolicitarPres" value="Solicitar Préstamo"></a>
<a href=""><input type="button" id="SolicitarRes" name="SolicitarRes" value="Solicitar Reservas"></a>
<?php endif; ?>

</body>
</html>
