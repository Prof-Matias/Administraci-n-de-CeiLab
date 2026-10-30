<?php
/**
 * Vista_Prestamos.php  (v7)
 * Se incluye al final de Paginas_Principales/Pagina_Principal.php.
 *
 *  Administrador: «Gestionar Préstamos» / «Ver Solicitudes»
 *    lista por estado -> detalle -> aprobar / rechazar / entregar (series) / devolver
 *  Cliente: pedido desde el catálogo y «Mis préstamos»
 *
 * Toda la validación de estados la hace el servidor (Config/Estados.php);
 * esta vista solo muestra lo que el servidor permite y sus mensajes.
 */
$esAdminPrestamos = (($_SESSION['Rol'] ?? '') === 'ADMINISTRADOR');
?>
<style>
    .estado { display:inline-block; padding:2px 8px; border-radius:10px; font-size:12px; font-weight:bold; white-space:nowrap; }
    .estado-DISPONIBLE, .estado-APROBADO { background:#e3f4e8; color:#1e6b35; }
    .estado-PRESTADO, .estado-ACTIVO     { background:#e4ecfb; color:#1f4b99; }
    .estado-EN_MANTENIMIENTO, .estado-PENDIENTE { background:#fff3d6; color:#8a5a00; }
    .estado-DADO_DE_BAJA, .estado-RECHAZADO, .estado-CANCELADO { background:#f1f1f1; color:#666; }
    .estado-FINALIZADO { background:#ece6f7; color:#553c8b; }
    .filtros-estado button { margin:0 4px 8px 0; }
    .filtros-estado button.activo { outline:2px solid #333; }
    .lista-errores { margin:8px 0; padding:8px 12px 8px 28px; background:#fdecea; color:#8a1c1c; border-radius:6px; }
    .bloque-linea { border:1px solid #ddd; border-radius:6px; padding:10px; margin:8px 0; }
    .series-grid { display:flex; flex-wrap:wrap; gap:6px 14px; margin-top:6px; }
    .series-grid label { font-family:monospace; }
    .carrito { border-top:1px solid #ddd; margin-top:16px; padding-top:12px; }
    .carrito li { margin:4px 0; }
</style>

<?php if ($esAdminPrestamos): ?>
<!-- ======================================================================
     MODAL: GESTIÓN DE PRÉSTAMOS (ADMINISTRADOR)
     ====================================================================== -->
<div class="modal-overlay" id="modalGestionPrestamos">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Gestión de Préstamos</h3>
            <button class="modal-close" onclick="cerrarModal('modalGestionPrestamos')">&times;</button>
        </div>
        <div class="filtros-estado" id="filtrosPrestamos"></div>
        <div class="table-responsive">
            <table class="tabla-modal">
                <thead>
                    <tr><th>#</th><th>Estado</th><th>Cliente</th><th>Pedido</th><th>Materia</th><th>Horas</th><th>Solicitado</th><th>Acciones</th></tr>
                </thead>
                <tbody id="bodyTablaPrestamos"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: DETALLE / ACCIONES DE UN PRÉSTAMO -->
<div class="modal-overlay" id="modalDetallePrestamo" style="z-index:1050;">
    <div class="modal-content" style="max-width:820px;">
        <div class="modal-header">
            <h3 id="tituloDetallePrestamo">Préstamo</h3>
            <button class="modal-close" onclick="cerrarModal('modalDetallePrestamo')">&times;</button>
        </div>
        <div id="cuerpoDetallePrestamo"></div>
    </div>
</div>

<!-- MODAL: HISTORIAL DE ESTADOS DE UN MATERIAL -->
<div class="modal-overlay" id="modalHistorialMaterial" style="z-index:1100;">
    <div class="modal-content" style="max-width:820px;">
        <div class="modal-header">
            <h3 id="tituloHistorialMaterial">Historial de estados</h3>
            <button class="modal-close" onclick="cerrarModal('modalHistorialMaterial')">&times;</button>
        </div>
        <div class="table-responsive">
            <table class="tabla-modal">
                <thead><tr><th>Fecha</th><th>Evento</th><th>De</th><th>A</th><th>Préstamo</th><th>Responsable</th><th>Motivo</th></tr></thead>
                <tbody id="bodyHistorialMaterial"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: HISTORIAL GENERAL DE MOVIMIENTOS (todos los materiales) -->
<div class="modal-overlay" id="modalHistorialGeneral">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Historial general de movimientos</h3>
            <button class="modal-close" onclick="cerrarModal('modalHistorialGeneral')">&times;</button>
        </div>
        <div class="modal-footer-acciones">
        <button type="button" class="btn-imprimir-modal" onclick="imprimirHistorialGeneral()">Imprimir / Guardar PDF</button>
        </div>
        <div class="table-responsive">
            <table class="tabla-modal">
                <thead>
    <tr>
        <th>Fecha</th>
        <th>Producto</th>
        <th>Evento</th>
        <th>De</th>
        <th>A</th>
        <th>Préstamo</th>
        <th>Solicitante</th>
        <th>Admin Responsable</th>
        <th>Motivo</th>
    </tr>
</thead>
                <tbody id="bodyHistorialGeneral"></tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<!-- ======================================================================
     MODAL: MIS PRÉSTAMOS (CLIENTE)
     ====================================================================== -->
<div class="modal-overlay" id="modalMisPrestamos">
    <div class="modal-content" style="max-width:1000px;">
        <div class="modal-header">
            <h3>Mis préstamos</h3>
            <button class="modal-close" onclick="cerrarModal('modalMisPrestamos')">&times;</button>
        </div>
        <div class="table-responsive">
            <table class="tabla-modal">
                <thead><tr><th>#</th><th>Estado</th><th>Pedido</th><th>Materia</th><th>Solicitado</th><th>Entregado</th><th>Devuelto</th><th></th></tr></thead>
                <tbody id="bodyMisPrestamos"></tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    const RUTA_CONTROLADOR_PRESTAMOS = '../CRUD_Prestamos/Controlador/Controlador.php';

    const ETIQUETA_ESTADO = {
        DISPONIBLE: 'Disponible', PRESTADO: 'Prestado', EN_MANTENIMIENTO: 'En mantenimiento', DADO_DE_BAJA: 'Dado de baja',
        PENDIENTE: 'Pendiente', APROBADO: 'Aprobado', RECHAZADO: 'Rechazado', ACTIVO: 'Activo (entregado)',
        FINALIZADO: 'Finalizado', CANCELADO: 'Cancelado'
    };

    /** Escapa texto para insertarlo en HTML. */
    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
    function chipEstado(e) { return `<span class="estado estado-${esc(e)}">${esc(ETIQUETA_ESTADO[e] || e)}</span>`; }
    function fecha(f) { return f ? esc(String(f).slice(0, 16).replace('T', ' ')) : '—'; }

    /**
     * fetch + JSON. Si el servidor rechaza, muestra el mensaje y la lista de errores
     * (por ejemplo: «La serie CEIB-0001 está prestada (solicitud #2)») y devuelve null.
     */
    async function pedirJSON(url, opciones = {}) {
        try {
            const res = await fetch(url, opciones);
            const texto = await res.text();
            let data;
            try { data = JSON.parse(texto); } catch (e) {
                alert('Respuesta inesperada del servidor:\n' + texto.substring(0, 300));
                return null;
            }
            if (data && data.success === false) {
                const lista = (data.errores || []).map(e => '• ' + e).join('\n');
                alert((data.message || 'No se pudo completar la operación.') + (lista ? '\n\n' + lista : ''));
                return null;
            }
            return data;
        } catch (err) {
            alert('Error de conexión con el servidor: ' + err.message);
            return null;
        }
    }
    function postJSON(url, cuerpo) {
        return pedirJSON(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(cuerpo) });
    }

<?php if ($esAdminPrestamos): ?>
    /* =====================================================================
       ADMINISTRADOR
       ===================================================================== */
    let filtroPrestamos = 'PENDIENTE';

    async function abrirModalGestionPrestamos(estado) {
        if (estado !== undefined) filtroPrestamos = estado;
        document.getElementById('modalGestionPrestamos').classList.add('active');
        await cargarPrestamos();
    }

    async function cargarPrestamos() {
        const estados = ['', 'PENDIENTE', 'APROBADO', 'ACTIVO', 'FINALIZADO', 'RECHAZADO', 'CANCELADO'];
        document.getElementById('filtrosPrestamos').innerHTML = estados.map(e =>
            `<button class="btn-modal ${e === filtroPrestamos ? 'activo' : ''}" onclick="filtroPrestamos='${e}'; cargarPrestamos()">${e ? esc(ETIQUETA_ESTADO[e]) : 'Todos'}</button>`
        ).join('');

        const r = await pedirJSON(`${RUTA_CONTROLADOR_PRESTAMOS}?action=listar&estado=${encodeURIComponent(filtroPrestamos)}`);
        const tbody = document.getElementById('bodyTablaPrestamos');
        if (!r) return;
        if (!r.data.length) {
            tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;">No hay préstamos en este estado.</td></tr>`;
            return;
        }
        tbody.innerHTML = r.data.map(p => `
            <tr>
                <td>${p.id_solicitud}</td>
                <td>${chipEstado(p.estado)}</td>
                <td>${esc(p.cliente)}<br><small>${esc(p.ci_cliente)}</small></td>
                <td>${esc(p.pedido || '—')}</td>
                <td>${esc(p.materia || '—')}</td>
                <td>${esc(p.horas_solicitadas || '—')}</td>
                <td>${fecha(p.fecha_solicitud)}</td>
                <td style="white-space:nowrap;"><button class="btn-modal btn-editar" onclick="abrirDetallePrestamo(${p.id_solicitud})">${accionPrincipal(p.estado)}</button>${botonEditarPrestamo(p)}</td>
            </tr>`).join('');
    }

    function accionPrincipal(estado) {
        return { PENDIENTE: 'Revisar', APROBADO: 'Entregar', ACTIVO: 'Registrar devolución' }[estado] || 'Ver';
    }

    async function abrirDetallePrestamo(id) {
        const r = await pedirJSON(`${RUTA_CONTROLADOR_PRESTAMOS}?action=unidades_entrega&id=${id}`);
        if (!r) return;
        const p = r.data;
        document.getElementById('tituloDetallePrestamo').innerHTML = `Préstamo #${p.id_solicitud} ${chipEstado(p.estado)}`;

        let html = `
            <p><b>Cliente:</b> ${esc(p.cliente)} (${esc(p.ci_cliente)}) · <b>Materia:</b> ${esc(p.materia || '—')} · <b>Horas:</b> ${esc(p.horas_solicitadas)}</p>
            <p><b>Solicitado:</b> ${fecha(p.fecha_solicitud)} · <b>Validado:</b> ${fecha(p.fecha_validacion)} ·
               <b>Entregado:</b> ${fecha(p.fecha_entrega)} · <b>Devuelto:</b> ${fecha(p.fecha_devolucion)}</p>
            ${p.motivo_rechazo ? `<p><b>Motivo del rechazo:</b> ${esc(p.motivo_rechazo)}</p>` : ''}`;

        if (p.estado === 'PENDIENTE')      html += vistaRevisar(p);
        else if (p.estado === 'APROBADO')  html += vistaEntregar(p);
        else if (p.estado === 'ACTIVO')    html += vistaDevolver(p);
        else                               html += vistaPedido(p) + vistaEntregado(p);

        document.getElementById('cuerpoDetallePrestamo').innerHTML = html;
        document.getElementById('modalDetallePrestamo').classList.add('active');
    }

    function textoDisponibilidad(d) {
        if (!d.existe) return '<span style="color:#a00">ya no existe en el inventario</span>';
        return `${d.libres} libres · ${d.disponibles} disponibles · ${d.comprometidas} comprometidas · ${d.prestadas} prestadas · ${d.en_mantenimiento} en mantenimiento`;
    }

    function vistaPedido(p) {
        return `<h4>Pedido</h4><ul>${p.pedido.map(l =>
            `<li><b>${l.cantidad} × ${esc(l.nombre_material)}</b> <small>(${textoDisponibilidad(l.disponibilidad)})</small></li>`).join('')}</ul>`;
    }

    function vistaEntregado(p) {
        if (!p.entregado.length) return '';
        return `<h4>Entregado</h4><table class="tabla-modal"><thead><tr><th>Material</th><th>Serie</th><th>Cant.</th><th>Devuelto</th><th>Quedó</th><th>Observación</th></tr></thead><tbody>
            ${p.entregado.map(e => `<tr><td>${esc(e.nombre)}</td><td>${esc(e.id_serie || '—')}</td><td>${e.cantidad}</td>
                <td>${e.fecha_devolucion ? fecha(e.fecha_devolucion) + (e.id_serie ? '' : ` (${e.cantidad_devuelta})`) : 'Pendiente'}</td>
                <td>${e.estado_devolucion ? chipEstado(e.estado_devolucion) : '—'}</td><td>${esc(e.observacion || '')}</td></tr>`).join('')}
            </tbody></table>`;
    }

    function vistaRevisar(p) {
        const alcanza = p.pedido.every(l => l.disponibilidad.libres >= l.cantidad);
        return vistaPedido(p) + `
            ${alcanza ? '' : '<p class="lista-errores" style="padding-left:12px;">No hay unidades libres para todo el pedido: el servidor no permitirá aprobarlo.</p>'}
            <div style="display:flex; gap:10px; margin-top:12px;">
                <button class="btn-modal btn-crear" onclick="aprobarPrestamo(${p.id_solicitud})">Aprobar</button>
                <button class="btn-modal btn-eliminar" onclick="rechazarPrestamo(${p.id_solicitud})">Rechazar</button>
                <button class="btn-modal btn-cancelar" onclick="cancelarPrestamoAdmin(${p.id_solicitud})">Cancelar</button>
            </div>`;
    }

    function vistaEntregar(p) {
        const lineas = p.pedido.map(l => {
            const d = l.disponibilidad;
            if (d.tipo_control === 'CANTIDAD') {
                const stock = l.unidades.length ? l.unidades[0].cantidad_disponible : 0;
                return `<div class="bloque-linea"><b>${l.cantidad} × ${esc(l.nombre_material)}</b> — consumible por cantidad, quedan ${stock}. Se descuenta al entregar.</div>`;
            }
            const opciones = l.unidades.map(u =>
                `<label><input type="checkbox" class="serie-entrega" data-nombre="${esc(l.nombre_material)}" value="${esc(u.id_serie)}"> ${esc(u.id_serie)}</label>`).join('');
            return `<div class="bloque-linea"><b>${l.cantidad} × ${esc(l.nombre_material)}</b> — marcá ${l.cantidad} de las ${l.unidades.length} unidades disponibles:
                    <div class="series-grid">${opciones || '<i>No hay unidades disponibles.</i>'}</div></div>`;
        }).join('');
        return `<h4>Entrega</h4>${lineas}
            <div class="form-group">
                <label>O escaneá / escribí las series (una por línea o separadas por coma):</label>
                <textarea id="seriesEscaneadas" rows="2" placeholder="CEIB-0003, CEIB-0004"></textarea>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn-modal btn-crear" onclick="entregarPrestamo(${p.id_solicitud})">Confirmar entrega</button>
                ${botonEditarPrestamo(p)}
                <button class="btn-modal btn-cancelar" onclick="cancelarPrestamoAdmin(${p.id_solicitud})">Cancelar préstamo</button>
            </div>`;
    }

    function vistaDevolver(p) {
        const pendientes = p.entregado.filter(e => !e.fecha_devolucion);
        const filas = pendientes.map(e => e.tipo_control === 'SERIE' ? `
            <tr data-id="${e.id_material}" data-tipo="SERIE">
                <td><input type="checkbox" class="dev-sel" checked></td>
                <td>${esc(e.nombre)}</td><td><code>${esc(e.id_serie)}</code></td><td>1</td>
                <td><select class="dev-estado"><option value="DISPONIBLE">Vuelve bien → Disponible</option><option value="EN_MANTENIMIENTO">Con daños → Mantenimiento</option></select></td>
                <td><input type="text" class="dev-obs" placeholder="Obligatoria si hay daños"></td>
            </tr>` : `
            <tr data-id="${e.id_material}" data-tipo="CANTIDAD">
                <td><input type="checkbox" class="dev-sel" checked></td>
                <td>${esc(e.nombre)}</td><td>—</td><td>${e.cantidad}</td>
                <td>Devueltos: <input type="number" class="dev-cant" min="0" max="${e.cantidad}" value="${e.cantidad}" style="width:70px;"></td>
                <td><input type="text" class="dev-obs" placeholder="Obligatoria si faltan"></td>
            </tr>`).join('');
        return vistaEntregado(p) + `
            <h4>Registrar devolución</h4>
            <table class="tabla-modal"><thead><tr><th></th><th>Material</th><th>Serie</th><th>Cant.</th><th>Estado al volver</th><th>Observación</th></tr></thead>
            <tbody id="bodyDevolucion">${filas}</tbody></table>
            <p><small>Se puede devolver una parte; el préstamo se finaliza cuando vuelve todo.</small></p>
            <div style="display:flex; gap:10px;">
                <button class="btn-modal btn-crear" onclick="devolverPrestamo(${p.id_solicitud})">Registrar devolución</button>
                ${botonEditarPrestamo(p)}
            </div>`;
    }

    async function aprobarPrestamo(id) {
        if (!confirm(`¿Aprobar el préstamo #${id}?`)) return;
        if (await postJSON(`${RUTA_CONTROLADOR_PRESTAMOS}?action=aprobar`, { id_solicitud: id })) refrescarPrestamo(id);
    }
    async function rechazarPrestamo(id) {
        const motivo = prompt('Motivo del rechazo:');
        if (!motivo) return;
        if (await postJSON(`${RUTA_CONTROLADOR_PRESTAMOS}?action=rechazar`, { id_solicitud: id, motivo })) refrescarPrestamo(id);
    }
    async function cancelarPrestamoAdmin(id) {
        if (!confirm(`¿Cancelar el préstamo #${id}?`)) return;
        if (await postJSON(`${RUTA_CONTROLADOR_PRESTAMOS}?action=cancelar`, { id_solicitud: id })) refrescarPrestamo(id);
    }
    async function entregarPrestamo(id) {
        const marcadas = [...document.querySelectorAll('.serie-entrega:checked')].map(c => c.value);
        const escritas = document.getElementById('seriesEscaneadas').value.split(/[\s,;]+/).filter(Boolean);
        const series = [...new Set([...marcadas, ...escritas.map(s => s.toUpperCase())])];
        if (await postJSON(`${RUTA_CONTROLADOR_PRESTAMOS}?action=entregar`, { id_solicitud: id, series })) {
            alert('Entrega registrada: las unidades quedaron en estado «Prestado».');
            refrescarPrestamo(id);
        }
    }
    async function devolverPrestamo(id) {
        const items = [...document.querySelectorAll('#bodyDevolucion tr')]
            .filter(tr => tr.querySelector('.dev-sel').checked)
            .map(tr => tr.dataset.tipo === 'SERIE'
                ? { id_material: +tr.dataset.id, estado: tr.querySelector('.dev-estado').value, observacion: tr.querySelector('.dev-obs').value }
                : { id_material: +tr.dataset.id, cantidad_devuelta: +tr.querySelector('.dev-cant').value, observacion: tr.querySelector('.dev-obs').value });
        if (!items.length) { alert('Marcá al menos un material.'); return; }
        const r = await postJSON(`${RUTA_CONTROLADOR_PRESTAMOS}?action=devolver`, { id_solicitud: id, items });
        if (r) {
            alert(r.data.finalizado ? 'Devolución completa: el préstamo quedó finalizado y archivado.'
                                    : `Devolución parcial registrada. Faltan ${r.data.pendientes} materiales.`);
            refrescarPrestamo(id);
        }
    }
    /* ---------- Editar préstamo (solo APROBADO y ACTIVO) ---------- */
    function botonEditarPrestamo(p) {
        if (p.estado !== 'APROBADO' && p.estado !== 'ACTIVO') return '';
        return `<button class="btn-modal" style="background-color:#0284c7; color:#fff;" onclick="abrirEditarPrestamo(${p.id_solicitud})">Editar préstamo</button>`;
    }

    async function abrirEditarPrestamo(id) {
        const r = await pedirJSON(`${RUTA_CONTROLADOR_PRESTAMOS}?action=unidades_entrega&id=${id}`);
        if (!r) return;
        const p = r.data;
        if (p.estado !== 'APROBADO' && p.estado !== 'ACTIVO') {
            alert('Solo se pueden editar préstamos aprobados o activos.');
            return;
        }
        document.getElementById('tituloDetallePrestamo').innerHTML = `Editar préstamo #${p.id_solicitud} ${chipEstado(p.estado)}`;
        document.getElementById('cuerpoDetallePrestamo').innerHTML = `
            <p><b>Cliente:</b> ${esc(p.cliente)} (${esc(p.ci_cliente)}) · <b>Materia:</b> ${esc(p.materia || '—')} · <b>Horas:</b> ${esc(p.horas_solicitadas)}</p>
            ${p.estado === 'APROBADO' ? vistaEditarAprobado(p) : vistaEditarActivo(p)}
            <div style="display:flex; gap:10px; margin-top:12px;">
                <button class="btn-modal btn-crear" onclick="guardarEdicionPrestamo(${p.id_solicitud}, '${p.estado}')">Guardar cambios</button>
                <button class="btn-modal btn-cancelar" onclick="abrirDetallePrestamo(${p.id_solicitud})">Volver sin guardar</button>
            </div>`;
        document.getElementById('modalDetallePrestamo').classList.add('active');
    }

    // Aprobado: todavía no se entregó nada, se cambia la cantidad pedida de cada línea
    function vistaEditarAprobado(p) {
        const filas = p.pedido.map(l => `
            <tr><td><b>${esc(l.nombre_material)}</b></td><td>${l.cantidad}</td>
                <td><input type="number" class="edit-cant" data-nombre="${esc(l.nombre_material)}" min="1" value="${l.cantidad}" style="width:80px;"></td>
                <td><small>${textoDisponibilidad(l.disponibilidad)}</small></td></tr>`).join('');
        return `<h4>Cantidades del pedido</h4>
            <table class="tabla-modal"><thead><tr><th>Material</th><th>Pedido</th><th>Nueva cantidad</th><th>Disponibilidad</th></tr></thead><tbody>${filas}</tbody></table>
            <p><small>La cantidad nueva no puede superar las unidades libres: el servidor lo valida al guardar.</small></p>`;
    }

    // Activo: ya hay unidades entregadas. Equipos: se marcan los que vuelven y los que se suman. Consumibles: cantidad nueva.
    function vistaEditarActivo(p) {
        const abiertas = p.entregado.filter(e => !e.fecha_devolucion);
        const nombres = [...new Set(abiertas.map(e => e.nombre))];
        const bloques = nombres.map(nombre => {
            const filas  = abiertas.filter(e => e.nombre === nombre);
            const linea  = p.pedido.find(l => l.nombre_material === nombre);
            const libres = linea ? linea.disponibilidad.libres : 0;
            if (filas[0].tipo_control === 'CANTIDAD') {
                return `<div class="bloque-linea"><b>${esc(nombre)}</b> — consumible por cantidad. Entregados hoy: <b>${filas[0].cantidad}</b>
                    <small>(se pueden sumar hasta ${libres} más)</small><br>
                    Nueva cantidad: <input type="number" class="edit-cant-activo" data-nombre="${esc(nombre)}" min="1" value="${filas[0].cantidad}" style="width:80px;"></div>`;
            }
            const quitar = filas.map(e =>
                `<label><input type="checkbox" class="edit-quitar" value="${e.id_material}"> ${esc(e.id_serie)}</label>`).join('');
            const sumar = ((linea && linea.unidades) || []).map(u =>
                `<label><input type="checkbox" class="edit-agregar" value="${esc(u.id_serie)}"> ${esc(u.id_serie)}</label>`).join('');
            return `<div class="bloque-linea"><b>${esc(nombre)}</b> — equipos entregados hoy: <b>${filas.length}</b>
                <div><small>Marcá los que vuelven al CeiLab (quedan disponibles):</small><div class="series-grid">${quitar}</div></div>
                <div style="margin-top:6px;"><small>Marcá los que se suman (${libres} libres):</small>
                    <div class="series-grid">${sumar || '<i>No hay unidades disponibles.</i>'}</div></div></div>`;
        }).join('');
        return `<h4>Ajustar lo entregado</h4>${bloques || '<p><i>No hay nada pendiente de devolución.</i></p>'}
            <p><small>Cada producto tiene que quedar con al menos 1 unidad. Para devolver un producto entero usá «Registrar devolución».</small></p>`;
    }

    async function guardarEdicionPrestamo(id, estado) {
        let cuerpo;
        if (estado === 'APROBADO') {
            cuerpo = { id_solicitud: id, pedido: [...document.querySelectorAll('.edit-cant')]
                .map(i => ({ nombre: i.dataset.nombre, cantidad: parseInt(i.value, 10) || 0 })) };
        } else {
            cuerpo = {
                id_solicitud: id,
                cantidades: [...document.querySelectorAll('.edit-cant-activo')]
                    .map(i => ({ nombre: i.dataset.nombre, cantidad: parseInt(i.value, 10) || 0 })),
                quitar: [...document.querySelectorAll('.edit-quitar:checked')].map(c => +c.value),
                agregar_series: [...document.querySelectorAll('.edit-agregar:checked')].map(c => c.value)
            };
        }
        if (!confirm(`¿Guardar los cambios del préstamo #${id}?`)) return;
        if (await postJSON(`${RUTA_CONTROLADOR_PRESTAMOS}?action=editar`, cuerpo)) {
            alert('Préstamo actualizado.');
            refrescarPrestamo(id);
        }
    }

    function refrescarPrestamo(id) {
        abrirDetallePrestamo(id);
        cargarPrestamos();
        if (document.getElementById('modalGestionProductos')?.classList.contains('active')) cargarProductos();
    }

    async function verHistorialMaterial(id, titulo) {
        const r = await pedirJSON(`${RUTA_CONTROLADOR_PRESTAMOS}?action=historial_material&id_material=${id}`);
        if (!r) return;
        document.getElementById('tituloHistorialMaterial').innerText = 'Historial de estados — ' + titulo;
        document.getElementById('bodyHistorialMaterial').innerHTML = r.data.length ? r.data.map(h => `
            <tr><td>${fecha(h.fecha)}</td><td>${esc(h.evento)}</td>
                <td>${h.estado_anterior ? chipEstado(h.estado_anterior) : '—'}</td><td>${chipEstado(h.estado_nuevo)}</td>
                <td>${h.id_solicitud ? '#' + h.id_solicitud : '—'}</td><td>${esc(h.responsable || '—')}</td><td>${esc(h.motivo || '')}</td></tr>`).join('')
            : '<tr><td colspan="7" style="text-align:center;">Sin cambios registrados.</td></tr>';
        document.getElementById('modalHistorialMaterial').classList.add('active');
    }

    async function abrirHistorialGeneral() {
        const r = await pedirJSON(`${RUTA_CONTROLADOR_PRESTAMOS}?action=historial_general`);
        if (!r) return;

        const lista = r.data || r.historial || [];

        document.getElementById('bodyHistorialGeneral').innerHTML = lista.length ? lista.map(h => {
            // Formatear el solicitante (Nombre y Cédula)
            let solicitanteHtml = '—';
            if (h.solicitante_ci) {
                solicitanteHtml = `${esc(h.solicitante_nombre)}<br><small style="color: #64748b;">CI: ${esc(h.solicitante_ci)}</small>`;
            }

            return `
                <tr>
                    <td>${fecha(h.fecha)}</td>
                    <td><b>${esc(h.material)}</b>${h.id_serie ? '<br><small style="color: #64748b;">' + esc(h.id_serie) + '</small>' : '<br><small style="color: #64748b;">Por cantidad</small>'}</td>
                    <td><code>${esc(h.evento)}</code></td>
                    <td>${h.estado_anterior ? chipEstado(h.estado_anterior) : '—'}</td>
                    <td>${chipEstado(h.estado_nuevo)}</td>
                    <td>${h.id_solicitud ? '#' + h.id_solicitud : '—'}</td>
                    <td>${solicitanteHtml}</td>
                    <td>${esc(h.responsable || '—')}</td>
                    <td>${esc(h.motivo || '')}</td>
                </tr>`;
        }).join('') : '<tr><td colspan="9" style="text-align:center;">Todavía no hay movimientos registrados.</td></tr>';

        document.getElementById('modalHistorialGeneral').classList.add('active');
    }

<?php else: ?>
    /* =====================================================================
       CLIENTE
       ===================================================================== */
    const carritoPrestamo = {};   // nombre -> cantidad

    /* Si el cliente ya tiene un préstamo APROBADO o ACTIVO (entregado), no puede pedir otro.
       El servidor lo vuelve a comprobar en «solicitar»; esto solo avisa y deshabilita los botones. */
    let prestamoBloqueante = null;   // {id_solicitud, estado} o null

    function textoBloqueo() {
        const b = prestamoBloqueante;
        const situacion = b.estado === 'ACTIVO' ? 'entregado y todavía sin devolver' : 'aprobado y todavía sin entregar';
        return `Ya tenés el préstamo #${b.id_solicitud} ${situacion}. No podés solicitar otro hasta que se finalice o se cancele.`;
    }

    async function verificarPrestamoActivo() {
        const r = await pedirJSON(`${RUTA_CONTROLADOR_PRESTAMOS}?action=prestamo_bloqueante`);
        prestamoBloqueante = r ? r.data : null;
        const aviso = document.getElementById('avisoBloqueoPrestamo');
        if (!aviso) return;
        aviso.textContent = prestamoBloqueante ? textoBloqueo() + ' Podés consultar el catálogo.' : '';
        aviso.style.display = prestamoBloqueante ? 'block' : 'none';
    }

    function aplicarBloqueoCatalogo() {
        const bloqueado = !!prestamoBloqueante;
        document.querySelectorAll('#contenedor-productos-cliente input.cant-pedido, #contenedor-productos-cliente .btn-prestamo:not(.btn-sin-stock)')
            .forEach(el => { el.disabled = bloqueado; });
    }

    function agregarAlPedido(nombre, maximo) {
        if (prestamoBloqueante) { alert(textoBloqueo()); return; }
        const input = document.querySelector(`input.cant-pedido[data-nombre="${CSS.escape(nombre)}"]`);
        const cant = parseInt(input?.value, 10) || 1;
        const nuevo = (carritoPrestamo[nombre] || 0) + cant;
        if (nuevo > maximo) { alert(`De «${nombre}» hay ${maximo} libres ahora.`); return; }
        carritoPrestamo[nombre] = nuevo;
        dibujarCarrito();
    }
    function quitarDelPedido(nombre) { delete carritoPrestamo[nombre]; dibujarCarrito(); }

    function dibujarCarrito() {
        const cont = document.getElementById('carritoPrestamo');
        if (!cont) return;
        const items = Object.entries(carritoPrestamo);
        cont.innerHTML = !items.length ? '<p><i>Todavía no agregaste materiales.</i></p>' : `
            <ul>${items.map(([n, c]) => `<li>${c} × ${esc(n)} <button class="btn-modal btn-eliminar" style="padding:2px 8px;" onclick="quitarDelPedido(${esc(JSON.stringify(n))})">quitar</button></li>`).join('')}</ul>
            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:end;">
                <div class="form-group"><label>Materia</label><input type="text" id="pedidoMateria"></div>
                <div class="form-group"><label>Horas</label><input type="number" id="pedidoHoras" min="1" value="2" style="width:80px;"></div>
                <button class="btn-modal btn-crear" onclick="enviarSolicitudPrestamo()">Enviar solicitud</button>
            </div>`;
    }

    async function enviarSolicitudPrestamo() {
        if (prestamoBloqueante) { alert(textoBloqueo()); return; }
        const items = Object.entries(carritoPrestamo).map(([nombre, cantidad]) => ({ nombre, cantidad }));
        const r = await postJSON(`${RUTA_CONTROLADOR_PRESTAMOS}?action=solicitar`, {
            items, materia: document.getElementById('pedidoMateria').value, horas_solicitadas: document.getElementById('pedidoHoras').value
        });
        if (r) {
            alert(`Solicitud #${r.data.id_solicitud} enviada. Queda pendiente hasta que el administrador la apruebe.`);
            Object.keys(carritoPrestamo).forEach(k => delete carritoPrestamo[k]);
            dibujarCarrito();
            cargarProductosCliente();
        }
    }

    async function abrirModalMisPrestamos() {
        document.getElementById('modalMisPrestamos').classList.add('active');
        const r = await pedirJSON(`${RUTA_CONTROLADOR_PRESTAMOS}?action=mis_prestamos`);
        if (!r) return;
        document.getElementById('bodyMisPrestamos').innerHTML = r.data.length ? r.data.map(p => `
            <tr><td>${p.id_solicitud}</td><td>${chipEstado(p.estado)}${p.motivo_rechazo ? `<br><small>${esc(p.motivo_rechazo)}</small>` : ''}</td>
                <td>${esc(p.pedido || '—')}</td><td>${esc(p.materia || '—')}</td><td>${fecha(p.fecha_solicitud)}</td>
                <td>${fecha(p.fecha_entrega)}</td><td>${fecha(p.fecha_devolucion)}</td>
                <td>${['PENDIENTE', 'APROBADO'].includes(p.estado) ? `<button class="btn-modal btn-eliminar" onclick="cancelarMiPrestamo(${p.id_solicitud})">Cancelar</button>` : ''}</td></tr>`).join('')
            : '<tr><td colspan="8" style="text-align:center;">Todavía no hiciste solicitudes.</td></tr>';
    }

    async function cancelarMiPrestamo(id) {
        if (!confirm(`¿Cancelar la solicitud #${id}?`)) return;
        if (await postJSON(`${RUTA_CONTROLADOR_PRESTAMOS}?action=cancelar`, { id_solicitud: id })) abrirModalMisPrestamos();
    }
<?php endif; ?>
</script>