// Rutas globales a los controladores PHP
        const RUTA_CONTROLADOR = '../CRUD_Usuarios/Controlador/Controlador_Usuarios.php';
        const RUTA_CONTROLADOR_PRODUCTOS = '../CRUD_Productos/Controlador/Controlador_Productos.php';
        const RUTA_VERIFICADOR = '../Login_logica/Validar_Cedula.php';

        // Banderas de estado de edición
        let esEdicion = false;
        let esEdicionProducto = false;

        /**
         * Algoritmo del Módulo 10 para validar el Dígito Verificador de la Cédula Uruguaya (CI).
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

        /**
         * Oculta una ventana modal por su ID de forma segura.
         */
        function cerrarModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.remove('active');
        }

        // Configuración inicial al cargar el DOM
        document.addEventListener('DOMContentLoaded', () => {
            // Menú desplegable de perfil
            const menuBtn = document.getElementById('menuBtn');
            const menuContent = document.getElementById('menuContent');
            if (menuBtn && menuContent) {
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

            // Restricción a números en el campo de Cédula
            const cedulaInput = document.getElementById('cedula');
            const errorCedula = document.getElementById('errorCedula');

            if (cedulaInput) {
                cedulaInput.addEventListener('input', (e) => {
                    e.target.value = e.target.value.replace(/[^0-9]/g, '');
                    if (errorCedula) errorCedula.style.display = 'none';
                });
            }
        });

    /* ==========================================================================
        MÓDULO VISTA CLIENTE (TARJETAS DE PRÉSTAMO Y CARRITO V7)
    ========================================================================== */
            async function abrirModalSolicitarPrestamo() {
            const modal = document.getElementById('modalSolicitarPrestamo');
            if (modal) {
                modal.classList.add('active');
            }

            // Averigua si el cliente ya tiene un préstamo aprobado o entregado (en ese caso no puede pedir otro)
            if (typeof verificarPrestamoActivo === 'function') {
                await verificarPrestamoActivo();
            }

            // Fuerza a pintar el carrito vacío o con los ítems actuales al abrir
            if (typeof dibujarCarrito === 'function') {
                dibujarCarrito();
            }

            // Carga los productos del catálogo
            if (typeof cargarProductosCliente === 'function') {
                await cargarProductosCliente();
            }
        }

            async function cargarProductosCliente() {
                try {
                    const res = await fetch(`${RUTA_CONTROLADOR_PRODUCTOS}?action=catalogo`);
                    const textoRespuesta = await res.text();
                    
                    let productos;
                    try {
                        productos = JSON.parse(textoRespuesta);
                    } catch (e) {
                        console.error("Respuesta del servidor no válida:", textoRespuesta);
                        alert("Error al cargar el catálogo:\n" + textoRespuesta.substring(0, 300));
                        return;
                    }

                    const contenedor = document.getElementById('contenedor-productos-cliente');
                    if (!contenedor) return; 

                    if (!Array.isArray(productos) || productos.length === 0) {
                        contenedor.innerHTML = `<p style="text-align:center; width:100%; grid-column: 1 / -1;">No hay productos disponibles en este momento.</p>`;
                        return;
                    }

                    contenedor.innerHTML = productos.map(p => {
                        const fotoLimpia = p.foto_material ? p.foto_material.replace(/^\/+/, '') : '';
                        const fotoRuta = fotoLimpia ? `../CRUD_Productos/${fotoLimpia}` : '';
                        
                        // v7: El catálogo agrupa por nombre y maneja unidades "libres"
                        const libres = parseInt(p.libres) || 0;
                        const estadoDisp = p.disponibilidad || (libres > 0 ? 'Disponible' : 'Agotado');

                        let accion = '';
                        if (libres > 0) {
                            accion = `
                                <div style="display: flex; gap: 8px; margin-top: 10px; align-items: center;">
                                    <input type="number" class="cant-pedido" data-nombre="${esc(p.nombre)}" min="1" max="${libres}" value="1" style="width: 60px; padding: 6px;">
                                    <button onclick="agregarAlPedido(${esc(JSON.stringify(p.nombre))}, ${libres})" class="btn-prestamo" style="margin-top: 0; flex-grow: 1;">Agregar al pedido</button>
                                </div>`;
                        } else {
                            accion = `<button class="btn-prestamo btn-sin-stock" disabled style="margin-top: 10px; width: 100%;">${esc(estadoDisp)}</button>`;
                        }

                        return `
                            <div class="tarjeta-producto${libres > 0 ? '' : ' tarjeta-agotada'}">
                                <img src="${esc(fotoRuta)}" alt="${esc(p.nombre)}" onerror="this.src=''; this.style.display='none';">
                                <div class="tarjeta-cuerpo">
                                    <h4>${esc(p.nombre)}</h4>
                                    <p><strong>Categoría:</strong> ${esc(p.categoria || '-')}</p>
                                    <p><strong>Descripción:</strong> ${esc(p.descripcion || '-')}</p>
                                    <p><strong>Disponibles ahora:</strong> ${libres} (Total: ${esc(p.total)})</p>
                                    ${accion}
                                </div>
                            </div>
                        `;
                    }).join('');

                    // Dibuja o actualiza el carrito de la interfaz
                    if (typeof dibujarCarrito === 'function') {
                        dibujarCarrito();
                    }

                    // Si el cliente ya tiene un préstamo aprobado/entregado, deshabilita los botones de pedir
                    if (typeof aplicarBloqueoCatalogo === 'function') {
                        aplicarBloqueoCatalogo();
                    }

                } catch (error) {
                    console.error("Error al cargar el catálogo de cliente:", error);
                }
            }

            

        /* ==========================================================================
           MÓDULO USUARIOS & TELÉFONOS
           ========================================================================== */
        async function abrirModalGestionUsuarios() {
            const modal = document.getElementById('modalGestionUsuarios');
            if (!modal) {
                alert("Error: No se encontró el modal 'modalGestionUsuarios' en la página.");
                return;
            }
            modal.classList.add('active');
            await cargarUsuarios();
        }

        function agregarCampoTelefono(valor = '', esObligatorio = false) {
            const lista = document.getElementById('lista-telefonos');
            if (!lista) return;

            const div = document.createElement('div');
            div.className = 'input-grupo-tel';
            div.style.cssText = 'display: flex; gap: 8px; margin-bottom: 6px;';

            const attrRequired = esObligatorio ? 'required' : '';

            div.innerHTML = `
                <input type="text" name="telefonos[]" class="form-control telefono-input" 
                       inputmode="numeric" placeholder="Ej: 099123456" value="${valor}" 
                       oninput="this.value = this.value.replace(/[^0-9]/g, '');" ${attrRequired} style="flex: 1;">
                <button type="button" class="btn-modal btn-eliminar" style="padding: 2px 8px; font-size: 14px;" onclick="eliminarCampoTelefono(this)">&times;</button>
            `;
            lista.appendChild(div);
        }

        function eliminarCampoTelefono(btn) {
            const lista = document.getElementById('lista-telefonos');
            if (lista && lista.children.length > 1) {
                btn.parentElement.remove();
            } else if (btn.parentElement) {
                const input = btn.parentElement.querySelector('input');
                if (input) input.value = '';
            }
        }

        async function cargarUsuarios() {
    try {
        const res = await fetch(`${RUTA_CONTROLADOR}?action=listar`);
        const usuarios = await res.json();

        const tbody = document.getElementById('bodyTablaUsuarios');
        if (!tbody) return;

        if (!Array.isArray(usuarios) || usuarios.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;">No hay usuarios registrados.</td></tr>`;
            return;
        }

        tbody.innerHTML = usuarios.map(u => {
            const cedulaVal = u.ci || u.cedula || '-';
            const telefonosTexto = (Array.isArray(u.telefonos) && u.telefonos.length > 0)
                ? u.telefonos.join(', ')
                : '-';

            const inactiva = String(u.estado_cuenta || '').trim().toUpperCase() === 'INACTIVA';

            const botonEstado = inactiva
                ? `<button class="btn-modal btn-crear" onclick="cambiarEstadoUsuario('${cedulaVal}', 'ACTIVA')">Reactivar</button>`
                : `<button class="btn-modal btn-desactivar" onclick="cambiarEstadoUsuario('${cedulaVal}', 'INACTIVA')">Desactivar</button>`;

            const botonEditar = inactiva
                ? `<button class="btn-modal btn-editar" disabled>Editar</button>`
                : `<button class="btn-modal btn-editar" onclick="editarUsuario('${cedulaVal}')">Editar</button>`;

            return `
                <tr class="${inactiva ? 'fila-inactiva' : ''}">
                    <td>${cedulaVal}</td>
                    <td>${u.nombre || '-'}</td>
                    <td>${u.apellido || '-'}</td>
                    <td>${u.email || u.correo || '-'}</td>
                    <td>${telefonosTexto}</td>
                    <td>${u.rol || '-'}</td>
                    <td>${u.especialidad || '-'}</td>
                    <td class="celda-acciones" style="white-space: nowrap;">
                        ${botonEditar}
                        ${botonEstado}
                    </td>
                </tr>
            `;
        }).join('');
    } catch (error) {
        console.error("Error al cargar lista de usuarios:", error);
    }
}

async function cambiarEstadoUsuario(cedula, nuevoEstado) {
    const accion = nuevoEstado === 'INACTIVA' ? 'desactivar' : 'reactivar';
    if (!confirm(`¿Seguro que querés ${accion} esta cuenta?`)) return;

    try {
        const res = await fetch(`${RUTA_CONTROLADOR}?action=cambiarEstado`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cedula: cedula, estado: nuevoEstado })
        });

        const textoRespuesta = await res.text(); // Capturamos el texto crudo
        let data;
        try {
            data = JSON.parse(textoRespuesta); // Intentamos convertirlo a JSON
        } catch (e) {
            // Si no es JSON, mostramos el error real que envió PHP en la pantalla
            console.error("Respuesta cruda del servidor:", textoRespuesta);
            alert("Error de PHP:\n" + textoRespuesta.substring(0, 400));
            return;
        }

        if (data.success) {
            cargarUsuarios();
        } else {
            alert(data.message || 'No se pudo cambiar el estado de la cuenta.');
        }
    } catch (error) {
        console.error("Error de red:", error);
        alert('Error de red o de conexión.');
    }

}
        function abrirModalFormulario() {
            esEdicion = false;
            const form = document.getElementById('formUsuario');
            if (form) form.reset();

            const cedulaOrig = document.getElementById('cedula_original');
            if (cedulaOrig) cedulaOrig.value = '';

            const cedulaInput = document.getElementById('cedula');
            if (cedulaInput) cedulaInput.readOnly = false;

            const errorCedula = document.getElementById('errorCedula');
            if (errorCedula) errorCedula.style.display = 'none';

            const listaTel = document.getElementById('lista-telefonos');
            if (listaTel) {
                listaTel.innerHTML = '';
                agregarCampoTelefono('', true);
            }

            const labelTel = document.getElementById('labelTelefonos');
            if (labelTel) labelTel.innerText = 'Teléfono(s) *';

            const titulo = document.getElementById('tituloFormUsuario');
            if (titulo) titulo.innerText = 'Nuevo Usuario';

            const helpPass = document.getElementById('helpPass');
            if (helpPass) helpPass.style.display = 'none';

            const passInput = document.getElementById('contrasena');
            if (passInput) passInput.required = true;

            const modal = document.getElementById('modalFormularioUsuario');
            if (modal) modal.classList.add('active');
        }

        async function editarUsuario(cedula) {
            esEdicion = true;
            try {
                const res = await fetch(`${RUTA_CONTROLADOR}?action=ver&cedula=${cedula}`);
                const u = await res.json();

                if (!u || (!u.ci && !u.cedula)) {
                    alert("No se encontraron datos para el usuario seleccionado.");
                    return;
                }

                const ciVal = u.ci || u.cedula;
                document.getElementById('cedula_original').value = ciVal;
                document.getElementById('cedula').value = ciVal;
                document.getElementById('cedula').readOnly = true;

                const errorCedula = document.getElementById('errorCedula');
                if (errorCedula) errorCedula.style.display = 'none';

                document.getElementById('nombre').value = u.nombre || '';
                document.getElementById('apellido').value = u.apellido || '';
                document.getElementById('correo').value = u.email || u.correo || '';
                document.getElementById('rol').value = u.rol || 'CLIENTE';
                document.getElementById('especialidad').value = u.especialidad || '';

                const lista = document.getElementById('lista-telefonos');
                if (lista) {
                    lista.innerHTML = '';
                    if (Array.isArray(u.telefonos) && u.telefonos.length > 0) {
                        u.telefonos.forEach(tel => agregarCampoTelefono(tel, false));
                    } else {
                        agregarCampoTelefono('', false);
                    }
                }

                const labelTel = document.getElementById('labelTelefonos');
                if (labelTel) labelTel.innerText = 'Teléfono(s) (Opcional)';

                const passActual = document.getElementById('contrasena_actual');
                if (passActual) passActual.value = u.contrasenia || u.contrasena || '';

                const passInput = document.getElementById('contrasena');
                if (passInput) {
                    passInput.value = '';
                    passInput.required = false;
                }

                const helpPass = document.getElementById('helpPass');
                if (helpPass) helpPass.style.display = 'block';

                const titulo = document.getElementById('tituloFormUsuario');
                if (titulo) titulo.innerText = 'Editar Usuario';

                const modal = document.getElementById('modalFormularioUsuario');
                if (modal) modal.classList.add('active');
            } catch (error) {
                console.error("Error al cargar usuario para edición:", error);
                alert("Error al obtener los datos del usuario.");
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
                if (errorCedula) errorCedula.style.display = 'block';
                document.getElementById('cedula').focus();
                return;
            } else {
                if (errorCedula) errorCedula.style.display = 'none';
            }

            const mensaje = esEdicion 
                ? '¿Estás seguro de actualizar este usuario?' 
                : '¿Estás seguro de registrar este nuevo usuario?';

            const textoConf = document.getElementById('textoConfirmacion');
            if (textoConf) textoConf.innerText = mensaje;

            const modalConf = document.getElementById('modalConfirmacionGuardar');
            if (modalConf) modalConf.classList.add('active');
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

                const data = await res.json();

                if (data.success) {
                    alert(data.message || 'Operación realizada con éxito.');
                    cerrarModal('modalFormularioUsuario');
                    form.reset();
                    cargarUsuarios();
                } else {
                    alert(data.message || 'Ocurrió un error al guardar los datos.');
                }
            } catch (error) {
                console.error("Error al guardar:", error);
                alert('Error de conexión con el servidor.');
            }
        }

        async function eliminarUsuario(cedula) {
            if (confirm('¿Desea eliminar el usuario seleccionado?')) {
                try {
                    const res = await fetch(`${RUTA_CONTROLADOR}?action=eliminar&cedula=${cedula}`);
                    const data = await res.json();
                    if (data.success) {
                        alert("Usuario eliminado correctamente.");
                        cargarUsuarios();
                    } else {
                        alert(data.message || 'No se pudo eliminar el usuario.');
                    }
                } catch (error) {
                    console.error("Error al eliminar usuario:", error);
                    alert("Error de conexión al intentar eliminar el usuario.");
                }
            }
        }

        /* ==========================================================================
           MÓDULO PRODUCTOS / MATERIALES (ADMINISTRADOR)
           ========================================================================== */
        async function abrirModalGestionProductos() {
            const modal = document.getElementById('modalGestionProductos');
            if (modal) modal.classList.add('active');
            await cargarProductos();
        }

       // Carga la tabla principal agregando el botón "Historial" en la columna de acciones
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
        if (!tbody) return;

        if (!Array.isArray(productos) || productos.length === 0) {
            tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;">No hay productos registrados.</td></tr>`;
            return;
        }

        tbody.innerHTML = productos.map(p => {
            const fotoLimpia = p.foto_material ? p.foto_material.replace(/^\/+/, '') : (p.Foto_Material ? p.Foto_Material.replace(/^\/+/, '') : '');
            const fotoRuta = fotoLimpia ? `../CRUD_Productos/${fotoLimpia}` : '';

            const estadoUpper = (p.estado || '').toUpperCase();
            const esBaja = estadoUpper === 'DADO_DE_BAJA';
            
            // Estilos para atenuar únicamente la información cuando el producto esté dado de baja
            const estiloDato = esBaja ? 'opacity: 0.55; color: #6c757d;' : '';

            const etiquetaEstado = badgeEstadoMaterial(p.estado);
            const detalleMotivo = (esBaja && p.motivo_baja) 
                ? `<br><small style="color: #6c757d; font-style: italic; display: block; margin-top: 4px;">Motivo: ${p.motivo_baja}</small>` 
                : '';

            // Botón de historial visible en todos los estados
            const btnHistorial = `<button class="btn-modal" style="background-color: #0284c7; color: white;" onclick="verHistorialProducto(${p.id_material})">Historial</button>`;

            let botonesAccion = '';
            if (!esBaja) {
                botonesAccion = `
                    <button class="btn-modal btn-editar" onclick="editarProducto(${p.id_material})">Editar</button>
                    <button class="btn-modal btn-eliminar" onclick="BajarMaterial(${p.id_material})">Dar de Baja</button>
                    ${btnHistorial}
                `;
            } else {
                botonesAccion = `
                    <button class="btn-modal btn-crear" style="background-color: #28a745;" onclick="reactivarMaterial(${p.id_material})">Reactivar</button>
                    ${btnHistorial}
                `;
            }

            return `
                <tr style="${esBaja ? 'background-color: #f8f9fa;' : ''}">
                    <td style="${estiloDato}">${p.id_material}</td>
                    <td style="${estiloDato}">${p.id_serie ? `<code>${p.id_serie}</code>` : '<small>por cantidad</small>'}</td>
                    <td style="${estiloDato}">
                        <img src="${fotoRuta}" 
                            style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; background: #eee;" 
                            alt="Img" 
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <span style="display:none; font-size: 11px; color: #777;">Sin foto</span>
                    </td>
                    <td style="${estiloDato}"><b>${p.nombre}</b></td>
                    <td style="${estiloDato}">${p.descripcion || '-'}</td>
                    <td style="${estiloDato}">${p.categoria || '-'}</td>
                    <td style="${estiloDato}">${p.cantidad_total}</td>
                    <td style="${estiloDato}">${p.cantidad_disponible}</td>
                    <td style="${estiloDato}">
                        ${etiquetaEstado}
                        ${detalleMotivo}
                    </td>
                    <!-- Columna de acciones protegida con 100% de opacidad -->
                    <td style="white-space: nowrap; opacity: 1 !important;">
                        ${botonesAccion}
                    </td>
                </tr>
            `;
        }).join('');
    } catch (error) {
        console.error("Error al cargar productos:", error);
    }
}

// Devuelve la etiqueta (pastilla de color) de un estado de material
function badgeEstadoMaterial(estado) {
    const e = String(estado || '').toUpperCase();
    const clases = {
        DISPONIBLE: 'badge-disponible',
        PRESTADO: 'badge-prestado',
        EN_MANTENIMIENTO: 'badge-mantenimiento',
        DADO_DE_BAJA: 'badge-baja'
    };
    const textos = {
        DISPONIBLE: 'Disponible',
        PRESTADO: 'Prestado',
        EN_MANTENIMIENTO: 'En mantenimiento',
        DADO_DE_BAJA: 'Dado de baja'
    };
    return `<span class="badge-estado ${clases[e] || 'badge-secondary'}">${textos[e] || estado || '-'}</span>`;
}

// Consulta vía AJAX el historial del producto e inserta las filas en el modal
async function verHistorialProducto(idMaterial) {
    try {
        const res = await fetch(`${RUTA_CONTROLADOR_PRODUCTOS}?action=historial&id=${idMaterial}`);
        const historial = await res.json();

        const tbody = document.getElementById('bodyTablaHistorial');
        if (!tbody) return;

        if (!Array.isArray(historial) || historial.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;">No hay registros de cambios de estado para este producto.</td></tr>`;
        } else {
            tbody.innerHTML = historial.map(h => `
                <tr>
                    <td><small>${h.fecha || '-'}</small></td>
                    <td>${h.estado_anterior ? badgeEstadoMaterial(h.estado_anterior) : '<span class="badge-estado badge-secondary">Inicial</span>'}</td>
                    <td>${badgeEstadoMaterial(h.estado_nuevo)}</td>
                    <td><b>${h.evento || '-'}</b></td>
                    <td>${h.ci_responsable || '<small>Sistema</small>'}</td>
                    <td>${h.motivo || '-'}</td>
                </tr>
            `).join('');
        }

        const titulo = document.getElementById('tituloModalHistorial');
        if (titulo) titulo.innerText = `Historial del Producto #${idMaterial}`;

        const modal = document.getElementById('modalHistorialProducto');
        if (modal) modal.classList.add('active');

    } catch (error) {
        console.error("Error al consultar el historial:", error);
        alert("Ocurrió un error al obtener el historial del producto.");
    }
}

        async function reactivarMaterial(idMaterial) {
            if (!confirm("¿Estás seguro de que deseas reactivar este producto y restablecerlo como DISPONIBLE?")) {
                return;
            }

            try {
                const res = await fetch(`${RUTA_CONTROLADOR_PRODUCTOS}?action=reactivar`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_material: idMaterial })
                });

                const resultado = await res.json();

                if (resultado.success) {
                    alert(resultado.message || "Producto reactivado con éxito.");
                    cargarProductos();
                } else {
                    alert(resultado.message || "No se pudo reactivar el producto.");
                }
            } catch (error) {
                console.error("Error al reactivar:", error);
                alert("Error de conexión al intentar reactivar el producto.");
            }
        }

        function ajustarFormularioProducto() {
            const tipoSelect = document.getElementById('prod_tipo');
            if (!tipoSelect) return;

            const tipo = tipoSelect.value;
            
            const gSeries = document.getElementById('grupoSeries');
            const gSerie = document.getElementById('grupoSerie');
            const gCant = document.getElementById('grupoCantidad');
            const gAplicar = document.getElementById('grupoAplicar');

            if (gSeries) gSeries.style.display = (!esEdicionProducto && tipo === 'SERIE') ? '' : 'none';
            if (gSerie) gSerie.style.display = (esEdicionProducto && tipo === 'SERIE') ? '' : 'none';
            if (gCant) gCant.style.display = (tipo === 'CANTIDAD') ? '' : 'none';
            if (gAplicar) gAplicar.style.display = esEdicionProducto ? '' : 'none';

            const prodSeries = document.getElementById('prod_series');
            if (prodSeries) prodSeries.required = !esEdicionProducto && tipo === 'SERIE';

            const prodIdSerie = document.getElementById('prod_id_serie');
            if (prodIdSerie) prodIdSerie.required = esEdicionProducto && tipo === 'SERIE';

            const prodCantTotal = document.getElementById('prod_cant_total');
            if (prodCantTotal) prodCantTotal.required = (tipo === 'CANTIDAD');

            tipoSelect.disabled = esEdicionProducto;
        }

        function abrirModalFormularioProducto() {
            esEdicionProducto = false;
            const form = document.getElementById('formProducto');
            if (form) form.reset();

            document.getElementById('id_material').value = '';
            document.getElementById('foto_actual').value = '';
            document.getElementById('tituloFormProducto').innerText = 'Nuevo Producto';
            
            ajustarFormularioProducto();
            document.getElementById('modalFormularioProducto').classList.add('active');
        }

        async function editarProducto(id) {
            esEdicionProducto = true;
            try {
                const res = await fetch(`${RUTA_CONTROLADOR_PRODUCTOS}?action=ver&id=${id}`);
                const p = await res.json();

                if (!p || !p.id_material) {
                    alert("No se encontraron datos para este producto.");
                    return;
                }

                document.getElementById('id_material').value = p.id_material;
                document.getElementById('prod_nombre').value = p.nombre || '';
                document.getElementById('prod_descripcion').value = p.descripcion || '';
                document.getElementById('prod_categoria').value = p.categoria || '';
                document.getElementById('foto_actual').value = p.foto_material || p.Foto_Material || '';

                const selectTipo = document.getElementById('prod_tipo');
                if (selectTipo) {
                    selectTipo.value = p.tipo_control || 'SERIE';
                }

                if (p.tipo_control === 'CANTIDAD') {
                    const cantInput = document.getElementById('prod_cant_total');
                    if (cantInput) cantInput.value = p.cantidad_total || 0;
                } else {
                    const serieInput = document.getElementById('prod_id_serie');
                    if (serieInput) serieInput.value = p.id_serie || '';
                }

                ajustarFormularioProducto();

                document.getElementById('tituloFormProducto').innerText = 'Editar Producto';
                document.getElementById('modalFormularioProducto').classList.add('active');

            } catch (error) {
                console.error("Error al cargar el producto para edición:", error);
                alert("Ocurrió un error al obtener la información del producto.");
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

        async function BajarMaterial(idMaterial) {
            const motivo = prompt("Ingrese el motivo de la baja del material:");
            
            if (motivo === null) return; 
            if (motivo.trim() === "") {
                alert("Debe especificar un motivo obligatoriamente.");
                return;
            }

            try {
                const res = await fetch(`${RUTA_CONTROLADOR_PRODUCTOS}?action=darDeBaja`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_material: idMaterial, motivo_baja: motivo })
                });
                
                const resultado = await res.json();

                if (resultado.success) {
                    alert(resultado.message || "Material dado de baja correctamente.");
                    cargarProductos();
                } else {
                    alert(resultado.message || resultado.mensaje || "No se pudo procesar la baja.");
                }
            } catch (error) {
                console.error("Error al dar de baja:", error);
                alert("Error de conexión al procesar la baja con el servidor.");
            }
        }

    // Funcion para el modal de imprimir stock
        async function abrirModalStockStock() {
        const r = await pedirJSON(`${RUTA_CONTROLADOR_PRODUCTOS}?action=reporte_stock`);
        if (!r || !r.success) return;

        const tbody = document.getElementById('bodyImprimirStock');
        tbody.innerHTML = r.stock.length ? r.stock.map(s => {
            let textoAprobados = `${s.count_aprobados}`;
            if (s.series_aprobadas && s.series_aprobadas.length > 0) {
                textoAprobados += ` <small style="color: #475569;">(${s.series_aprobadas.join(', ')})</small>`;
            }

            let textoPrestados = `${s.count_prestados}`;
            if (s.series_prestadas && s.series_prestadas.length > 0) {
                textoPrestados += ` <small style="color: #475569;">(${s.series_prestadas.join(', ')})</small>`;
            }

            return `
                <tr>
                    <td><b>${esc(s.nombre)}</b></td>
                    <td>${s.cantidad_total}</td>
                    <td>${s.cantidad_disponible}</td>
                    <td>
                        <span style="color: ${s.comprometidos > 0 ? '#d97706' : 'inherit'}; font-weight: ${s.comprometidos > 0 ? 'bold' : 'normal'};">
                            ${s.comprometidos}
                        </span>
                    </td>
                    <td>
                        <span style="color: ${s.count_aprobados > 0 ? '#d97706' : 'inherit'}; font-weight: ${s.count_aprobados > 0 ? 'bold' : 'normal'};">
                            ${textoAprobados}
                        </span>
                    </td>
                    <td>
                        <span style="color: ${s.count_prestados > 0 ? '#2563eb' : 'inherit'}; font-weight: ${s.count_prestados > 0 ? 'bold' : 'normal'};">
                            ${textoPrestados}
                        </span>
                    </td>
                </tr>
            `;
        }).join('') : '<tr><td colspan="6" style="text-align:center;">No hay materiales registrados.</td></tr>';

        document.getElementById('modalImprimirStock').classList.add('active');
    }

    function imprimirHistorialGeneral() {
    // Obtenemos el contenedor o el HTML interno del cuerpo de la tabla y título
    const contenidoTabla = document.getElementById('bodyHistorialGeneral').innerHTML;
    
    if (!contenidoTabla || contenidoTabla.includes('Todavía no hay movimientos')) {
        alert('No hay datos para imprimir.');
        return;
    }

    const ventanaImpresion = window.open('', '_blank', 'width=900,height=650');
    
    ventanaImpresion.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Historial General de Movimientos - CeiLab</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    color: #1e293b;
                    margin: 20px;
                }
                h2 {
                    text-align: center;
                    color: #0f172a;
                    margin-bottom: 5px;
                }
                .subtitulo {
                    text-align: center;
                    font-size: 13px;
                    color: #64748b;
                    margin-bottom: 25px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                th, td {
                    border: 1px solid #cbd5e1;
                    padding: 8px 10px;
                    font-size: 12px;
                    text-align: left;
                    vertical-align: middle;
                }
                th {
                    background-color: #f1f5f9;
                    color: #334155;
                    font-weight: bold;
                }
                small {
                    color: #64748b;
                }
                code {
                    background: #f8fafc;
                    padding: 2px 4px;
                    border-radius: 4px;
                    font-family: monospace;
                }
            </style>
        </head>
        <body>
            <h2>Historial General de Movimientos - Inventario CeiLab</h2>
            <div class="subtitulo">Generado el: ${new Date().toLocaleString()}</div>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Material</th>
                        <th>Evento</th>
                        <th>De</th>
                        <th>A</th>
                        <th>Préstamo</th>
                        <th>Solicitante</th>
                        <th>Responsable</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    ${contenidoTabla}
                </tbody>
            </table>
            <script>
                window.onload = function() {
                    window.print();
                    window.close();
                }
            </script>
        </body>
        </html>
    `);
    ventanaImpresion.document.close();
}
// =========================================================
// CORRECCIÓN GLOBAL: DROPDOWN Y MODO OSCURO
// =========================================================

// 1. Función explícita para abrir/cerrar el Dropdown de Lucía
window.toggleDropdown = function(event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById('userDropdown') || document.querySelector('.dropdown-menu');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
};

// Cerrar el dropdown al hacer clic en cualquier otra parte de la pantalla
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('userDropdown') || document.querySelector('.dropdown-menu');
    const userBtn = document.querySelector('.user-profile-btn') || document.querySelector('.user-dropdown-btn');
    
    if (dropdown && dropdown.classList.contains('show')) {
        if (!userBtn || !userBtn.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    }
});

// 2. Función explícita para alternar el Modo Oscuro
window.toggleTheme = function() {
    const isDark = document.body.classList.toggle('dark-mode');
    localStorage.setItem('ceilab-theme', isDark ? 'dark' : 'light');
};

// 3. Cargar el tema guardado al iniciar la página
document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('ceilab-theme');
    const themeToggle = document.getElementById('themeToggle') || document.querySelector('.switch input');
    
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        if (themeToggle) themeToggle.checked = true;
    }

    // Si el switch no usa onclick sino evento change:
    if (themeToggle) {
        themeToggle.addEventListener('change', window.toggleTheme);
    }
});

// =========================================================
// CONTROL DE VISIBILIDAD DINÁMICA SEGÚN TIPO DE CONTROL
// =========================================================
window.toggleCamposTipoControl = function() {
    const selectTipo = document.getElementById('tipo_control');
    const grupoSeriesMulti = document.getElementById('grupo-series-multilinea');
    const grupoSerieUnico = document.getElementById('grupo-serie-unico');
    const grupoCantidad = document.getElementById('grupo-cantidad');

    if (!selectTipo) return;

    // Evaluamos el valor seleccionado
    const esPorSerie = selectTipo.value.includes('serie');

    if (esPorSerie) {
        // Mostrar campos de número de serie y ocultar cantidad total
        if (grupoSeriesMulti) grupoSeriesMulti.style.display = 'flex';
        if (grupoSerieUnico) grupoSerieUnico.style.display = 'flex';
        if (grupoCantidad) grupoCantidad.style.display = 'none';
    } else {
        // Ocultar campos de número de serie y mostrar cantidad total
        if (grupoSeriesMulti) grupoSeriesMulti.style.display = 'none';
        if (grupoSerieUnico) grupoSerieUnico.style.display = 'none';
        if (grupoCantidad) grupoCantidad.style.display = 'flex';
    }
};

// Sincronizar automáticamente cada vez que se carga la página o se abre el modal
document.addEventListener('DOMContentLoaded', () => {
    window.toggleCamposTipoControl();
});

// =========================================================
// CONTROL DE VISIBILIDAD MEDIANTE CLASES CSS
// =========================================================
window.toggleCamposTipoControl = function() {
    const selectTipo = document.getElementById('tipo_control');
    if (!selectTipo) return;

    // Detectamos si la opción seleccionada es por serie o por cantidad
    const valor = selectTipo.value.toLowerCase();
    const esPorSerie = valor.includes('serie');

    // Buscamos los bloques (pueden ser por ID o buscando las etiquetas)
    const grupoSeriesMulti = document.getElementById('grupo-series-multilinea') || 
                             document.querySelector('#numeros_serie')?.closest('.form-group');
                             
    const grupoSerieUnico = document.getElementById('grupo-serie-unico') || 
                            document.querySelector('#numero_serie_input')?.closest('.form-group');
                            
    const grupoCantidad = document.getElementById('grupo-cantidad') || 
                          document.querySelector('#cantidad_total')?.closest('.form-group');

    if (esPorSerie) {
        // Mostrar series / Ocultar cantidad
        if (grupoSeriesMulti) grupoSeriesMulti.classList.remove('oculto-dinamico');
        if (grupoSerieUnico) grupoSerieUnico.classList.remove('oculto-dinamico');
        if (grupoCantidad) grupoCantidad.classList.add('oculto-dinamico');
    } else {
        // Ocultar series / Mostrar cantidad
        if (grupoSeriesMulti) grupoSeriesMulti.classList.add('oculto-dinamico');
        if (grupoSerieUnico) grupoSerieUnico.classList.add('oculto-dinamico');
        if (grupoCantidad) grupoCantidad.classList.remove('oculto-dinamico');
    }
};

// Escuchar cambios en el selector directamente en todo el documento
document.addEventListener('change', (e) => {
    if (e.target && e.target.id === 'tipo_control') {
        window.toggleCamposTipoControl();
    }
});

// Sincronizar al cargar la página
document.addEventListener('DOMContentLoaded', () => {
    window.toggleCamposTipoControl();
});

// =========================================================
// CONMUTACIÓN DINÁMICA DE CAMPOS EN EL FORMULARIO DE PRODUCTO
// =========================================================
window.ajustarFormularioProducto = function() {
    const selectTipo = document.getElementById('prod_tipo');
    const grupoSeries = document.getElementById('grupoSeries');   // Textarea multilinea
    const grupoSerie = document.getElementById('grupoSerie');     // Input serie único
    const grupoCantidad = document.getElementById('grupoCantidad'); // Input cantidad total
    const grupoAplicar = document.getElementById('grupoAplicar');   // Checkbox de grupo

    if (!selectTipo) return;

    const valor = selectTipo.value.toUpperCase();

    if (valor === 'SERIE') {
        // Modo Por Número de Serie:
        // Se muestran las opciones de serie y se oculta la cantidad total
        if (grupoSeries) grupoSeries.style.setProperty('display', 'block', 'important');
        if (grupoCantidad) grupoCantidad.style.setProperty('display', 'none', 'important');
        
        // Si está en modo edición (donde existe grupoSerie único) o creación
        if (grupoSerie && grupoSerie.querySelector('input').value.trim() !== "") {
            if (grupoSeries) grupoSeries.style.setProperty('display', 'none', 'important');
            grupoSerie.style.setProperty('display', 'block', 'important');
        }
    } else if (valor === 'CANTIDAD') {
        // Modo Por Cantidad:
        // Se ocultan los campos de serie y se muestra el campo de cantidad total
        if (grupoSeries) grupoSeries.style.setProperty('display', 'none', 'important');
        if (grupoSerie) grupoSerie.style.setProperty('display', 'none', 'important');
        if (grupoAplicar) grupoAplicar.style.setProperty('display', 'none', 'important');
        
        if (grupoCantidad) grupoCantidad.style.setProperty('display', 'block', 'important');
    }
};

// Ejecutar la sincronización al abrir la ventana modal
window.abrirModalFormularioProducto = function() {
    const modal = document.getElementById('modalFormularioProducto');
    if (modal) {
        modal.classList.add('active');
        // Ejecutamos la función para que evalúe la opción seleccionada al abrir
        window.ajustarFormularioProducto();
    }
};

// Sincronizar al cargar el documento
document.addEventListener('DOMContentLoaded', () => {
    window.ajustarFormularioProducto();
});


// Abrir el modal del perfil
window.abrirModalVerPerfil = function(event) {
    if (event) event.preventDefault();
    
    // Cierra el menú desplegable de Lucía
    const dropdown = document.getElementById('menuContent');
    if (dropdown) dropdown.classList.remove('show');

    // Abre el modal de perfil
    const modal = document.getElementById('modalVerPerfil');
    if (modal) modal.classList.add('active');
};

// Cierre general de modales
window.cerrarModal = function(idModal) {
    const modal = document.getElementById(idModal);
    if (modal) modal.classList.remove('active');
};

// =========================================================
// CARGA Y EDICIÓN CORRECTA DEL PERFIL EN SCRIPT.JS
// =========================================================
window.abrirEditarPerfilPropio = function() {
    // 1. Cierra la ventana del perfil
    cerrarModal('modalVerPerfil');

    // 2. Abre el modal de edición de usuario
    const modalForm = document.getElementById('modalFormularioUsuario');
    if (!modalForm) return;
    
    modalForm.classList.add('active');

    // 3. Cambia el título de la ventana
    const titulo = document.getElementById('tituloFormUsuario');
    if (titulo) titulo.textContent = 'Editar Mi Perfil';

    // 4. Carga de datos reales sin etiquetas PHP escritas como texto
    const user = window.USUARIO_ACTUAL || {};

    const inputCedulaOriginal = document.getElementById('cedula_original');
    const inputCedula = document.getElementById('cedula');
    const inputNombre = document.getElementById('nombre');
    const inputApellido = document.getElementById('apellido');
    const inputCorreo = document.getElementById('correo');
    const inputPass = document.getElementById('contrasena');
    const helpPass = document.getElementById('helpPass');

    if (inputCedulaOriginal) inputCedulaOriginal.value = user.cedula || '';
    if (inputCedula) inputCedula.value = user.cedula || '';
    if (inputNombre) inputNombre.value = user.nombre || '';
    if (inputApellido) inputApellido.value = user.apellido || '';
    if (inputCorreo) inputCorreo.value = user.correo || '';
    if (inputPass) inputPass.value = ''; // Se deja en blanco para no sobreescribir si no cambia
    if (helpPass) helpPass.style.display = 'block';

    // 5. Ocultar los campos que no deben modificarse (Cédula, Rol y Especialidad)
    const campoCedula = inputCedula ? inputCedula.closest('.form-group') : null;
    const campoRol = document.getElementById('rol') ? document.getElementById('rol').closest('.form-group') : null;
    const campoEspecialidad = document.getElementById('especialidad') ? document.getElementById('especialidad').closest('.form-group') : null;

    if (campoCedula) campoCedula.style.setProperty('display', 'none', 'important');
    if (campoRol) campoRol.style.setProperty('display', 'none', 'important');
    if (campoEspecialidad) campoEspecialidad.style.setProperty('display', 'none', 'important');
};

window.abrirModalFormulario = function() {
    const modalForm = document.getElementById('modalFormularioUsuario');
    if (modalForm) modalForm.classList.add('active');

    const titulo = document.getElementById('tituloFormUsuario');
    if (titulo) titulo.textContent = 'Usuario';

    // Mostrar Cédula, Rol y Especialidad
    const campoCedula = document.getElementById('cedula')?.closest('.form-group');
    const campoRol = document.getElementById('rol')?.closest('.form-group');
    const campoEspecialidad = document.getElementById('especialidad')?.closest('.form-group');

    if (campoCedula) campoCedula.style.setProperty('display', 'flex', 'important');
    if (campoRol) campoRol.style.setProperty('display', 'flex', 'important');
    if (campoEspecialidad) campoEspecialidad.style.setProperty('display', 'flex', 'important');
};