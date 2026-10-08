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

// Captura de variables de sesión normalizadas (compatible minúsculas/mayúsculas)
$rolUsuario    = $_SESSION['rol']    ?? $_SESSION['Rol']    ?? null;
$nombreUsuario = $_SESSION['nombre'] ?? $_SESSION['Nombre'] ?? 'Usuario';

// Control de seguridad: Si no hay un Rol definido en la sesión, redirige al Login
if (!$rolUsuario) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Diseño_Principal.css">
    <title>CeiLab - Gestión Principal</title>
    
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

    /* ==========================================================================
       ESTILOS CSS: TABLAS RESPONSIVE
       ========================================================================== */
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-top: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .tabla-modal {
        width: 100%;
        min-width: 800px;
        border-collapse: collapse;
    }

    .tabla-modal th, .tabla-modal td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
        vertical-align: middle;
    }

    .tabla-modal th {
        background-color: #f4f4f4;
        position: sticky;
        top: 0;
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

    .form-group input, .form-group select, .form-group textarea {
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

    /* ==========================================================================
       ESTILOS CSS: VISTA DE TARJETAS PARA CLIENTES
       ========================================================================== */
    .grid-productos {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        padding: 10px 0;
    }

    .tarjeta-producto {
        background-color: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .tarjeta-producto:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    }

    .tarjeta-producto img {
        width: 100%;
        height: 180px;
        object-fit: cover;
        border-bottom: 1px solid #e0e0e0;
        background: #f8f9fa;
    }

    .tarjeta-cuerpo {
        padding: 15px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .tarjeta-cuerpo h4 {
        margin: 0 0 10px 0;
        font-size: 1.1rem;
        color: #333;
    }

    .tarjeta-cuerpo p {
        margin: 4px 0;
        color: #555;
        font-size: 0.9rem;
    }

    .btn-prestamo {
        margin-top: 15px;
        background-color: #0d6efd;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        text-align: center;
        transition: background-color 0.3s;
    }

    .btn-prestamo:hover {
        background-color: #0b5ed7;
    }

    .btn-sin-stock {
        background-color: #6c757d;
        cursor: not-allowed;
    }
    .btn-sin-stock:hover {
        background-color: #6c757d;
    }
    </style>
</head>
<body>

    <!-- ==========================================================================
         BARRA DE NAVEGACIÓN PRINCIPAL
         ========================================================================== -->
    <header class="Barra">
        <a href="Pagina_Principal.php"><img src="../Material_Visual/Logo2.png" alt="Logo CeiLab"></a>
        <div class="dropdown">
            <button class="dropbtn" id="menuBtn"><?php echo htmlspecialchars($nombreUsuario); ?></button>
            <div class="dropdown-content" id="menuContent">
                <a href="">Ver Perfil</a>
                <a href="../index.php" class="logout-link">Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <!-- Mensaje de Bienvenida personalizado -->
    <div>
        <h2>Bienvenido al sistema de prestaciones del CeiLab del CERP del Este</h2>
    </div>

    <!-- ==========================================================================
         MENÚ SEGÚN EL ROL DEL USUARIO AUTENTICADO
         ========================================================================== -->
    <?php if (strtoupper(trim($rolUsuario)) === "ADMINISTRADOR"): ?>
        <!-- Opciones para Administradores -->
        <input type="button" id="Usuarios" value="Gestionar Usuarios" onclick="abrirModalGestionUsuarios()">
        <input type="button" id="Productos" value="Gestionar Productos" onclick="abrirModalGestionProductos()">
        <input type="button" id="Prestamo" value="Gestionar Préstamos">
        <input type="button" id="Reservas" value="Gestionar Reservas">
        <input type="button" id="Solicitud" value="Ver Solicitudes">
        <input type="button" id="Historial" value="Ver historial de solicitudes">
    <?php elseif (strtoupper(trim($rolUsuario)) === "CLIENTE"): ?>
        <!-- Opciones para Clientes -->
        <input type="button" id="SolicitarPres" value="Solicitar Préstamo" onclick="abrirModalSolicitarPrestamo()">
        <input type="button" id="SolicitarRes" value="Solicitar Reservas">
    <?php endif; ?>

    <!-- ==========================================================================
         MODAL: CATÁLOGO DE PRODUCTOS (VISTA CLIENTE)
         ========================================================================== -->
    <div class="modal-overlay" id="modalSolicitarPrestamo">
        <div class="modal-content" style="max-width: 1200px;">
            <div class="modal-header">
                <h3>Catálogo de Materiales - Solicitar Préstamo</h3>
                <button class="modal-close" onclick="cerrarModal('modalSolicitarPrestamo')">&times;</button>
            </div>
            
            <div id="contenedor-productos-cliente" class="grid-productos">
                <!-- Las tarjetas de productos se renderizan dinámicamente aquí -->
            </div>
        </div>
    </div>

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
            
            <div class="table-responsive">
                <table class="tabla-modal">
                    <thead>
                        <tr>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Especialidad</th>
                            <th style="width: 160px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="bodyTablaUsuarios">
                        <!-- Filas renderizadas dinámicamente mediante JavaScript -->
                    </tbody>
                </table>
            </div>
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
                <input type="hidden" id="cedula_original" name="cedula_original">
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
         MODAL 3: CONFIRMACIÓN DE OPERACIÓN DE USUARIOS
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
         MODAL 4: TABLA DE GESTIÓN DE PRODUCTOS / MATERIALES
         ========================================================================== -->
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
                    <tbody id="bodyTablaProductos">
                        <!-- Filas renderizadas dinámicamente mediante JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ==========================================================================
         MODAL 5: FORMULARIO DE REGISTRO / EDICIÓN DE PRODUCTO
         ========================================================================== -->
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
                    <label>Nombre del Producto / Material</label>
                    <input type="text" id="prod_nombre" name="nombre" required>
                </div>
                
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea id="prod_descripcion" name="descripcion" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Categoría</label>
                    <input type="text" id="prod_categoria" name="categoria" required>
                </div>
                
                <div class="form-group">
                    <label>Cantidad Total</label>
                    <input type="number" id="prod_cant_total" name="cantidad_total" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Cantidad Disponible</label>
                    <input type="number" id="prod_cant_disp" name="cantidad_disponible" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Estado</label>
                    <select id="prod_estado" name="estado" required>
                        <option value="Disponible">Disponible</option>
                        <option value="En Mantenimiento">En Mantenimiento</option>
                        <option value="Agotado">Agotado</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Imagen del Producto</label>
                    <input type="file" id="prod_foto" name="foto_material" accept="image/*">
                </div>
                
                <button type="submit" class="btn-modal btn-crear" style="width: 100%; margin-top: 10px;">Guardar Producto</button>
            </form>
        </div>
    </div>

    <!-- ==========================================================================
         MODAL 6: CONFIRMACIÓN DE OPERACIÓN DE PRODUCTOS
         ========================================================================== -->
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

    <!-- ==========================================================================
         LÓGICA JAVASCRIPT / CLIENTE
         ========================================================================== -->
    <script>
        // Ruta del controlador PHP encargado de procesar las solicitudes de usuarios
        const RUTA_CONTROLADOR = '../CRUD_Usuarios/Controlador/Controlador_Usuarios.php';
        let esEdicion = false;

        // Ruta del controlador PHP encargado de procesar las solicitudes de productos
        const RUTA_CONTROLADOR_PRODUCTOS = '../CRUD_Productos/Controlador/Controlador_Productos.php';
        let esEdicionProducto = false;

        /**
         * Algoritmo del Módulo 10 para validar el Dígito Verificador de la Cédula Uruguaya (CI).
         */
        function validarCedulaUruguayaJS(ci) {
            ci = ci.replace(/[^0-9]/g, '');
            if (ci.length === 7) ci = '0' + ci;
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
         */
        function cerrarModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        /* ==========================================================================
           MÓDULO VISTA CLIENTE (TARJETAS DE PRÉSTAMO)
           ========================================================================== */
        async function abrirModalSolicitarPrestamo() {
            document.getElementById('modalSolicitarPrestamo').classList.add('active');
            await cargarProductosCliente();
        }

        async function cargarProductosCliente() {
            try {
                const res = await fetch(`${RUTA_CONTROLADOR_PRODUCTOS}?action=listar`);
                const textoRespuesta = await res.text();
                
                let productos;
                try {
                    productos = JSON.parse(textoRespuesta);
                } catch (e) {
                    console.error("Respuesta del servidor no válida:", textoRespuesta);
                    return;
                }

                const contenedor = document.getElementById('contenedor-productos-cliente');
                if (!contenedor) return; 

                if (!Array.isArray(productos) || productos.length === 0) {
                    contenedor.innerHTML = `<p style="text-align:center; width:100%; grid-column: 1 / -1;">No hay productos disponibles en este momento.</p>`;
                    return;
                }

                contenedor.innerHTML = productos.map(p => {
                    // Soporte para minúsculas y mayúsculas en campos JSON
                    const idMat = p.id_material || p.ID_Material;
                    const nomMat = p.nombre || p.Nombre;
                    const catMat = p.categoria || p.Categoria || '-';
                    const descMat = p.descripcion || p.Descripcion || '-';
                    const fotoMat = p.foto_material || p.Foto_Material || '';
                    const estadoMat = p.estado || p.Estado;
                    const cantDisp = parseInt(p.cantidad_disponible ?? p.Cantidad_Disponible) || 0;

                    const fotoLimpia = fotoMat ? fotoMat.replace(/^\/+/, '') : '';
                    const fotoRuta = fotoLimpia ? `../CRUD_Productos/${fotoLimpia}` : '';
                    
                    let btnHtml = '';
                    if (cantDisp > 0 && estadoMat !== 'En Mantenimiento') {
                        btnHtml = `<button onclick="iniciarProcesoPrestamo(${idMat}, '${nomMat}')" class="btn-prestamo">Solicitar Préstamo</button>`;
                    } else {
                        btnHtml = `<button class="btn-prestamo btn-sin-stock" disabled>Agotado / No Disponible</button>`;
                    }

                    return `
                        <div class="tarjeta-producto">
                            <img src="${fotoRuta}" alt="${nomMat}" onerror="this.style.display='none';">
                            <div class="tarjeta-cuerpo">
                                <h4>${nomMat}</h4>
                                <p><strong>Categoría:</strong> ${catMat}</p>
                                <p><strong>Descripción:</strong> ${descMat}</p>
                                <p><strong>Disponibles:</strong> ${cantDisp}</p>
                                ${btnHtml}
                            </div>
                        </div>
                    `;
                }).join('');

            } catch (error) {
                console.error("Error al cargar el catálogo de cliente:", error);
            }
        }

        function iniciarProcesoPrestamo(idProducto, nombreProducto) {
            alert(`Iniciando solicitud para el material: ${nombreProducto} (ID: ${idProducto})\n\n(Funcionalidad en desarrollo)`);
        }

        /* ==========================================================================
           MÓDULO USUARIOS (ADMINISTRADOR)
           ========================================================================== */
        async function abrirModalGestionUsuarios() {
            document.getElementById('modalGestionUsuarios').classList.add('active');
            await cargarUsuarios();
        }

        async function cargarUsuarios() {
            try {
                const res = await fetch(`${RUTA_CONTROLADOR}?action=listar`);
                const usuarios = await res.json();
                
                const tbody = document.getElementById('bodyTablaUsuarios');
                tbody.innerHTML = usuarios.map(u => {
                    const ci = u.ci || u.CI;
                    const nombre = u.nombre || u.Nombre;
                    const apellido = u.apellido || u.Apellido;
                    const email = u.email || u.Email;
                    const rol = u.rol || u.Rol;
                    const especialidad = u.especialidad || u.Especialidad || '-';

                    return `
                        <tr>
                            <td>${ci}</td>
                            <td>${nombre}</td>
                            <td>${apellido}</td>
                            <td>${email}</td>
                            <td>${rol}</td>
                            <td>${especialidad}</td>
                            <td style="white-space: nowrap;">
                                <button class="btn-modal btn-editar" onclick="editarUsuario('${ci}')">Editar</button>
                                <button class="btn-modal btn-eliminar" onclick="eliminarUsuario('${ci}')">Eliminar</button>
                            </td>
                        </tr>
                    `;
                }).join('');
            } catch (error) {
                console.error("Error al cargar usuarios:", error);
            }
        }

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

        async function editarUsuario(cedula) {
            esEdicion = true;
            try {
                const res = await fetch(`${RUTA_CONTROLADOR}?action=ver&cedula=${cedula}`);
                const u = await res.json();

                const ciVal = u.ci || u.CI;
                const nombreVal = u.nombre || u.Nombre;
                const apellidoVal = u.apellido || u.Apellido;
                const emailVal = u.email || u.Email;
                const rolVal = u.rol || u.Rol;
                const espVal = u.especialidad || u.Especialidad || '';
                const passVal = u.contrasenia || u.Contraseña || '';

                document.getElementById('cedula_original').value = ciVal;
                document.getElementById('cedula').value = ciVal;
                document.getElementById('cedula').readOnly = false;
                document.getElementById('errorCedula').style.display = 'none';
                
                document.getElementById('nombre').value = nombreVal;
                document.getElementById('apellido').value = apellidoVal;
                document.getElementById('correo').value = emailVal;
                document.getElementById('rol').value = rolVal;
                document.getElementById('especialidad').value = espVal;
                
                document.getElementById('contrasena_actual').value = passVal;
                document.getElementById('contrasena').value = '';
                document.getElementById('contrasena').required = false;
                document.getElementById('helpPass').style.display = 'block';

                document.getElementById('tituloFormUsuario').innerText = 'Editar Usuario';
                document.getElementById('modalFormularioUsuario').classList.add('active');
            } catch (error) {
                console.error("Error al cargar el usuario:", error);
            }
        }

        function solicitarConfirmacion(e) {
            e.preventDefault();
            const form = document.getElementById('formUsuario');
            const cedulaVal = document.getElementById('cedula').value;
            const errorCedula = document.getElementById('errorCedula');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

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

        async function eliminarUsuario(cedula) {
            if (!confirm(`¿Estás seguro de eliminar el usuario con Cédula ${cedula}?`)) return;

            try {
                const res = await fetch(`${RUTA_CONTROLADOR}?action=eliminar&cedula=${cedula}`, {
                    method: 'POST'
                });
                const data = await res.json();

                if (data.success) {
                    alert('Usuario eliminado correctamente.');
                    cargarUsuarios();
                } else {
                    alert(data.message || 'Error al eliminar usuario.');
                }
            } catch (error) {
                console.error("Error al eliminar usuario:", error);
            }
        }
        /* ==========================================================================
           MÓDULO PRODUCTOS / MATERIALES (ADMINISTRADOR)
           ========================================================================== */
        async function abrirModalGestionProductos() {
            document.getElementById('modalGestionProductos').classList.add('active');
            await cargarProductos();
        }

        async function cargarProductos() {
            try {
                const res = await fetch(`${RUTA_CONTROLADOR_PRODUCTOS}?action=listar`);
                const textoRespuesta = await res.text();

                let productos;
                try {
                    productos = JSON.parse(textoRespuesta);
                } catch (e) {
                    console.error("Respuesta del servidor no válida:", textoRespuesta);
                    return;
                }

                const tbody = document.getElementById('bodyTablaProductos');
                if (!Array.isArray(productos) || productos.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;">No hay productos registrados.</td></tr>`;
                    return;
                }

                tbody.innerHTML = productos.map(p => {
                    // Normalización de propiedades devueltas por el JSON (minúsculas con fallback a mayúsculas)
                    const id = p.id_material || p.ID_Material;
                    const nombre = p.nombre || p.Nombre;
                    const descripcion = p.descripcion || p.Descripcion || '-';
                    const categoria = p.categoria || p.Categoria || '-';
                    const cantTotal = p.cantidad_total ?? p.Cantidad_Total ?? 0;
                    const cantDisp = p.cantidad_disponible ?? p.Cantidad_Disponible ?? 0;
                    const estado = p.estado || p.Estado || '-';
                    const foto = p.foto_material || p.Foto_Material || '';

                    const fotoLimpia = foto ? foto.replace(/^\/+/, '') : '';
                    const fotoRuta = fotoLimpia ? `../CRUD_Productos/${fotoLimpia}` : '';

                    return `
                        <tr>
                            <td>${id}</td>
                            <td>
                                <img src="${fotoRuta}" 
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; background: #eee;" 
                                     alt="Img" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <span style="display:none; font-size: 11px; color: #777;">Sin foto</span>
                            </td>
                            <td><b>${nombre}</b></td>
                            <td>${descripcion}</td>
                            <td>${categoria}</td>
                            <td>${cantTotal}</td>
                            <td>${cantDisp}</td>
                            <td>${estado}</td>
                            <td style="white-space: nowrap;">
                                <button class="btn-modal btn-editar" onclick="editarProducto(${id})">Editar</button>
                                <button class="btn-modal btn-eliminar" onclick="eliminarProducto(${id})">Eliminar</button>
                            </td>
                        </tr>
                    `;
                }).join('');
            } catch (error) {
                console.error("Error al cargar productos:", error);
            }
        }   

        function abrirModalFormularioProducto() {
            esEdicionProducto = false;
            document.getElementById('formProducto').reset();
            document.getElementById('id_material').value = '';
            document.getElementById('foto_actual').value = '';
            document.getElementById('tituloFormProducto').innerText = 'Nuevo Producto';
            document.getElementById('modalFormularioProducto').classList.add('active');
        }

        async function editarProducto(id) {
            esEdicionProducto = true;
            try {
                const res = await fetch(`${RUTA_CONTROLADOR_PRODUCTOS}?action=ver&id=${id}`);
                const p = await res.json();

                const idMat = p.id_material || p.ID_Material;
                const nomMat = p.nombre || p.Nombre;
                const descMat = p.descripcion || p.Descripcion || '';
                const catMat = p.categoria || p.Categoria || '';
                const cantTotal = p.cantidad_total ?? p.Cantidad_Total ?? 0;
                const cantDisp = p.cantidad_disponible ?? p.Cantidad_Disponible ?? 0;
                const estMat = p.estado || p.Estado || 'Disponible';
                const fotoMat = p.foto_material || p.Foto_Material || '';

                document.getElementById('id_material').value = idMat;
                document.getElementById('prod_nombre').value = nomMat;
                document.getElementById('prod_descripcion').value = descMat;
                document.getElementById('prod_categoria').value = catMat;
                document.getElementById('prod_cant_total').value = cantTotal;
                document.getElementById('prod_cant_disp').value = cantDisp;
                document.getElementById('prod_estado').value = estMat;
                document.getElementById('foto_actual').value = fotoMat;

                document.getElementById('tituloFormProducto').innerText = 'Editar Producto';
                document.getElementById('modalFormularioProducto').classList.add('active');
            } catch (error) {
                console.error("Error al cargar el producto:", error);
            }
        }

        function solicitarConfirmacionProducto(e) {
            e.preventDefault();
            const form = document.getElementById('formProducto');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const mensaje = esEdicionProducto 
                ? '¿Estás seguro de actualizar este producto?' 
                : '¿Estás seguro de registrar este nuevo producto?';

            document.getElementById('textoConfirmacionProducto').innerText = mensaje;
            document.getElementById('modalConfirmacionGuardarProducto').classList.add('active');
        }

        async function ejecutarGuardadoProducto() {
            cerrarModal('modalConfirmacionGuardarProducto');

            const form = document.getElementById('formProducto');
            const formData = new FormData(form);
            const accion = esEdicionProducto ? 'actualizar' : 'guardar';

            try {
                const res = await fetch(`${RUTA_CONTROLADOR_PRODUCTOS}?action=${accion}`, {
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
                    alert(esEdicionProducto ? '¡Producto actualizado con éxito!' : '¡Producto guardado con éxito!');
                    cerrarModal('modalFormularioProducto');
                    form.reset();
                    cargarProductos();
                } else {
                    alert(data.message || 'El producto ya existe o no se pudo guardar.');
                }
            } catch (error) {
                console.error("Error de red/petición:", error);
                alert('Error de conexión con el servidor: ' + error.message);
            }
        }

        async function eliminarProducto(id) {
            if (confirm('¿Desea eliminar el producto seleccionado?')) {
                try {
                    const res = await fetch(`${RUTA_CONTROLADOR_PRODUCTOS}?action=eliminar&id=${id}`);
                    const data = await res.json();
                    if (data.success) {
                        cargarProductos();
                    } else {
                        alert(data.message || 'No se pudo eliminar el producto.');
                    }
                } catch (error) {
                    console.error("Error al eliminar producto:", error);
                }
            }
        }
    </script>
</body>
</html>