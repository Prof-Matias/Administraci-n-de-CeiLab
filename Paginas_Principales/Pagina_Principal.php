<?php 
/**
 * Pagina_Principal.php
 * 
 * Vista principal de la aplicación CeiLab.
 * Controla el acceso por roles, despliega las herramientas administrativas y de cliente,
 * y gestiona las ventanas modales mediante JavaScript (Fetch API).
 */

// Inclusión de la configuración de conexión y la verificación de sesión activa
require_once "../Config/Conexion.php";
require_once "Verificar_Usuario.php";

// Control de seguridad: Si no hay un Rol definido en la sesión, redirige al Login
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
    <link rel="stylesheet" href="Diseño_Principal.css?v=4">
    <link rel="stylesheet" href="Diseño2.css?v=3">
    <link rel="stylesheet" href="animaciones.css">
    <title>CeiLab - Gestión Principal</title>
</head>
<body>

    <!-- MARCO CONTENEDOR PRINCIPAL DE LA MAQUETA -->
    <div class="app-container">
        
        <!-- BARRA DE NAVEGACIÓN SUPERIOR -->
        <header class="Barra">
            <a href="Pagina_Principal.php" class="brand-logo">
                <img src="../Material_Visual/itrio.png" alt="Logo ITRIO" class="logo-itrio">
                <span class="brand-text">CEILAB</span>
            </a>

            <div class="nav-actions">
                <!-- Switch animado de modo oscuro -->
                <label class="theme-switch" for="theme-toggle" title="Alternar Modo Oscuro">
                    <input type="checkbox" id="theme-toggle" onchange="toggleTheme()">
                    <span class="slider">
                        <span class="slider-thumb">
                            <span class="icon sun">☀️</span>
                            <span class="icon moon">🌙</span>
                        </span>
                    </span>
                </label>

                <!-- MENÚ DESPLEGABLE DE PERFIL CON FUNCIONALIDAD DIRECTA -->
                <div class="dropdown">
                    <button class="dropbtn" id="menuBtn" type="button" onclick="toggleDropdown(event)">
                        <?php echo htmlspecialchars($_SESSION['Nombre']); ?>
                    </button>
                    <div class="dropdown-content" id="menuContent">
                        <a href="#" onclick="abrirModalVerPerfil(event)">Ver Perfil</a>
                        <a href="../Index.php" class="logout-link">Cerrar Sesión</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- SECCIÓN DE ENCABEZADO CON LA IMAGEN Y TEXTO -->
        <section class="hero-section">
            <div class="hero-overlay">
                <h1>Bienvenida/o al<br>Sistema de gestión</h1>
                <p class="hero-subtitle">Impulsado por Itrio Team</p>
            </div>
        </section>

        <!-- SECCIÓN DE ACCIONES (PRÉSTAMOS Y RESERVAS) -->
        <section class="actions-section">
            <h3>Préstamos y Reservas</h3>

            <div class="buttons-grid">
                <?php if ($_SESSION['Rol'] == "ADMINISTRADOR"): ?>
                    <!-- Opciones para Administradores -->
                    <button type="button" class="action-card" id="Usuarios" onclick="abrirModalGestionUsuarios()">
                        <span class="card-title">Gestionar Usuarios</span>
                        <span class="card-desc">Administra cuentas, roles y accesos.</span>
                    </button>
                    <button type="button" class="action-card" id="Productos" onclick="abrirModalGestionProductos()">
                        <span class="card-title">Gestionar Productos</span>
                        <span class="card-desc">Carga, edita o quita equipos del inventario.</span>
                    </button>
                    <button type="button" class="action-card" id="Prestamo" onclick="abrirModalGestionPrestamos('')">
                        <span class="card-title">Gestionar Préstamos</span>
                        <span class="card-desc">Controla las entregas y devoluciones.</span>
                    </button>
                    <button type="button" class="action-card" id="Reservas">
                        <span class="card-title">Gestionar Reservas</span>
                        <span class="card-desc">Revisa el calendario de reservas semanales.</span>
                    </button>
                    <button type="button" class="action-card" id="Solicitud" onclick="abrirModalGestionPrestamos('PENDIENTE')">
                        <span class="card-title">Ver Solicitudes</span>
                        <span class="card-desc">Atiende las peticiones pendientes.</span>
                    </button>
                    <button type="button" class="action-card" id="Historial" onclick="abrirHistorialGeneral()">
                        <span class="card-title">Ver Historial</span>
                        <span class="card-desc">Consulta el registro general de movimientos.</span>
                    </button>

                <?php elseif ($_SESSION['Rol'] == "CLIENTE"): ?>
                    <!-- Opciones para Clientes -->
                    <button type="button" class="action-card" id="SolicitarPres" onclick="abrirModalSolicitarPrestamo()">
                        <span class="card-title">Solicitar Préstamo</span>
                        <span class="card-desc">Crea una solicitud para pedir equipos prestados del ceilab.</span>
                    </button>
                    <button type="button" class="action-card" id="SolicitarRes">
                        <span class="card-title">Hacer Reserva</span>
                        <span class="card-desc">Crea una solicitud para reservar equipos en la semana.</span>
                    </button>
                    <button type="button" class="action-card" onclick="abrirModalSolicitarPrestamo()">
                        <span class="card-title">Consultar Catálogo</span>
                        <span class="card-desc">Consulta el catálogo para saber qué días están disponibles u ocupados los equipos.</span>
                    </button>
                    <!-- NUEVA TARJETA ESTILIZADA PARA CLIENTE -->
                    <button type="button" class="action-card" id="MisPrestamos" onclick="abrirModalMisPrestamos()">
                        <span class="card-title">Mis Préstamos</span>
                        <span class="card-desc">Revisa el historial y estado de tus solicitudes activas o pasadas.</span>
                    </button>
                <?php endif; ?>
            </div>
        </section>

        <!-- FOOTER INFERIOR DE LA MAQUETA -->
        <footer class="app-footer">
            <div class="footer-contact">
                <a href="https://cerpdeleste.cfe.edu.uy/"><p>🌐Página del Cerp del Este</p></a>
                <p>📞Teléfonos: 4225 4422 / 4225 4430 / 4225 4431</p>
                <p>✉️ cerpdeleste.cfe@gmail.com o cerpdeleste@cfe.edu.uy.</p>
            </div>
            <div class="footer-brand">
                <a href="https://cerpdeleste.cfe.edu.uy/"><img src="../Material_Visual/Logo2.png" alt="Logo CeRP del Este" class="footer-logo"></a>
            </div>
        </footer>

    </div>

    <!-- ==========================================================================
         VENTANAS MODALES (SISTEMA INTERNO)
         ========================================================================== -->
    <div class="modal-overlay" id="modalGestionUsuarios">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Gestión de Usuarios</h3>
                <button class="modal-close" onclick="cerrarModal('modalGestionUsuarios')">&times;</button>
            </div>
            <button class="btn-modal btn-crear" onclick="abrirModalFormulario()">+ Nuevo Usuario</button>
            <div class="table-responsive">
                <table class="tabla-modal">
                    <thead>
                        <tr>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Email</th>
                            <th>Teléfono(s)</th>
                            <th>Rol</th>
                            <th>Especialidad</th>
                            <th style="width: 280px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="bodyTablaUsuarios"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalFormularioUsuario">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 id="tituloFormUsuario">Usuario</h3>
                <button class="modal-close" onclick="cerrarModal('modalFormularioUsuario')">&times;</button>
            </div>
            <form id="formUsuario" onsubmit="solicitarConfirmacion(event)">
                <input type="hidden" id="cedula_original" name="cedula_original">
                <input type="hidden" id="contrasena_actual" name="contrasena_actual">
                <div class="form-group">
                    <label>Cédula (CI)</label>
                    <input type="text" id="cedula" name="cedula" inputmode="numeric" minlength="8" maxlength="8" placeholder="8 dígitos" required>
                    <span id="errorCedula" class="error-msg" style="display:none; color: #ef4444; font-size: 12px;">La Cédula ingresada no es válida.</span>
                </div>
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label>Apellido</label>
                    <input type="text" id="apellido" name="apellido" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="correo" name="correo" required>
                </div>
                <div class="form-group" id="contenedor-telefonos">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                        <label id="labelTelefonos" style="margin: 0;">Teléfono(s) *</label>
                        <button type="button" class="btn-modal btn-editar" style="padding: 3px 8px; font-size: 12px;" onclick="agregarCampoTelefono()">+ Agregar Teléfono</button>
                    </div>
                    <div id="lista-telefonos"></div>
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" id="contrasena" name="contrasena">
                    <small id="helpPass" style="display:none; color: #666;">Dejar en blanco para conservar la actual.</small>
                </div>
                <div class="form-group">
                    <label>Rol</label>
                    <select name="rol" id="rol" required>
                        <option value="CLIENTE">Cliente</option>
                        <option value="ADMINISTRADOR">Administrador</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Especialidad</label>
                    <input type="text" id="especialidad" name="especialidad">
                </div>
                <button type="submit" class="btn-modal btn-crear" style="width: 100%; margin-top: 10px;">Guardar Usuario</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalConfirmacionGuardar" style="z-index: 1100;">
        <div class="modal-content" style="max-width: 380px; text-align: center;">
            <h3 style="margin-top: 0;">Confirmación</h3>
            <p id="textoConfirmacion" style="margin: 20px 0; font-size: 16px;">¿Estás seguro de guardar los cambios?</p>
            <div style="display: flex; justify-content: space-evenly;">
                <button type="button" class="btn-modal btn-crear" style="width: 40%;" onclick="ejecutarGuardado()">Sí</button>
                <button type="button" class="btn-modal btn-cancelar" style="width: 40%;" onclick="cerrarModal('modalConfirmacionGuardar')">No</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalGestionProductos">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Gestión de Productos / Materiales</h3>
                <button class="modal-close" onclick="cerrarModal('modalGestionProductos')">&times;</button>
            </div>
            <button class="btn-modal btn-crear" onclick="abrirModalFormularioProducto()">+ Nuevo Producto</button>
            <div class="table-responsive">
                <table class="tabla-modal">
                    <thead>
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th style="width: 50px;">Serial</th>
                            <th style="width: 70px;">Imagen</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th style="width: 70px;">Total</th>
                            <th style="width: 70px;">Disp.</th>
                            <th>Estado</th>
                            <th style="width: 160px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="bodyTablaProductos"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalFormularioProducto">
        <div class="modal-content" style="max-width: 550px;">
            <div class="modal-header">
                <h3 id="tituloFormProducto">Nuevo Producto</h3>
                <button class="modal-close" onclick="cerrarModal('modalFormularioProducto')">&times;</button>
            </div>
            <form id="formProducto" enctype="multipart/form-data" onsubmit="solicitarConfirmacionProducto(event)">
                <input type="hidden" id="id_material" name="id_material">
                <input type="hidden" id="foto_actual" name="foto_actual">
                <div class="form-group">
                    <label>Tipo de control</label>
                    <select id="prod_tipo" name="tipo_control" onchange="ajustarFormularioProducto()" required>
                        <option value="SERIE">Por número de serie (un registro por equipo)</option>
                        <option value="CANTIDAD">Por cantidad (consumibles sin serie)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nombre del Producto / Material</label>
                    <input type="text" id="prod_nombre" name="nombre" required>
                    <small>Los equipos del mismo tipo comparten el nombre (por ejemplo, «Ceibalita»).</small>
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea id="prod_descripcion" name="descripcion" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Categoría</label>
                    <input type="text" id="prod_categoria" name="categoria" required>
                </div>
                <div class="form-group" id="grupoSeries">
                    <label>Números de serie (uno por línea)</label>
                    <textarea id="prod_series" name="series" rows="4" placeholder="CEIB-0011&#10;CEIB-0012"></textarea>
                </div>
                <div class="form-group" id="grupoSerie" style="display:none;">
                    <label>Número de serie</label>
                    <input type="text" id="prod_id_serie" name="id_serie">
                </div>
                <div class="form-group" id="grupoCantidad" style="display:none;">
                    <label>Cantidad total</label>
                    <input type="number" id="prod_cant_total" name="cantidad_total" min="0">
                    <small>El disponible se calcula: total menos lo que está prestado.</small>
                </div>
                <div class="form-group" id="grupoAplicar" style="display:none;">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="prod_aplicar" name="aplicar_al_grupo" value="1" checked style="width:auto; margin:0;"> 
                        Aplicar nombre, descripción, categoría y foto a todas las unidades
                    </label>
                </div>
                <div class="form-group">
                    <label>Imagen del Producto (JPG, PNG, GIF o WEBP)</label>
                    <input type="file" id="prod_foto" name="foto_material" accept="image/*">
                </div>
                <button type="submit" class="btn-modal btn-crear" style="width: 100%; margin-top: 10px;">Guardar Producto</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modalConfirmacionGuardarProducto" style="z-index: 1100;">
        <div class="modal-content" style="max-width: 380px; text-align: center;">
            <h3 style="margin-top: 0;">Confirmación</h3>
            <p id="textoConfirmacionProducto" style="margin: 20px 0; font-size: 16px;">¿Estás seguro de guardar este producto?</p>
            <div style="display: flex; justify-content: space-evenly;">
                <button type="button" class="btn-modal btn-crear" style="width: 40%;" onclick="ejecutarGuardadoProducto()">Sí</button>
                <button type="button" class="btn-modal btn-cancelar" style="width: 40%;" onclick="cerrarModal('modalConfirmacionGuardarProducto')">No</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalHistorialProducto" style="z-index: 1200;">
        <div class="modal-content" style="max-width: 750px;">
            <div class="modal-header">
                <h3 id="tituloModalHistorial">Historial de Estados</h3>
                <button class="modal-close" onclick="cerrarModal('modalHistorialProducto')">&times;</button>
            </div>
            <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                <table class="tabla-modal">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Estado Anterior</th>
                            <th>Estado Nuevo</th>
                            <th>Evento</th>
                            <th>Resp. (CI)</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody id="bodyTablaHistorial"></tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- VENTANA MODAL: VER PERFIL DE USUARIO -->
    <div class="modal-overlay" id="modalVerPerfil">
        <div class="modal-content perfil-card-modal">
            <div class="modal-header">
                <h3>Perfil de Usuario</h3>
                <button type="button" class="modal-close" onclick="cerrarModal('modalVerPerfil')">&times;</button>
            </div>
            
            <div class="perfil-card-body">
                <!-- Encabezado con Avatar -->
                <div class="perfil-avatar-section">
                    <div class="avatar-circle">
                        <?php echo strtoupper(substr($_SESSION['Nombre'], 0, 1)); ?>
                    </div>
                    <h4><?php echo htmlspecialchars($_SESSION['Nombre'] . ' ' . ($_SESSION['Apellido'] ?? '')); ?></h4>
                    <span class="badge-rol"><?php echo htmlspecialchars($_SESSION['Rol']); ?></span>
                </div>

                <!-- Lista de Datos en Filas Separadas -->
                <div class="perfil-details-list">
                    <div class="perfil-item">
                        <span class="item-label">Cédula de Identidad</span>
                        <span class="item-value"><?php echo htmlspecialchars($_SESSION['Cedula'] ?? $_SESSION['CI'] ?? '10000001'); ?></span>
                    </div>

                    <div class="perfil-item">
                        <span class="item-label">Correo Electrónico</span>
                        <span class="item-value"><?php echo htmlspecialchars($_SESSION['Correo'] ?? $_SESSION['Email'] ?? 'lucia.fernandez@cerpdeleste.edu.uy'); ?></span>
                    </div>

                    <div class="perfil-item">
                        <span class="item-label">Especialidad / Cargo</span>
                        <span class="item-value"><?php echo htmlspecialchars($_SESSION['Especialidad'] ?? 'Sin especificar'); ?></span>
                    </div>
                </div>

                <!-- Acciones -->
                <button type="button" class="btn-editar-perfil-main" onclick="abrirEditarPerfilPropio()">
                    Editar mis Datos
                </button>
            </div>
        </div>
    </div>


    <!-- ==========================================================================
         MODAL: CATÁLOGO DE PRODUCTOS (VISTA CLIENTE)
         ========================================================================== -->
    <div class="modal-overlay" id="modalSolicitarPrestamo">
        <div class="modal-content" style="max-width: 1200px;">
            <div class="modal-header">
                <h3>Catálogo de Materiales - Solicitar Préstamo</h3>
                <button class="modal-close" onclick="cerrarModal('modalSolicitarPrestamo')">&times;</button>
            </div>
            
            <div id="avisoBloqueoPrestamo" class="aviso-bloqueo-prestamo"></div>

            <div id="contenedor-productos-cliente" class="grid-productos">
                <!-- Las tarjetas de productos se renderizan dinámicamente aquí -->
            </div>

            <!-- v7: el cliente arma un pedido por tipo de material; el administrador asigna las series al entregar -->
            <div class="carrito">
                <h4>Tu pedido</h4>
                <div id="carritoPrestamo"><p><i>Todavía no agregaste materiales.</i></p></div>
            </div>
        </div>
    </div>

<!-- ==========================================================================
    CARGA DE SCRIPTS Y CONFIGURACIÓN GLOBAL
    ========================================================================== -->
<script>
    window.USUARIO_ACTUAL = {
        cedula: "<?php echo $_SESSION['Cedula'] ?? $_SESSION['CI'] ?? ''; ?>",
        nombre: "<?php echo $_SESSION['Nombre'] ?? ''; ?>",
        apellido: "<?php echo $_SESSION['Apellido'] ?? ''; ?>",
        correo: "<?php echo $_SESSION['Correo'] ?? $_SESSION['Email'] ?? ''; ?>",
        especialidad: "<?php echo $_SESSION['Especialidad'] ?? ''; ?>",
        rol: "<?php echo $_SESSION['Rol'] ?? ''; ?>"
    };
</script>

<!-- 1. Primero cargamos el Script general de la página donde viven toggleDropdown y funciones base -->
<script src="Script.js?v=6"></script>

<!-- 2. Luego incluimos la vista de préstamos (que define abrirModalSolicitarPrestamo y los fetch) UNA SOLA VEZ -->
<?php include __DIR__ . '/../CRUD_Prestamos/Vista/Vista_Prestamos.php'; ?>

</body>
</html>