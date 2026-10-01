<?php 
require_once "../Config/Conexion.php";
    require_once "Verificar_Usuario.php";


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
    <style>
    /* Estilos CSS para las Ventanas Emergentes (Modals) */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }
    .modal-overlay.active {
        display: flex;
    }
    .modal-content {
        background: #ffffff;
        padding: 24px;
        border-radius: 8px;
        width: 95vw;
        max-width: 1600px;
        max-height: 90vh;      /* El modal ocupará hasta el 90% de la pantalla */
        overflow-y: auto;      /* Muestra la barra vertical para bajar siempre que sea necesario */
        overflow-x: hidden;    /* Bloquea completamente el desplazamiento horizontal */
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        position: relative;
        box-sizing: border-box;
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e0e0e0;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }
    .tabla-modal {
        width: 100%;
        table-layout: fixed;   /* Mantiene el ancho ajustado al 100% de la pantalla */
        border-collapse: collapse;
        margin-top: 15px;
    }
    .tabla-modal th, .tabla-modal td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
        overflow-wrap: anywhere; /* Hace salto de línea en correos o textos largos */
        word-break: break-word;
    }
    .tabla-modal th {
        background-color: #f4f4f4;
        position: sticky;
        top: -24px;            /* Mantiene la cabecera fija arriba al bajar */
        z-index: 2;
    }
    .form-group {
        margin-bottom: 12px;
    }
    .form-group label {
        display: block;
        margin-bottom: 4px;
        font-weight: bold;
    }
    .form-group input, .form-group select {
        width: 100%;
        padding: 8px;
        box-sizing: border-box;
    }
    .btn-modal {
        padding: 8px 14px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 5px;
        font-weight: bold;
    }
    .btn-crear { background-color: #28a745; color: white; }
    .btn-editar { background-color: #ffc107; color: black; }
    .btn-eliminar { background-color: #dc3545; color: white; }
    .btn-cancelar { background-color: #6c757d; color: white; }
</style>
</head>
<body>

    <!-- Barra de navegación -->
    <header class="Barra">
        <a href="Pagina_Principal.php"><img src="../Material_Visual/Logo2.png" alt="Logo CeiLab"></a>
        <div class="dropdown">
            <button class="dropbtn" id="menuBtn">Mi Perfil</button>
            <div class="dropdown-content" id="menuContent">
                <a href="">Ver Perfil</a>
                <a href="../Config/Logout.php" class="logout-link">Cerrar Sesión</a>
            </div>
        </div>
    </header>
    
    <div>
        <h1><?php echo htmlspecialchars($_SESSION['Nombre']); ?></h1>
        <h2>Bienvenido al sistema de prestaciones del CeiLab del Cerp del Este</h2>
    </div>

    <!-- Botones según Rol -->
    <?php if ($_SESSION['Rol'] == "ADMINISTRADOR"): ?>
        <input type="button" id="Usuarios" value="Gestionar Usuarios" onclick="abrirModalGestionUsuarios()">
        <input type="button" id="Productos" value="Gestionar Productos">
        <input type="button" id="Prestamo" value="Gestionar Préstamos">
        <input type="button" id="Reservas" value="Gestionar Reservas">
        <input type="button" id="Solicitud" value="Ver Solicitudes">
        <input type="button" id="Historial" value="Ver historial de solicitudes">
    <?php elseif ($_SESSION['Rol'] == "CLIENTE"): ?>
        <input type="button" id="SolicitarPres" value="Solicitar Préstamo">
        <input type="button" id="SolicitarRes" value="Solicitar Reservas">
    <?php endif; ?>

    <!-- MODAL 1: Lista de Usuarios -->
    <div class="modal-overlay" id="modalGestionUsuarios">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Gestión de Usuarios</h3>
                <button class="modal-close" onclick="cerrarModal('modalGestionUsuarios')">&times;</button>
            </div>
            <button class="btn-modal btn-crear" onclick="abrirModalFormulario()">+ Nuevo Usuario</button>
            <table class="tabla-modal">
                <thead>
                    <tr>
                        <th>Cédula</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Especialidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="bodyTablaUsuarios">
                    <!-- Filas cargadas dinámicamente con JS -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL 2: Formulario Crear / Editar Usuario -->
    <div class="modal-overlay" id="modalFormularioUsuario">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 id="tituloFormUsuario">Usuario</h3>
                <button class="modal-close" onclick="cerrarModal('modalFormularioUsuario')">&times;</button>
            </div>
            <form id="formUsuario" onsubmit="solicitarConfirmacion(event)">
                <input type="hidden" id="contrasena_actual" name="contrasena_actual">
                
                <div class="form-group">
                    <label>Cédula (CI)</label>
                    <input type="text" id="cedula" name="cedula" inputmode="numeric" maxlength="8" placeholder="Solo números (Ej: 12345678)" required>
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
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" id="contrasena" name="contrasena">
                    <small id="helpPass" style="display:none; color: #666;">Dejar en blanco para conservar la actual.</small>
                </div>
                <div class="form-group">
                    <label>Rol</label>
                    <select name="rol" id="rol" required>
                        <option value="ADMINISTRADOR">Administrador</option>
                        <option value="CLIENTE">Cliente</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Especialidad</label>
                    <input type="text" id="especialidad" name="especialidad" required>
                </div>
                
                <button type="submit" class="btn-modal btn-crear" style="width: 100%; margin-top: 10px;">Guardar</button>
            </form>
        </div>
    </div>

    <!-- MODAL 3: Ventana de Confirmación (¿Estás seguro?) -->
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

    <script>
        const RUTA_CONTROLADOR = '../CRUD_Usuarios/Controlador/Controlador_Usuarios.php';
        let esEdicion = false;

        // Menú desplegable Perfil
        const menuBtn = document.getElementById('menuBtn');
        const menuContent = document.getElementById('menuContent');
        if (menuBtn) {
            menuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                menuContent.classList.toggle('show');
            });
            document.addEventListener('click', (e) => {
                if (!menuContent.contains(e.target) && !menuBtn.contains(e.target)) {
                    menuContent.classList.remove('show');
                }
            });
        }

        // Restricción para que la Cédula solo acepte números
        document.addEventListener('DOMContentLoaded', () => {
            const cedulaInput = document.getElementById('cedula');
            if (cedulaInput) {
                cedulaInput.addEventListener('input', (e) => {
                    e.target.value = e.target.value.replace(/[^0-9]/g, '');
                });
            }
        });

        // Funciones auxiliares para Modales
        function cerrarModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        async function abrirModalGestionUsuarios() {
            document.getElementById('modalGestionUsuarios').classList.add('active');
            await cargarUsuarios();
        }

        async function cargarUsuarios() {
            try {
                const res = await fetch(`${RUTA_CONTROLADOR}?action=listar`);
                const usuarios = await res.json();
                
                const tbody = document.getElementById('bodyTablaUsuarios');
                tbody.innerHTML = usuarios.map(u => `
                    <tr>
                        <td>${u.CI}</td>
                        <td>${u.Nombre}</td>
                        <td>${u.Apellido}</td>
                        <td>${u.Email}</td>
                        <td>${u.Rol}</td>
                        <td>${u.Especialidad}</td>
                        <td>
                            <button class="btn-modal btn-editar" onclick="editarUsuario('${u.CI}')">Editar</button>
                            <button class="btn-modal btn-eliminar" onclick="eliminarUsuario('${u.CI}')">Eliminar</button>
                        </td>
                    </tr>
                `).join('');
            } catch (error) {
                console.error("Error al cargar usuarios:", error);
            }
        }

        function abrirModalFormulario() {
            esEdicion = false;
            document.getElementById('formUsuario').reset();
            document.getElementById('cedula').readOnly = false;
            document.getElementById('tituloFormUsuario').innerText = 'Nuevo Usuario';
            document.getElementById('helpPass').style.display = 'none';
            document.getElementById('contrasena').required = true;
            document.getElementById('modalFormularioUsuario').classList.add('active');
        }

        async function editarUsuario(cedula) {
            esEdicion = true;
            try {
                const res = await fetch(`${RUTA_CONTROLADOR}?action=ver&cedula=${cedula}`);
                const u = await res.json();

                document.getElementById('cedula').value = u.CI;
                document.getElementById('cedula').readOnly = true;
                document.getElementById('nombre').value = u.Nombre;
                document.getElementById('apellido').value = u.Apellido;
                document.getElementById('correo').value = u.Email;
                document.getElementById('rol').value = u.Rol;
                document.getElementById('especialidad').value = u.Especialidad;
                document.getElementById('contrasena_actual').value = u.Contraseña;
                
                document.getElementById('contrasena').value = '';
                document.getElementById('contrasena').required = false;
                document.getElementById('helpPass').style.display = 'block';

                document.getElementById('tituloFormUsuario').innerText = 'Editar Usuario';
                document.getElementById('modalFormularioUsuario').classList.add('active');
            } catch (error) {
                console.error("Error al cargar el usuario:", error);
            }
        }

        // Paso 1: Interceptar el submit y mostrar Modal de Confirmación sin limpiar campos
        function solicitarConfirmacion(e) {
            e.preventDefault();
            const form = document.getElementById('formUsuario');
            
            // Validar campos nativos HTML (email válido, requeridos, etc.)
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const mensaje = esEdicion 
                ? '¿Estás seguro de actualizar este usuario?' 
                : '¿Estás seguro de registrar este nuevo usuario?';

            document.getElementById('textoConfirmacion').innerText = mensaje;
            document.getElementById('modalConfirmacionGuardar').classList.add('active');
        }

        // Paso 2: Ejecutar petición al presionar "Sí" en la modal de confirmación
        async function ejecutarGuardado() {
    cerrarModal('modalConfirmacionGuardar');

    const form = document.getElementById('formUsuario');
    const formData = new FormData(form);
    const accion = esEdicion ? 'actualizar' : 'guardar';

    try {
        // Se envía formData directamente para que PHP lo reciba en $_POST
        const res = await fetch(`${RUTA_CONTROLADOR}?action=${accion}`, {
            method: 'POST',
            body: formData
        });

        // Leemos la respuesta como texto para evitar fallos al parsear JSON
        const textoRespuesta = await res.text();
        let data;

        try {
            data = JSON.parse(textoRespuesta);
        } catch (e) {
            console.error("El servidor no devolvió JSON:", textoRespuesta);
            alert("Error del servidor (PHP):\n" + textoRespuesta.substring(0, 300));
            return;
        }

        if (data.success) {
            alert(esEdicion ? '¡Usuario actualizado con éxito!' : '¡Usuario creado con éxito!');
            cerrarModal('modalFormularioUsuario');
            form.reset();
            cargarUsuarios();
        } else {
            alert(data.message || 'Ocurrió un error al procesar la solicitud.');
        }
    } catch (error) {
        console.error("Error de red/petición:", error);
        alert('Error de conexión con el servidor: ' + error.message);
    }
}   
        async function eliminarUsuario(cedula) {
            if (confirm('¿Desea eliminar el usuario seleccionado?')) {
                try {
                    const res = await fetch(`${RUTA_CONTROLADOR}?action=eliminar&cedula=${cedula}`);
                    const data = await res.json();
                    if (data.success) {
                        cargarUsuarios();
                    } else {
                        alert('No se pudo eliminar el usuario.');
                    }
                } catch (error) {
                    console.error("Error al eliminar:", error);
                }
            }
        }
    </script>
</body>
</html> 