/* ==========================================================================
   1. SISTEMA DE MODALES PERSONALIZADOS (REEMPLAZO DE LOCALHOST)
   ========================================================================== */
(function () {
    'use strict';

    let cola = Promise.resolve();
    function encolar(fn) {
        const p = cola.then(fn, fn);
        cola = p.catch(() => {});
        return p;
    }

    const ICONOS = { info: 'i', ok: '✓', error: '!', aviso: '!', pregunta: '?' };
    const TITULOS = { info: 'Información', ok: 'Listo', error: 'Error', aviso: 'Atención', pregunta: 'Confirmar' };

    function adivinarTipo(msg) {
        const t = String(msg).toLowerCase();
        if (/\berror\b|no se pudo|no se encontr|inesperada|fall[oó]|conexi[oó]n|obligatori/.test(t)) return 'error';
        if (/con [eé]xito|correctamente|registrada|actualizado|enviada|guardado|devoluci[oó]n (completa|parcial)/.test(t)) return 'ok';
        if (/marc[aá] al menos|solo se pueden|no hay datos|hay \d+ libres|debe /.test(t)) return 'aviso';
        return 'info';
    }

    function mostrar({ mensaje, tipo, titulo, aceptar, cancelar, peligro, entrada, placeholder }) {
        return encolar(() => new Promise(resolve => {
            const previo = document.activeElement;

            const overlay = document.createElement('div');
            overlay.className = 'cl-dlg-overlay';
            const dlg = document.createElement('div');
            dlg.className = `cl-dlg cl-dlg-${tipo}`;
            dlg.setAttribute('role', cancelar ? 'alertdialog' : 'dialog');
            dlg.setAttribute('aria-modal', 'true');

            const head = document.createElement('div');
            head.className = 'cl-dlg-head';
            const ico = document.createElement('span');
            ico.className = 'cl-dlg-icono';
            ico.textContent = ICONOS[tipo] || 'i';
            const tit = document.createElement('span');
            tit.textContent = titulo || TITULOS[tipo] || 'Aviso';
            head.append(ico, tit);

            const body = document.createElement('div');
            body.className = 'cl-dlg-body';
            body.textContent = String(mensaje ?? '');
            let input = null;
            if (entrada) {
                input = document.createElement('input');
                input.type = 'text';
                input.className = 'cl-dlg-input';
                input.placeholder = placeholder || '';
                body.appendChild(input);
            }

            const foot = document.createElement('div');
            foot.className = 'cl-dlg-foot';
            let btnCancelar = null;
            if (cancelar) {
                btnCancelar = document.createElement('button');
                btnCancelar.type = 'button';
                btnCancelar.className = 'cl-dlg-btn cl-sec';
                btnCancelar.textContent = cancelar;
                foot.appendChild(btnCancelar);
            }
            const btnOk = document.createElement('button');
            btnOk.type = 'button';
            btnOk.className = 'cl-dlg-btn' + (peligro ? ' cl-peligro' : '');
            btnOk.textContent = aceptar;
            foot.appendChild(btnOk);

            dlg.append(head, body, foot);
            overlay.appendChild(dlg);
            document.body.appendChild(overlay);

            function cerrar(valor) {
                document.removeEventListener('keydown', teclas, true);
                overlay.remove();
                if (previo && typeof previo.focus === 'function') { try { previo.focus(); } catch (e) {} }
                resolve(valor);
            }
            const valorAceptar = () => (entrada ? input.value : true);
            const valorCancelar = () => (entrada ? null : (cancelar ? false : true));

            function teclas(e) {
                if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); cerrar(valorCancelar()); }
                else if (e.key === 'Enter' && e.target !== btnCancelar) { e.preventDefault(); e.stopPropagation(); cerrar(valorAceptar()); }
                else if (e.key === 'Tab') {
                    const foc = [...dlg.querySelectorAll('input,button')];
                    const i = foc.indexOf(document.activeElement);
                    e.preventDefault();
                    foc[(i + (e.shiftKey ? -1 : 1) + foc.length) % foc.length].focus();
                }
            }
            document.addEventListener('keydown', teclas, true);

            btnOk.addEventListener('click', () => cerrar(valorAceptar()));
            if (btnCancelar) btnCancelar.addEventListener('click', () => cerrar(valorCancelar()));
            overlay.addEventListener('mousedown', e => { if (e.target === overlay && cancelar) cerrar(valorCancelar()); });

            (input || btnOk).focus();
        }));
    }

    // API pública global
    window.avisar = (msg, o = {}) => mostrar({
        mensaje: msg, tipo: o.tipo || adivinarTipo(msg), titulo: o.titulo,
        aceptar: o.textoAceptar || 'Aceptar'
    }).then(() => undefined);

    window.confirmar = (msg, o = {}) => mostrar({
        mensaje: msg, tipo: 'pregunta', titulo: o.titulo, peligro: o.peligro,
        aceptar: o.textoAceptar || 'Aceptar', cancelar: o.textoCancelar || 'Cancelar'
    });

    window.pedirTexto = (msg, o = {}) => mostrar({
        mensaje: msg, tipo: 'pregunta', titulo: o.titulo, peligro: o.peligro, entrada: true,
        placeholder: o.placeholder, aceptar: o.textoAceptar || 'Aceptar', cancelar: o.textoCancelar || 'Cancelar'
    });

    // Sobrescribe alert nativo
    window.alert = function (msg) { window.avisar(msg); };
})();


/* ==========================================================================
   2. CONFIGURACIÓN GENERAL Y AUXILIARES
   ========================================================================== */
const RUTA_CONTROLADOR = '../CRUD_Usuarios/Controlador/Controlador_Usuarios.php';
const RUTA_CONTROLADOR_PRODUCTOS = '../CRUD_Productos/Controlador/Controlador_Productos.php';
const RUTA_VERIFICADOR = '../Login_logica/Validar_Cedula.php';

let esEdicion = false;
let esEdicionProducto = false;

function esc(texto) {
    if (texto === null || texto === undefined) return '';
    return String(texto)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

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

function cerrarModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
}

document.addEventListener('DOMContentLoaded', () => {
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
   3. MÓDULO VISTA CLIENTE
   ========================================================================== */
async function abrirModalSolicitarPrestamo() {
    const modal = document.getElementById('modalSolicitarPrestamo');
    if (modal) {
        modal.classList.add('active');
    }

    if (typeof verificarPrestamoActivo === 'function') {
        await verificarPrestamoActivo();
    }

    if (typeof dibujarCarrito === 'function') {
        dibujarCarrito();
    }

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
            window.avisar("Error al cargar el catálogo:\n" + textoRespuesta.substring(0, 300));
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

        if (typeof dibujarCarrito === 'function') {
            dibujarCarrito();
        }

        if (typeof aplicarBloqueoCatalogo === 'function') {
            aplicarBloqueoCatalogo();
        }

    } catch (error) {
        console.error("Error al cargar el catálogo de cliente:", error);
    }
}


/* ==========================================================================
   4. MÓDULO USUARIOS & TELÉFONOS
   ========================================================================== */
async function abrirModalGestionUsuarios() {
    const modal = document.getElementById('modalGestionUsuarios');
    if (!modal) {
        window.avisar("Error: No se encontró el modal 'modalGestionUsuarios' en la página.");
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
    const confirmado = await window.confirmar(`¿Seguro que querés ${accion} esta cuenta?`, {
        titulo: 'Confirmar estado de usuario',
        peligro: nuevoEstado === 'INACTIVA'
    });
    if (!confirmado) return;

    try {
        const res = await fetch(`${RUTA_CONTROLADOR}?action=cambiarEstado`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cedula: cedula, estado: nuevoEstado })
        });

        const textoRespuesta = await res.text();
        let data;
        try {
            data = JSON.parse(textoRespuesta);
        } catch (e) {
            console.error("Respuesta cruda del servidor:", textoRespuesta);
            window.avisar("Error de PHP:\n" + textoRespuesta.substring(0, 400));
            return;
        }

        if (data.success) {
            cargarUsuarios();
        } else {
            window.avisar(data.message || 'No se pudo cambiar el estado de la cuenta.');
        }
    } catch (error) {
        console.error("Error de red:", error);
        window.avisar('Error de red o de conexión.');
    }
}

// CORRECCIÓN: Restablece correctamente la variable esEdicion y habilita el formulario
window.abrirModalFormulario = function() {
    // 1. Forzar bandera a modo creación (NUEVO USUARIO)
    esEdicion = false;

    const modalForm = document.getElementById('modalFormularioUsuario');
    if (!modalForm) return;

    // 2. Resetear el formulario HTML
    const form = document.getElementById('formUsuario');
    if (form) form.reset();

    // 3. LIMPIEZA EXPLÍCITA DE TODOS LOS CAMPOS DE TEXTO
    // (Esto elimina completamente los datos del usuario editado anteriormente)
    const cedulaOrig = document.getElementById('cedula_original');
    if (cedulaOrig) cedulaOrig.value = '';

    const cedulaInput = document.getElementById('cedula');
    if (cedulaInput) {
        cedulaInput.value = '';
        cedulaInput.readOnly = false; // Permitir escribir cédula nueva
    }

    const inputNombre = document.getElementById('nombre');
    if (inputNombre) inputNombre.value = '';

    const inputApellido = document.getElementById('apellido');
    if (inputApellido) inputApellido.value = '';

    const inputCorreo = document.getElementById('correo');
    if (inputCorreo) inputCorreo.value = '';

    const inputPass = document.getElementById('contrasena');
    if (inputPass) {
        inputPass.value = '';
        inputPass.required = true; // Contraseña obligatoria al crear
    }

    const passActual = document.getElementById('contrasena_actual');
    if (passActual) passActual.value = '';

    const selectRol = document.getElementById('rol');
    if (selectRol) selectRol.selectedIndex = 0;

    const selectEsp = document.getElementById('especialidad');
    if (selectEsp) selectEsp.selectedIndex = 0;

    const errorCedula = document.getElementById('errorCedula');
    if (errorCedula) errorCedula.style.display = 'none';

    // 4. Resetear el título de la ventana
    const titulo = document.getElementById('tituloFormUsuario');
    if (titulo) titulo.textContent = 'Nuevo Usuario';

    // 5. Reiniciar la lista de teléfonos (dejar solo 1 campo vacío)
    const listaTel = document.getElementById('lista-telefonos');
    if (listaTel) {
        listaTel.innerHTML = '';
        if (typeof agregarCampoTelefono === 'function') {
            agregarCampoTelefono('', true);
        }
    }

    const labelTel = document.getElementById('labelTelefonos');
    if (labelTel) labelTel.innerText = 'Teléfono(s) *';

    const helpPass = document.getElementById('helpPass');
    if (helpPass) helpPass.style.display = 'none';

    // 6. Restaurar visibilidad de campos que pudieron ocultarse en "Editar Mi Perfil"
    const campoCedula = document.getElementById('cedula')?.closest('.form-group');
    const campoRol = document.getElementById('rol')?.closest('.form-group');
    const campoEspecialidad = document.getElementById('especialidad')?.closest('.form-group');

    if (campoCedula) campoCedula.style.setProperty('display', 'flex', 'important');
    if (campoRol) campoRol.style.setProperty('display', 'flex', 'important');
    if (campoEspecialidad) campoEspecialidad.style.setProperty('display', 'flex', 'important');

    // 7. Desplegar la ventana limpia
    modalForm.classList.add('active');
};

async function editarUsuario(cedula) {
    esEdicion = true;
    try {
        const res = await fetch(`${RUTA_CONTROLADOR}?action=ver&cedula=${cedula}`);
        const u = await res.json();

        if (!u || (!u.ci && !u.cedula)) {
            window.avisar("No se encontraron datos para el usuario seleccionado.");
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
        window.avisar("Error al obtener los datos del usuario.");
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
            window.avisar(data.message || 'Operación realizada con éxito.');
            cerrarModal('modalFormularioUsuario');
            form.reset();
            cargarUsuarios();
        } else {
            window.avisar(data.message || 'Ocurrió un error al guardar los datos.');
        }
    } catch (error) {
        console.error("Error al guardar:", error);
        window.avisar('Error de conexión con el servidor.');
    }
}

async function eliminarUsuario(cedula) {
    const confirmado = await window.confirmar('¿Desea eliminar el usuario seleccionado?', {
        titulo: 'Eliminar usuario',
        peligro: true
    });

    if (confirmado) {
        try {
            const res = await fetch(`${RUTA_CONTROLADOR}?action=eliminar&cedula=${cedula}`);
            const data = await res.json();
            if (data.success) {
                window.avisar("Usuario eliminado correctamente.");
                cargarUsuarios();
            } else {
                window.avisar(data.message || 'No se pudo eliminar el usuario.');
            }
        } catch (error) {
            console.error("Error al eliminar usuario:", error);
            window.avisar("Error de conexión al intentar eliminar el usuario.");
        }
    }
}


/* ==========================================================================
   5. MÓDULO PRODUCTOS / MATERIALES (ADMINISTRADOR)
   ========================================================================== */
async function abrirModalGestionProductos() {
    const modal = document.getElementById('modalGestionProductos');
    if (modal) modal.classList.add('active');
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
            const estiloDato = esBaja ? 'opacity: 0.55; color: #6c757d;' : '';

            const etiquetaEstado = badgeEstadoMaterial(p.estado);
            const detalleMotivo = (esBaja && p.motivo_baja) 
                ? `<br><small style="color: #6c757d; font-style: italic; display: block; margin-top: 4px;">Motivo: ${p.motivo_baja}</small>` 
                : '';

            const btnHistorial = `<button class="btn-modal" style="background-color: #0284c7; color: white;" onclick="verHistorialProducto(${p.id_material})">Historial</button>`;

            let botonesAccion = '';
            if (!esBaja) {
                botonesAccion = `
                    <button class="btn-modal btn-editar" onclick="editarProducto(${p.id_material})">Editar</button>
                    <button class="btn-modal" style="background-color: #d97706; color: white;" onclick="abrirModalCambiarEstado(${p.id_material}, '${p.estado}')">Cambiar Estado</button>
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
        window.avisar("Ocurrió un error al obtener el historial del producto.");
    }
}

/* ==========================================================================
   6. CAMBIAR ESTADO DE MATERIAL CON SELECTOR DESPLEGABLE
   ========================================================================== */
async function abrirModalCambiarEstado(idMaterial, estadoActual) {
    const estados = [
        { valor: 'DISPONIBLE', etiqueta: 'DISPONIBLE' },
        { valor: 'EN_MANTENIMIENTO', etiqueta: 'EN MANTENIMIENTO' },
        { valor: 'DADO_DE_BAJA', etiqueta: 'DADO DE BAJA' }
    ];

    const opcionesHTML = estados.map(e => 
        `<option value="${e.valor}" ${e.valor === estadoActual ? 'selected' : ''}>${e.etiqueta}</option>`
    ).join('');

    const contenidoModal = `
        <div style="display: flex; flex-direction: column; gap: 14px; text-align: left;">
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <label for="modal_nuevo_estado" style="font-weight: 600; font-size: 13px; color: #334155;">Nuevo Estado</label>
                <select id="modal_nuevo_estado" class="cl-dlg-input" style="margin: 0; padding: 8px 10px; cursor: pointer;">
                    ${opcionesHTML}
                </select>
            </div>
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <label for="modal_motivo_estado" style="font-weight: 600; font-size: 13px; color: #334155;">Motivo del cambio</label>
                <textarea id="modal_motivo_estado" class="cl-dlg-input" rows="3" placeholder="Escriba la razón o detalle del cambio..." style="margin: 0; padding: 8px 10px; resize: vertical; min-height: 70px; font-family: inherit;"></textarea>
            </div>
        </div>
    `;

    const resultado = await new Promise(resolve => {
        const overlay = document.createElement('div');
        overlay.className = 'cl-dlg-overlay';

        const dlg = document.createElement('div');
        dlg.className = 'cl-dlg cl-dlg-pregunta';
        dlg.setAttribute('role', 'dialog');
        dlg.style.maxWidth = '420px';
        dlg.style.width = '90%';

        dlg.innerHTML = `
            <div class="cl-dlg-head">
                <span class="cl-dlg-icono">?</span>
                <span>Cambiar Estado del Material #${idMaterial}</span>
            </div>
            <div class="cl-dlg-body" style="padding: 18px 20px;">
                ${contenidoModal}
            </div>
            <div class="cl-dlg-foot">
                <button type="button" class="cl-dlg-btn cl-sec" id="btnCancelarEstado">Cancelar</button>
                <button type="button" class="cl-dlg-btn" id="btnGuardarEstado">Guardar Cambio</button>
            </div>
        `;

        overlay.appendChild(dlg);
        document.body.appendChild(overlay);

        const selectEstado = dlg.querySelector('#modal_nuevo_estado');
        const txtMotivo = dlg.querySelector('#modal_motivo_estado');
        const btnGuardar = dlg.querySelector('#btnGuardarEstado');
        const btnCancelar = dlg.querySelector('#btnCancelarEstado');

        txtMotivo.focus();

        const cerrar = (val) => {
            overlay.remove();
            resolve(val);
        };

        btnCancelar.addEventListener('click', () => cerrar(null));
        btnGuardar.addEventListener('click', () => {
            const nuevoEstado = selectEstado.value;
            const motivo = txtMotivo.value.trim();

            if (!motivo) {
                window.avisar("Debe ingresar un motivo para registrar el cambio de estado.");
                return;
            }

            cerrar({ estado: nuevoEstado, motivo: motivo });
        });
    });

    if (resultado) {
        await guardarNuevoEstadoMaterial(idMaterial, resultado.estado, resultado.motivo);
    }
}

async function guardarNuevoEstadoMaterial(idMaterial, nuevoEstado, motivo) {
    try {
        const res = await fetch(`${RUTA_CONTROLADOR_PRODUCTOS}?action=cambiarEstado`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                id_material: idMaterial, 
                nuevo_estado: nuevoEstado,
                estado: nuevoEstado,
                motivo_baja: motivo,
                motivo: motivo
            })
        });

        const textoRespuesta = await res.text();
        let resultado;
        try {
            resultado = JSON.parse(textoRespuesta);
        } catch (e) {
            console.error("Respuesta no válida del servidor:", textoRespuesta);
            window.avisar("Error del servidor (PHP):\n" + textoRespuesta.substring(0, 300));
            return;
        }

        if (resultado.success) {
            window.avisar(resultado.message || "Estado del material actualizado con éxito.");
            cargarProductos();
        } else {
            window.avisar(resultado.message || resultado.mensaje || "No se pudo actualizar el estado.");
        }
    } catch (error) {
        console.error("Error al actualizar estado:", error);
        window.avisar("Error de conexión al procesar el cambio de estado.");
    }
}

async function reactivarMaterial(idMaterial) {
    const confirmado = await window.confirmar("¿Estás seguro de que deseas reactivar este producto y restablecerlo como DISPONIBLE?", {
        titulo: 'Reactivar Producto'
    });

    if (!confirmado) return;

    try {
        const res = await fetch(`${RUTA_CONTROLADOR_PRODUCTOS}?action=reactivar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_material: idMaterial })
        });

        const resultado = await res.json();

        if (resultado.success) {
            window.avisar(resultado.message || "Producto reactivado con éxito.");
            cargarProductos();
        } else {
            window.avisar(resultado.message || "No se pudo reactivar el producto.");
        }
    } catch (error) {
        console.error("Error al reactivar:", error);
        window.avisar("Error de conexión al intentar reactivar el producto.");
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
            window.avisar("No se encontraron datos para este producto.");
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
        window.avisar("Ocurrió un error al obtener la información del producto.");
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
            window.avisar("Error del servidor (PHP):\n" + textoRespuesta.substring(0, 300));
            return;
        }

        if (data.success) {
            window.avisar(esEdicionProducto ? '¡Producto actualizado con éxito!' : '¡Producto guardado con éxito!');
            cerrarModal('modalFormularioProducto');
            form.reset();
            cargarProductos();
        } else {
            window.avisar(data.message || 'El producto ya existe o no se pudo guardar.');
        }
    } catch (error) {
        console.error("Error de red/petición:", error);
        window.avisar('Error de conexión con el servidor: ' + error.message);
    }
}


/* ==========================================================================
   7. REPORTES E IMPRESIÓN
   ========================================================================== */
async function abrirModalStockStock() {
    try {
        const res = await fetch(`${RUTA_CONTROLADOR_PRODUCTOS}?action=reporte_stock`);
        const r = await res.json();
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
    } catch (e) {
        console.error("Error al obtener reporte de stock:", e);
    }
}

function imprimirHistorialGeneral() {
    const contenidoTabla = document.getElementById('bodyHistorialGeneral')?.innerHTML;
    
    if (!contenidoTabla || contenidoTabla.includes('Todavía no hay movimientos')) {
        window.avisar('No hay datos para imprimir.');
        return;
    }

    const ventanaImpresion = window.open('', '_blank', 'width=900,height=650');
    
    ventanaImpresion.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Historial General de Movimientos - CeiLab</title>
            <style>
                body { font-family: Arial, sans-serif; color: #1e293b; margin: 20px; }
                h2 { text-align: center; color: #0f172a; margin-bottom: 5px; }
                .subtitulo { text-align: center; font-size: 13px; color: #64748b; margin-bottom: 25px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                th, td { border: 1px solid #cbd5e1; padding: 8px 10px; font-size: 12px; text-align: left; vertical-align: middle; }
                th { background-color: #f1f5f9; color: #334155; font-weight: bold; }
                small { color: #64748b; }
                code { background: #f8fafc; padding: 2px 4px; border-radius: 4px; font-family: monospace; }
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


/* ==========================================================================
   8. INTERFAZ Y TEMA
   ========================================================================== */
window.toggleDropdown = function(event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById('userDropdown') || document.querySelector('.dropdown-menu');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
};

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('userDropdown') || document.querySelector('.dropdown-menu');
    const userBtn = document.querySelector('.user-profile-btn') || document.querySelector('.user-dropdown-btn');
    
    if (dropdown && dropdown.classList.contains('show')) {
        if (!userBtn || !userBtn.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    }
});

window.toggleTheme = function() {
    const isDark = document.body.classList.toggle('dark-mode');
    localStorage.setItem('ceilab-theme', isDark ? 'dark' : 'light');
};

document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('ceilab-theme');
    const themeToggle = document.getElementById('themeToggle') || document.querySelector('.switch input');
    
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        if (themeToggle) themeToggle.checked = true;
    }

    if (themeToggle) {
        themeToggle.addEventListener('change', window.toggleTheme);
    }
});

window.abrirModalVerPerfil = function(event) {
    if (event) event.preventDefault();
    
    const dropdown = document.getElementById('menuContent');
    if (dropdown) dropdown.classList.remove('show');

    const modal = document.getElementById('modalVerPerfil');
    if (modal) modal.classList.add('active');
};

window.abrirEditarPerfilPropio = function() {
    cerrarModal('modalVerPerfil');

    const modalForm = document.getElementById('modalFormularioUsuario');
    if (!modalForm) return;
    
    modalForm.classList.add('active');

    const titulo = document.getElementById('tituloFormUsuario');
    if (titulo) titulo.textContent = 'Editar Mi Perfil';

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
    if (inputPass) inputPass.value = '';
    if (helpPass) helpPass.style.display = 'block';

    const campoCedula = inputCedula ? inputCedula.closest('.form-group') : null;
    const campoRol = document.getElementById('rol') ? document.getElementById('rol').closest('.form-group') : null;
    const campoEspecialidad = document.getElementById('especialidad') ? document.getElementById('especialidad').closest('.form-group') : null;

    if (campoCedula) campoCedula.style.setProperty('display', 'none', 'important');
    if (campoRol) campoRol.style.setProperty('display', 'none', 'important');
    if (campoEspecialidad) campoEspecialidad.style.setProperty('display', 'none', 'important');
};

/* ==========================================================================
   MODAL DE NUEVO PRÉSTAMO ADMINISTRATIVO (INDEPENDIENTE)
   ========================================================================== */

const PedidoAdminState = {
    clienteCedula: '',
    materia: '',
    horas: 2,
    carrito: []
};

async function adminAbrirModalNuevoPrestamo() {
    const modal = document.getElementById('modalPrestamoAdmin');
    if (!modal) return;

    // Resetear Estado
    PedidoAdminState.clienteCedula = '';
    PedidoAdminState.materia = '';
    PedidoAdminState.horas = 2;
    PedidoAdminState.carrito = [];

    // Mostrar Modal
    modal.style.display = 'flex';

    // Cargar datos
    await adminCargarUsuariosDropdown();
    await adminCargarCatalogo();
    adminRenderizarResumen();
}

function adminCerrarModal() {
    const modal = document.getElementById('modalPrestamoAdmin');
    if (modal) modal.style.display = 'none';
}

async function adminCargarUsuariosDropdown() {
    const select = document.getElementById('admin_select_cliente');
    if (!select) return;

    try {
        const ruta = typeof RUTA_CONTROLADOR !== 'undefined' ? RUTA_CONTROLADOR : '../CRUD_Usuarios/Controlador/Controlador_Usuarios.php';
        const res = await fetch(`${ruta}?action=listar`);
        const usuarios = await res.json();

        const activos = Array.isArray(usuarios) ? usuarios.filter(u => String(u.estado_cuenta || '').toUpperCase() !== 'INACTIVA') : [];

        select.innerHTML = `<option value="">-- Seleccionar cliente --</option>` + 
            activos.map(u => `<option value="${u.ci || u.cedula}">${u.nombre} ${u.apellido} (${u.ci || u.cedula})</option>`).join('');
    } catch (e) {
        select.innerHTML = `<option value="">Error al cargar usuarios</option>`;
    }
}

async function adminCargarCatalogo() {
    const contenedor = document.getElementById('admin-catalogo-productos');
    if (!contenedor) return;

    try {
        const rutaProd = typeof RUTA_CONTROLADOR_PRODUCTOS !== 'undefined' ? RUTA_CONTROLADOR_PRODUCTOS : '../CRUD_Productos/Controlador/Controlador_Productos.php';
        const res = await fetch(`${rutaProd}?action=catalogo`);
        const productos = await res.json();

        if (!Array.isArray(productos) || productos.length === 0) {
            contenedor.innerHTML = '<p>No hay productos disponibles.</p>';
            return;
        }

        contenedor.innerHTML = productos.map(p => {
            const libres = parseInt(p.libres) || 0;
            return `
                <div class="card-producto-admin">
                    <h5>${p.nombre}</h5>
                    <p><strong>Categoría:</strong> ${p.categoria || '-'}</p>
                    <p><strong>Disponibles:</strong> ${libres} de ${p.total}</p>
                    ${libres > 0 ? `
                        <div style="display:flex; gap:6px; margin-top:8px;">
                            <input type="number" id="cant_adm_${p.nombre}" value="1" min="1" max="${libres}" style="width:50px; text-align:center;">
                            <button type="button" style="flex:1; background:#0284c7; color:#fff; border:none; border-radius:4px; cursor:pointer;" onclick="adminAgregarItem('${p.nombre}', ${libres})">
                                + Agregar
                            </button>
                        </div>
                    ` : '<button disabled style="width:100%; margin-top:8px;">Sin Stock</button>'}
                </div>
            `;
        }).join('');
    } catch (e) {
        contenedor.innerHTML = '<p>Error al cargar el catálogo.</p>';
    }
}

function adminAgregarItem(nombre, max) {
    const input = document.getElementById(`cant_adm_${nombre}`);
    const cantidad = input ? parseInt(input.value) || 1 : 1;

    const existe = PedidoAdminState.carrito.find(i => i.nombre === nombre);
    if (existe) {
        if (existe.cantidad + cantidad > max) {
            window.avisar(`Stock insuficiente para ${nombre}`);
            return;
        }
        existe.cantidad += cantidad;
    } else {
        PedidoAdminState.carrito.push({ nombre, cantidad });
    }
    adminRenderizarResumen();
}

function adminQuitarItem(nombre) {
    PedidoAdminState.carrito = PedidoAdminState.carrito.filter(i => i.nombre !== nombre);
    adminRenderizarResumen();
}

function adminRenderizarResumen() {
    const contenedor = document.getElementById('admin-resumen-items');
    if (!contenedor) return;

    if (PedidoAdminState.carrito.length === 0) {
        contenedor.innerHTML = '<p class="texto-vacio" style="font-style:italic; color:#64748b; margin:0;">Todavía no agregaste materiales.</p>';
        return;
    }

    contenedor.innerHTML = PedidoAdminState.carrito.map(item => `
        <span style="display:inline-flex; align-items:center; gap:6px; background:#e0f2fe; padding:4px 10px; border-radius:12px; font-size:12px; margin-right:6px; margin-bottom:4px;">
            <b>${item.cantidad}x</b> ${item.nombre}
            <button type="button" onclick="adminQuitarItem('${item.nombre}')" style="border:none; background:none; color:#ef4444; font-weight:bold; cursor:pointer;">&times;</button>
        </span>
    `).join('');
}
async function adminProcesarSolicitudDirecta() {
    const ciCliente = document.getElementById('admin_select_cliente')?.value;

    if (!ciCliente) {
        window.avisar("Debes seleccionar un cliente obligatoriamente.");
        return;
    }

    if (!PedidoAdminState.carrito || PedidoAdminState.carrito.length === 0) {
        window.avisar("Debes agregar al menos un material al pedido.");
        return;
    }

    const payload = {
        ci_cliente: ciCliente,
        materia: PedidoAdminState.materia || 'General',
        horas: PedidoAdminState.horas || 2,
        auto_aprobar: true,
        items: PedidoAdminState.carrito
    };

    // Ruta exacta comprobada con la estructura del proyecto
    const rutaPHP = '../CRUD_Prestamos/Controlador/Controlador.php?action=guardarDesdeAdmin';

    try {
        const res = await fetch(rutaPHP, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const textoRespuesta = await res.text();

        // Si la respuesta no es un JSON válido (por ejemplo, error de sintaxis en PHP)
        let data;
        try {
            data = JSON.parse(textoRespuesta);
        } catch (jsonErr) {
            console.error("Respuesta no es JSON:", textoRespuesta);
            window.avisar("El controlador PHP devolvió un error. Revisa la consola.");
            return;
        }

        if (data.success) {
            window.avisar("Préstamo registrado y aprobado exitosamente.");
            adminCerrarModal();
            if (typeof cargarPrestamos === 'function') cargarPrestamos();
        } else {
            window.avisar(data.message || "Error al procesar el préstamo.");
        }
    } catch (e) {
        console.error("Error en la petición fetch:", e);
        window.avisar("Error de conexión al guardar el préstamo.");
    }
}
window.adminAbrirModalNuevoPrestamo = adminAbrirModalNuevoPrestamo;
window.adminCerrarModal = adminCerrarModal;