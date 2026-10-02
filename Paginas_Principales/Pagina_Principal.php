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
    <link rel="stylesheet" href="Diseño_Principal.css">
    <title>CeiLab - Gestión de Usuarios</title>
    
    <style>
    /* ==========================================================================
       ESTILOS CSS: VENTANAS EMERGENTES (MODALS) Y TABLAS
       ========================================================================== */
    
    /* Fondo oscuro para las ventanas modales */
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

    /* Clase activa para desplegar la ventana emergente */
    .modal-overlay.active {
        display: flex;
    }

    /* Contenedor blanco interno del modal */
    .modal-content {
        background: #ffffff;
        padding: 24px;
        border-radius: 8px;
        width: 95vw;
        max-width: 1600px;
        max-height: 90vh;
        overflow-y: auto;
        overflow-x: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        position: relative;
        box-sizing: border-box;
    }

    /* Encabezado del modal */
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e0e0e0;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    /* Botón para cerrar el modal (X) */
    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }

    /* Tabla de la lista de usuarios */
    .tabla-modal {
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .tabla-modal th, .tabla-modal td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .tabla-modal th {
        background-color: #f4f4f4;
        position: sticky;
        top: -24px;
        z-index: 2;
    }

    /* Estilos de los agrupadores de formularios */
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

    /* Botones de acción */
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
    .error-msg { color: #dc3545; font-size: 13px; margin-top: 4px; display: none; }
    </style>
</head>
<body>

    <!-- ==========================================================================
         BARRA DE NAVEGACIÓN PRINCIPAL
         ========================================================================== -->
    <header class="Barra">
        <a href="Pagina_Principal.php"><img src="../Material_Visual/Logo2.png" alt="Logo CeiLab"></a>
        <div class="dropdown">
            <button class="dropbtn" id="menuBtn">Mi Perfil</button>
            <div class="dropdown-content" id="menuContent">
                <a href="">Ver Perfil</a>
                <a href="../Index.php" class="logout-link">Cerrar Sesión</a>
            </div>
        </div>
    </header>
    
    <!-- Mensaje de Bienvenida personalizado -->
    <div>
        <h1><?php echo htmlspecialchars($_SESSION['Nombre']); ?></h1>
        <h2>Bienvenido al sistema de prestaciones del CeiLab del Cerp del Este</h2>
    </div>

    <!-- ==========================================================================
         MENÚ SEGÚN EL ROL DEL USUARIO AUTENTICADO
         ========================================================================== -->
    <?php if ($_SESSION['Rol'] == "ADMINISTRADOR"): ?>
        <!-- Opciones para Administradores -->
        <input type="button" id="Usuarios" value="Gestionar Usuarios" onclick="abrirModalGestionUsuarios()">
        <input type="button" id="Productos" value="Gestionar Productos">
        <input type="button" id="Prestamo" value="Gestionar Préstamos">
        <input type="button" id="Reservas" value="Gestionar Reservas">
        <input type="button" id="Solicitud" value="Ver Solicitudes">
        <input type="button" id="Historial" value="Ver historial de solicitudes">
    <?php elseif ($_SESSION['Rol'] == "CLIENTE"): ?>
        <!-- Opciones para Clientes -->
        <input type="button" id="SolicitarPres" value="Solicitar Préstamo">
        <input type="button" id="SolicitarRes" value="Solicitar Reservas">
    <?php endif; ?>

    <!-- ==========================================================================
         MODAL 1: TABLA DE GESTIÓN DE USUARIOS
         ========================================================================== -->
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
                    <!-- Filas renderizadas dinámicamente mediante JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==========================================================================
         MODAL 2: FORMULARIO DE REGISTRO / EDICIÓN DE USUARIO
         ========================================================================== -->
    <div class="modal-overlay" id="modalFormularioUsuario">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 id="tituloFormUsuario">Usuario</h3>
                <button class="modal-close" onclick="cerrarModal('modalFormularioUsuario')">&times;</button>
            </div>
            
            <form id="formUsuario" onsubmit="solicitarConfirmacion(event)">
                <!-- Campo oculto para conservar la Cédula Original antes de editar -->
                <input type="hidden" id="cedula_original" name="cedula_original">
                
                <!-- Campo oculto para conservar la contraseña actual si no se edita -->
                <input type="hidden" id="contrasena_actual" name="contrasena_actual">
                
                <div class="form-group">
                    <label>Cédula (CI)</label>
                    <input type="text" id="cedula" name="cedula" inputmode="numeric" minlength="8" maxlength="8" placeholder="8 dígitos (Ej: 12345678)" required>
                    <span id="errorCedula" class="error-msg">La Cédula ingresada no es válida.</span>
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

    <!-- ==========================================================================
         MODAL 3: CONFIRMACIÓN DE OPERACIÓN
         ========================================================================== -->
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

    <!-- ==========================================================================
         LÓGICA JAVASCRIPT / CLIENTE
         ========================================================================== -->
    <script>
        // Ruta del controlador PHP encargado de procesar las solicitudes de usuarios
        const RUTA_CONTROLADOR = '../CRUD_Usuarios/Controlador/Controlador_Usuarios.php';
        let esEdicion = false;

        /**
         * Algoritmo del Módulo 10 para validar el Dígito Verificador de la Cédula Uruguaya (CI).
         * @param {string} ci - Cédula a validar.
         * @returns {boolean} - Devuelve true si la Cédula es válida.
         */
        function validarCedulaUruguayaJS(ci) {
            ci = ci.replace(/[^0-9]/g, '');
            if (ci.length !== 8) return false;

            const factores = [2, 9, 8, 7, 6, 3, 4];
            let suma = 0;

            for (let i = 0; i < 7; i++) {
                suma += parseInt(ci[i]) * factores[i];
            }

            const digitoEsperado = (10 - (suma % 10)) % 10;
            const digitoReal = parseInt(ci[7]);

            return digitoEsperado === digitoReal;
        }

        // Configuración del menú desplegable de perfil
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

        // Restricción de entrada: Permite solo números en el campo de Cédula
        document.addEventListener('DOMContentLoaded', () => {
            const cedulaInput = document.getElementById('cedula');
            const errorCedula = document.getElementById('errorCedula');

            if (cedulaInput) {
                cedulaInput.addEventListener('input', (e) => {
                    e.target.value = e.target.value.replace(/[^0-9]/g, '');
                    if (errorCedula) errorCedula.style.display = 'none';
                });
            }
        });

        /**
         * Oculta una ventana modal por su ID.
         * @param {string} id - ID del elemento modal.
         */
        function cerrarModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        /**
         * Abre la modal principal de usuarios y carga la lista actualizada.
         */
        async function abrirModalGestionUsuarios() {
            document.getElementById('modalGestionUsuarios').classList.add('active');
            await cargarUsuarios();
        }

        /**
         * Consulta mediante fetch la lista de usuarios al controlador PHP y renderiza la tabla.
         */
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

        /**
         * Limpia y prepara el formulario para registrar un nuevo usuario.
         */
        function abrirModalFormulario() {
            esEdicion = false;
            document.getElementById('formUsuario').reset();
            document.getElementById('cedula_original').value = '';
            document.getElementById('cedula').readOnly = false;
            document.getElementById('errorCedula').style.display = 'none';
            document.getElementById('tituloFormUsuario').innerText = 'Nuevo Usuario';
            document.getElementById('helpPass').style.display = 'none';
            document.getElementById('contrasena').required = true;
            document.getElementById('modalFormularioUsuario').classList.add('active');
        }

        /**
         * Obtiene los datos del usuario seleccionado por Cédula y los precarga en el formulario para su edición.
         * @param {string} cedula - Cédula del usuario a editar.
         */
        async function editarUsuario(cedula) {
            esEdicion = true;
            try {
                const res = await fetch(`${RUTA_CONTROLADOR}?action=ver&cedula=${cedula}`);
                const u = await res.json();

                // Se guarda la Cédula Original en un campo oculto
                document.getElementById('cedula_original').value = u.CI;
                document.getElementById('cedula').value = u.CI;
                document.getElementById('cedula').readOnly = false; // Se permite modificar la cédula si fuera necesario
                document.getElementById('errorCedula').style.display = 'none';
                
                document.getElementById('nombre').value = u.Nombre;
                document.getElementById('apellido').value = u.Apellido;
                document.getElementById('correo').value = u.Email;
                document.getElementById('rol').value = u.Rol;
                document.getElementById('especialidad').value = u.Especialidad;
                
                // Se guarda el hash de contraseña actual para no perderlo si no se modifica
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

        /**
         * Intercepta el envío del formulario, valida la Cédula y solicita confirmación al usuario.
         * @param {Event} e - Evento de submit.
         */
        function solicitarConfirmacion(e) {
            e.preventDefault();
            const form = document.getElementById('formUsuario');
            const cedulaVal = document.getElementById('cedula').value;
            const errorCedula = document.getElementById('errorCedula');

            // Validar requerimientos nativos de HTML
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Validar Cédula mediante el algoritmo de Módulo 10
            if (!validarCedulaUruguayaJS(cedulaVal)) {
                errorCedula.style.display = 'block';
                document.getElementById('cedula').focus();
                return;
            } else {
                errorCedula.style.display = 'none';
            }

            const mensaje = esEdicion 
                ? '¿Estás seguro de actualizar este usuario?' 
                : '¿Estás seguro de registrar este nuevo usuario?';

            document.getElementById('textoConfirmacion').innerText = mensaje;
            document.getElementById('modalConfirmacionGuardar').classList.add('active');
        }

        /**
         * Envía la información cargada en el formulario al controlador PHP vía POST.
         */
        async function ejecutarGuardado() {
            cerrarModal('modalConfirmacionGuardar');

            const form = document.getElementById('formUsuario');
            const formData = new FormData(form);
            const accion = esEdicion ? 'actualizar' : 'guardar';

            try {
                const res = await fetch(`${RUTA_CONTROLADOR}?action=${accion}`, {
                    method: 'POST',
                    body: formData
                });

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

        /**
         * Solicita confirmación y envía petición de eliminación por Cédula al controlador.
         * @param {string} cedula - Cédula del usuario a eliminar.
         */
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