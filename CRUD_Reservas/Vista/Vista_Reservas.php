<?php
/**
 * Vista_Reservas.php  (v8)
 * Se incluye en Paginas_Principales/Pagina_Principal.php, DESPUÉS de Vista_Prestamos.php
 * (reutiliza sus ayudas: esc, chipEstado, pedirJSON, postJSON y las clases CSS de los modales).
 *
 *  Cliente:       <Hacer Reserva>      -> elegir fecha y franja, ver el stock de ESA franja por tipo de material,
 *                                         pedir cantidades, confirmar si hay stock parcial, anotarse en lista de espera,
 *                                         ver / modificar / cancelar «Mis reservas» y «Mi lista de espera»
 *  Administrador: <Gestionar Reservas> -> reservas por estado/fecha (aprobar, rechazar, modificar, cancelar, iniciar,
 *                                         finalizar), lista de espera e historial
 *
 * La vista solo muestra y envía: el stock, los choques y los estados los decide el servidor
 * (Modelo_Reservas.php + Config/Disponibilidad.php). Lo que se ve en pantalla es una ayuda, no la validación.
 */
$esAdminReservas = (($_SESSION['Rol'] ?? '') === 'ADMINISTRADOR');
?>
<style>
    .res-fila { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:12px; align-items:flex-end; }
    .modal-content .res-fila label { display:flex !important; flex-direction:column; width:auto; font-size:13px; gap:4px; }
    .modal-content .res-fila input { width:auto; }
    .res-aviso { font-size:12px; color:#8a5a00; }
    .res-acciones button { margin:0 4px 4px 0; }
    .res-cant { width:70px; }
    .res-sin { color:#8a1c1c; font-weight:600; }
    .res-panel { border:1px solid #e0b252; background:#fff8e6; border-radius:8px; padding:12px 14px; margin:12px 0; }
    .res-panel.error { border-color:#e5a5a5; background:#fdecea; }
    /* El texto del panel tiene color propio: en modo oscuro la app pone el texto en blanco y quedaba invisible sobre el fondo claro */
    .res-panel, .res-panel h4, .res-panel li, .res-panel strong, .res-panel small { color:#1f2937 !important; }
    .res-panel button { color:#1f2937 !important; background:#fff !important; border:1px solid #9ca3af !important; border-radius:6px; padding:5px 10px; cursor:pointer; }
    .res-panel button.btn-guardar { background:#10b981 !important; color:#fff !important; border-color:#0e9f6e !important; }
    body.dark-mode .res-panel { background:#3a2f12; border-color:#8a6d1f; }
    body.dark-mode .res-panel.error { background:#3b1c1c; border-color:#8a3a3a; }
    body.dark-mode .res-panel, body.dark-mode .res-panel h4, body.dark-mode .res-panel li,
    body.dark-mode .res-panel strong, body.dark-mode .res-panel small { color:#f3f4f6 !important; }
    body.dark-mode .res-aviso { color:#ffd374; }
    body.dark-mode .res-sin { color:#ff9b9b; }
    .res-panel h4 { margin:0 0 8px; }
    .res-panel ul { margin:6px 0 10px 18px; padding:0; }
    .res-panel button { margin:4px 6px 0 0; }
    .res-tabs button { margin-right:6px; }
    .res-tabs button.activo, #resFiltros button.activo { font-weight:700; text-decoration:underline; }
</style>

<?php if (!$esAdminReservas): ?>
<!-- ======================================================================
     MODAL: HACER RESERVA (CLIENTE)
     ====================================================================== -->
<div class="modal-overlay" id="modalHacerReserva">
    <div class="modal-content" style="max-width:920px;">
        <div class="modal-header">
            <h3 id="resTitulo">Hacer una reserva</h3>
            <button class="modal-close" onclick="cerrarModal('modalHacerReserva')">&times;</button>
        </div>

        <form id="formReserva" onsubmit="resEnviar(event)">
            <div class="res-fila">
                <label>Fecha <input type="date" id="resFecha" required onchange="resConsultarStock()"></label>
                <label>Desde <input type="time" id="resIni" required onchange="resConsultarStock()"></label>
                <label>Hasta <input type="time" id="resFin" required onchange="resConsultarStock()"></label>
            </div>
            <p class="res-aviso" style="margin:4px 0 8px;">
                El stock que ves es el de la franja elegida: descuenta lo reservado y lo prestado en esas horas.
                Elegí cuántas unidades querés de cada material.
            </p>
            <div class="table-responsive">
                <table class="tabla-modal">
                    <thead><tr><th>Material</th><th>En servicio</th><th>Libres</th><th>Reservadas</th><th>Prestadas</th><th>Se liberan</th><th>Cantidad</th></tr></thead>
                    <tbody id="resStock"><tr><td colspan="7" style="text-align:center;">Elegí fecha y horario.</td></tr></tbody>
                </table>
            </div>
            <div id="resPanel"></div>
            <div style="margin-top:14px;">
                <button type="submit" class="btn-guardar" id="resBtnEnviar">Solicitar reserva</button>
                <button type="button" id="resBtnCancelarEdicion" style="display:none;" onclick="resCancelarEdicion()">Cancelar edición</button>
            </div>
        </form>

        <h4 style="margin-top:22px;">Mis reservas</h4>
        <div class="table-responsive">
            <table class="tabla-modal">
                <thead><tr><th>#</th><th>Estado</th><th>Fecha</th><th>Horario</th><th>Materiales</th><th>Detalle</th><th></th></tr></thead>
                <tbody id="resMisReservas"></tbody>
            </table>
        </div>

        <h4 style="margin-top:22px;">Mi lista de espera</h4>
        <div class="table-responsive">
            <table class="tabla-modal">
                <thead><tr><th>Material</th><th>Cantidad</th><th>Fecha</th><th>Horario</th><th>Estado</th><th></th></tr></thead>
                <tbody id="resMiEspera"></tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<!-- ======================================================================
     MODAL: GESTIÓN DE RESERVAS (ADMINISTRADOR)
     ====================================================================== -->
<div class="modal-overlay" id="modalGestionReservas">
    <div class="modal-content" style="max-width:1080px;">
        <div class="modal-header">
            <h3>Gestión de Reservas</h3>
            <button class="modal-close" onclick="cerrarModal('modalGestionReservas')">&times;</button>
        </div>
        <div class="res-tabs res-fila">
            <button type="button" id="resTabReservas" onclick="resTab('reservas')">Reservas</button>
            <button type="button" id="resTabEspera" onclick="resTab('espera')">Lista de espera</button>
            <button type="button" id="resTabHistorial" onclick="resTab('historial')">Historial</button>
        </div>

        <div id="resSecReservas">
            <div class="res-fila">
                <div class="filtros-estado" id="resFiltros" style="margin:0;"></div>
                <label>Fecha <input type="date" id="resFiltroFecha" onchange="resListarAdmin()"></label>
                <button type="button" onclick="document.getElementById('resFiltroFecha').value=''; resListarAdmin();">Quitar fecha</button>
            </div>
            <div class="table-responsive">
                <table class="tabla-modal">
                    <thead><tr><th>#</th><th>Estado</th><th>Cliente</th><th>Fecha</th><th>Horario</th><th>Materiales</th><th>Acciones</th></tr></thead>
                    <tbody id="resBodyAdmin"></tbody>
                </table>
            </div>
        </div>

        <div id="resSecEspera" style="display:none;">
            <div class="res-fila"><button type="button" onclick="resProcesarEspera()">Atender lista de espera ahora</button></div>
            <div class="table-responsive">
                <table class="tabla-modal">
                    <thead><tr><th>#</th><th>Cliente</th><th>Material</th><th>Cant.</th><th>Fecha</th><th>Horario</th><th>Estado</th><th>Solicitud generada</th><th></th></tr></thead>
                    <tbody id="resBodyEspera"></tbody>
                </table>
            </div>
        </div>

        <div id="resSecHistorial" style="display:none;">
            <div class="res-fila">
                <label>Cédula <input type="text" id="resHistCi" maxlength="8" style="width:110px;" onchange="resListarHistorial()"></label>
                <label>Fecha <input type="date" id="resHistFecha" onchange="resListarHistorial()"></label>
                <button type="button" onclick="document.getElementById('resHistCi').value=''; document.getElementById('resHistFecha').value=''; resListarHistorial();">Limpiar</button>
            </div>
            <div class="table-responsive">
                <table class="tabla-modal">
                    <thead><tr><th>#</th><th>Cliente</th><th>Fecha</th><th>Horario</th><th>Detalle</th><th>Archivada</th></tr></thead>
                    <tbody id="resBodyHistorial"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php if ($esAdminReservas): ?>
<div class="modal-overlay" id="modalResModificar">
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3 id="resModTitulo">Modificar reserva</h3>
            <button class="modal-close" onclick="cerrarModal('modalResModificar')">&times;</button>
        </div>
        <form onsubmit="resGuardarModificacion(event)">
            <input type="hidden" id="resModId">
            <div class="res-fila">
                <label>Fecha <input type="date" id="resModFecha" required></label>
                <label>Desde <input type="time" id="resModIni" required></label>
                <label>Hasta <input type="time" id="resModFin" required></label>
            </div>
            <label style="display:flex; flex-direction:column; gap:4px; font-size:13px;">Materiales (uno por línea, «Nombre=cantidad»)
                <textarea id="resModItems" rows="5" required></textarea>
            </label>
            <div style="margin-top:12px;"><button type="submit" class="btn-guardar">Guardar cambios</button></div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    const RUTA_CONTROLADOR_RESERVAS = '../CRUD_Reservas/Controlador/Controlador_Reservas.php';

    /** Fecha local AAAA-MM-DD (no usa toISOString, que devuelve la fecha en UTC). */
    function resFechaLocal(d) {
        const p = n => String(n).padStart(2, '0');
        return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
    }
    function resHora(h) { return String(h || '').slice(0, 5); }

    /**
     * POST que NO muestra alert: devuelve {ok, status, json} para poder tratar el aviso de stock
     * (409 STOCK_PARCIAL / 422 SIN_STOCK) con un panel en vez de un mensaje simple.
     */
    async function resPost(cuerpo) {
        try {
            const res = await fetch(RUTA_CONTROLADOR_RESERVAS, {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(cuerpo)
            });
            const texto = await res.text();
            let json;
            try { json = JSON.parse(texto); } catch (e) { alert('Respuesta inesperada del servidor:\n' + texto.substring(0, 300)); return null; }
            return { ok: json.success !== false, status: res.status, json };
        } catch (err) {
            alert('Error de conexión con el servidor: ' + err.message);
            return null;
        }
    }
    function resAvisoError(json) {
        const lista = (json.errores || []).map(e => '• ' + e).join('\n');
        alert((json.message || 'No se pudo completar la operación.') + (lista ? '\n\n' + lista : ''));
    }

<?php if (!$esAdminReservas): ?>
    /* =====================================================================
       CLIENTE
       ===================================================================== */
    let resEditando = 0;          // id de la reserva que se está modificando (0 = reserva nueva)
    let resUltimoPedido = null;   // lo último que se intentó enviar (para reintentar con confirmación)

    async function abrirModalHacerReserva() {
        const manana = new Date(); manana.setDate(manana.getDate() + 1);
        const campo = document.getElementById('resFecha');
        campo.min = resFechaLocal(new Date());
        if (!campo.value) campo.value = resFechaLocal(manana);
        document.getElementById('modalHacerReserva').classList.add('active');
        resConsultarStock();
        resListarMias();
        resListarEspera();
    }

    /** Pide al servidor el stock de la franja elegida, por tipo de material. */
    async function resConsultarStock(conservar) {
        const f = document.getElementById('resFecha').value;
        const i = document.getElementById('resIni').value, h = document.getElementById('resFin').value;
        const cuerpo = document.getElementById('resStock');
        document.getElementById('resPanel').innerHTML = '';
        if (!f || !i || !h) { cuerpo.innerHTML = '<tr><td colspan="7" style="text-align:center;">Elegí fecha y horario.</td></tr>'; return; }
        // Conserva lo que ya se tipeó al cambiar la franja
        const previo = {};
        document.querySelectorAll('input.res-cant').forEach(c => { if (Number(c.value) > 0) previo[c.dataset.nombre] = c.value; });
        const datos = conservar || previo;

        const r = await pedirJSON(`${RUTA_CONTROLADOR_RESERVAS}?action=catalogo&fecha=${f}&hora_inicio=${i}&hora_fin=${h}`);
        if (!r) return;
        cuerpo.innerHTML = r.data.map(t => {
            const prox = (t.proximos || []).slice(0, 3).map(p => `${esc(p.hora)}: +${esc(p.cantidad)}`).join('<br>');
            return `<tr>
                <td><strong>${esc(t.nombre)}</strong><br><small>${esc(t.categoria || '')}</small></td>
                <td>${esc(t.capacidad)}${t.en_mantenimiento ? `<br><small>(${esc(t.en_mantenimiento)} en mantenimiento)</small>` : ''}</td>
                <td class="${t.libres === 0 ? 'res-sin' : ''}">${esc(t.libres)}</td>
                <td>${esc(t.reservadas)}</td>
                <td>${esc(t.prestadas)}</td>
                <td><small>${prox || '—'}</small></td>
                <td><input type="number" class="res-cant" min="0" max="${esc(t.capacidad)}" value="${esc(datos[t.nombre] || 0)}" data-nombre="${esc(t.nombre)}"></td>
            </tr>`;
        }).join('') || '<tr><td colspan="7" style="text-align:center;">No hay materiales reservables.</td></tr>';
    }

    function resItemsElegidos() {
        return [...document.querySelectorAll('input.res-cant')]
            .map(c => ({ nombre: c.dataset.nombre, cantidad: parseInt(c.value, 10) || 0 }))
            .filter(x => x.cantidad > 0);
    }

    function resPedidoActual(extra) {
        return Object.assign({
            fecha: document.getElementById('resFecha').value,
            hora_inicio: document.getElementById('resIni').value,
            hora_fin: document.getElementById('resFin').value,
            items: resItemsElegidos()
        }, extra || {});
    }

    async function resEnviar(ev, extra) {
        if (ev) ev.preventDefault();
        const pedido = resPedidoActual(extra);
        if (!pedido.items.length) { alert('Elegí la cantidad de al menos un material.'); return; }
        resUltimoPedido = pedido;
        const r = await resPost(Object.assign({ action: resEditando ? 'modificar' : 'solicitar' }, resEditando ? { id_solicitud: resEditando } : {}, pedido));
        if (!r) return;
        if (r.ok) { resExito(r.json.data); return; }
        if (r.json.codigo && r.json.detalle) { resMostrarAviso(r.json); return; }
        resAvisoError(r.json);
    }

    function resExito(d) {
        document.getElementById('resPanel').innerHTML = '';
        if (resEditando) {
            alert('Reserva modificada.');
            resCancelarEdicion();
        } else {
            const lineas = (d.items || []).map(i => `• ${i.nombre}: reservado ${i.reservado}` +
                (i.en_espera ? `, en lista de espera ${i.en_espera}` : '') + (i.sin_cubrir && !i.en_espera ? `, sin cubrir ${i.sin_cubrir}` : ''));
            alert((d.id_solicitud ? 'Solicitud enviada (n.º ' + d.id_solicitud + '). Queda pendiente hasta que un administrador la apruebe.\n\n'
                                  : 'No se creó ninguna reserva.\n\n') + lineas.join('\n') +
                  (d.espera && d.espera.length ? '\n\nTe anotamos en la lista de espera: si se libera stock se te crea una solicitud pendiente a tu nombre.' : ''));
        }
        document.querySelectorAll('input.res-cant').forEach(c => c.value = 0);
        resConsultarStock(); resListarMias(); resListarEspera();
    }

    /** Muestra el detalle del stock y los botones para confirmar (409) o anotarse en espera (422). */
    function resMostrarAviso(json) {
        const parcial = json.codigo === 'STOCK_PARCIAL';
        const items = (json.detalle && json.detalle.items) || [];
        const filas = items.map(i => {
            const prox = (i.proximos || []).map(p => `${esc(p.hora)} (+${esc(p.cantidad)}, ${esc(p.origen)})`).join(', ');
            return `<li><strong>${esc(i.nombre)}</strong>: pediste ${esc(i.solicitado)}, libres ${esc(i.libres)}, faltan ${esc(i.faltan)}.
                    Reservadas ${esc(i.reservadas)}, prestadas ${esc(i.prestadas)}${i.en_mantenimiento ? ', en mantenimiento ' + esc(i.en_mantenimiento) : ''}.
                    ${prox ? '<br>Se van liberando: ' + prox + '.' : ''}
                    ${i.completo_desde ? '<br>Tendrías todo disponible desde las <strong>' + esc(i.completo_desde) + '</strong>.' : ''}</li>`;
        }).join('');
        const titulo = parcial ? 'No alcanza el stock para todo lo pedido' : esc(json.message);
        const botones = parcial
            ? `<button type="button" class="btn-guardar" onclick="resEnviar(null, {aceptar_parcial: true})">Reservar solo lo disponible</button>
               <button type="button" onclick="resEnviar(null, {aceptar_parcial: true, anotar_espera: true})">Reservar lo disponible y anotarme en espera por el resto</button>`
            : `<button type="button" class="btn-guardar" onclick="resEnviar(null, {anotar_espera: true})">Anotarme en lista de espera</button>`;
        document.getElementById('resPanel').innerHTML = `
            <div class="res-panel ${parcial ? '' : 'error'}">
                <h4>${titulo}</h4>
                <ul>${filas}</ul>
                ${resEditando ? '' : botones}
                <button type="button" onclick="document.getElementById('resPanel').innerHTML=''">Cambiar mi pedido</button>
            </div>`;
    }

    async function resListarMias() {
        const r = await pedirJSON(`${RUTA_CONTROLADOR_RESERVAS}?action=mis_reservas`);
        if (!r) return;
        window.resMias = r.data;
        document.getElementById('resMisReservas').innerHTML = r.data.map(x => `
            <tr>
                <td>${esc(x.id_solicitud)}</td>
                <td>${chipEstado(x.estado)}</td>
                <td>${esc(x.fecha)}</td>
                <td>${esc(resHora(x.hora_inicio))}–${esc(resHora(x.hora_fin))}</td>
                <td>${esc(x.resumen)}${(x.asignadas || []).some(a => a.id_serie) ? `<br><small>${esc(x.asignadas.filter(a => a.id_serie).map(a => a.id_serie).join(', '))}</small>` : ''}</td>
                <td>${esc(x.motivo_rechazo || '')}</td>
                <td>${x.estado === 'PENDIENTE' ? `<button type="button" onclick="resModificar(${Number(x.id_solicitud)})">Modificar</button>` : ''}
                    ${['PENDIENTE', 'APROBADO'].includes(x.estado)
                        ? `<button type="button" onclick="resCancelar(${Number(x.id_solicitud)})">Cancelar</button>` : ''}</td>
            </tr>`).join('') || '<tr><td colspan="7" style="text-align:center;">Todavía no hiciste reservas.</td></tr>';
    }

    async function resListarEspera() {
        const r = await pedirJSON(`${RUTA_CONTROLADOR_RESERVAS}?action=mi_espera`);
        if (!r) return;
        document.getElementById('resMiEspera').innerHTML = r.data.map(x => `
            <tr>
                <td>${esc(x.nombre)}</td><td>${esc(x.cantidad)}</td><td>${esc(x.fecha)}</td>
                <td>${esc(resHora(x.hora_inicio))}–${esc(resHora(x.hora_fin))}</td>
                <td>${esc(x.estado)}${x.id_solicitud_generada ? ` (solicitud #${esc(x.id_solicitud_generada)})` : ''}</td>
                <td>${x.estado === 'ESPERANDO' ? `<button type="button" onclick="resCancelarEspera(${Number(x.id_espera)})">Salir de la espera</button>` : ''}</td>
            </tr>`).join('') || '<tr><td colspan="6" style="text-align:center;">No estás en ninguna lista de espera.</td></tr>';
    }

    /** Carga una reserva pendiente en el formulario para editarla. */
    function resModificar(id) {
        const x = (window.resMias || []).find(r => Number(r.id_solicitud) === id);
        if (!x) return;
        resEditando = id;
        document.getElementById('resFecha').value = x.fecha;
        document.getElementById('resIni').value = resHora(x.hora_inicio);
        document.getElementById('resFin').value = resHora(x.hora_fin);
        document.getElementById('resTitulo').textContent = 'Modificar la reserva n.º ' + id;
        document.getElementById('resBtnEnviar').textContent = 'Guardar cambios';
        document.getElementById('resBtnCancelarEdicion').style.display = '';
        const previo = {}; (x.items || []).forEach(i => previo[i.nombre] = i.cantidad);
        resConsultarStock(previo);
    }
    function resCancelarEdicion() {
        resEditando = 0;
        document.getElementById('resTitulo').textContent = 'Hacer una reserva';
        document.getElementById('resBtnEnviar').textContent = 'Solicitar reserva';
        document.getElementById('resBtnCancelarEdicion').style.display = 'none';
        document.querySelectorAll('input.res-cant').forEach(c => c.value = 0);
        document.getElementById('resPanel').innerHTML = '';
    }
    async function resCancelarEspera(id) {
        const r = await resPost({ action: 'cancelar_espera', id_espera: id });
        if (r && !r.ok) resAvisoError(r.json);
        resListarEspera();
    }
<?php else: ?>
    /* =====================================================================
       ADMINISTRADOR
       ===================================================================== */
    let resFiltroEstado = 'PENDIENTE';
    let resDatosAdmin = [];

    async function abrirModalGestionReservas(estado) {
        if (estado !== undefined) resFiltroEstado = estado;
        document.getElementById('modalGestionReservas').classList.add('active');
        resTab('reservas');
    }

    function resTab(cual) {
        ['reservas', 'espera', 'historial'].forEach(t => {
            document.getElementById('resSec' + t.charAt(0).toUpperCase() + t.slice(1)).style.display = (t === cual) ? '' : 'none';
            document.getElementById('resTab' + t.charAt(0).toUpperCase() + t.slice(1)).classList.toggle('activo', t === cual);
        });
        if (cual === 'reservas') resListarAdmin();
        if (cual === 'espera') resListarEsperaAdmin();
        if (cual === 'historial') resListarHistorial();
    }

    async function resListarAdmin() {
        const estados = [['', 'Todas'], ['PENDIENTE', 'Pendientes'], ['APROBADO', 'Aprobadas'], ['ACTIVO', 'En uso'],
                         ['FINALIZADO', 'Finalizadas'], ['RECHAZADO', 'Rechazadas'], ['CANCELADO', 'Canceladas']];
        document.getElementById('resFiltros').innerHTML = estados.map(([v, t]) =>
            `<button type="button" class="${v === resFiltroEstado ? 'activo' : ''}" onclick="resFiltroEstado='${v}'; resListarAdmin();">${t}</button>`).join('');

        const q = new URLSearchParams({ action: 'listar' });
        if (resFiltroEstado) q.set('estado', resFiltroEstado);
        const dia = document.getElementById('resFiltroFecha').value;
        if (dia) q.set('fecha', dia);

        const r = await pedirJSON(`${RUTA_CONTROLADOR_RESERVAS}?${q}`);
        if (!r) return;
        resDatosAdmin = r.data;
        document.getElementById('resBodyAdmin').innerHTML = r.data.map(x => {
            const pendiente = x.estado === 'PENDIENTE';
            const aviso = (pendiente && x.sin_stock && x.sin_stock.length)
                ? `<br><small class="res-aviso">⚠ Sin stock suficiente ahora: ${x.sin_stock.map(s => esc(s.nombre) + ' (pide ' + esc(s.pedidas) + ', libres ' + esc(s.libres) + ')').join(', ')}</small>` : '';
            const series = (x.asignadas || []).filter(a => a.id_serie).map(a => a.id_serie).join(', ');
            const puedeIniciar = x.estado === 'APROBADO';
            return `<tr>
                <td>${esc(x.id_solicitud)}</td>
                <td>${chipEstado(x.estado)}${x.motivo_rechazo ? `<br><small>${esc(x.motivo_rechazo)}</small>` : ''}</td>
                <td>${esc(x.cliente)}<br><small>${esc(x.ci_cliente)}</small></td>
                <td>${esc(x.fecha)}</td>
                <td>${esc(resHora(x.hora_inicio))}–${esc(resHora(x.hora_fin))}</td>
                <td>${esc(x.resumen)}${series ? `<br><small>${esc(series)}</small>` : ''}${aviso}</td>
                <td class="res-acciones">
                    ${pendiente ? `<button type="button" onclick="resAprobar(${Number(x.id_solicitud)})">Aprobar</button>
                                   <button type="button" onclick="resRechazar(${Number(x.id_solicitud)})">Rechazar</button>` : ''}
                    ${['PENDIENTE', 'APROBADO'].includes(x.estado) ? `<button type="button" onclick="resModificarAdmin(${Number(x.id_solicitud)})">Modificar</button>` : ''}
                    ${puedeIniciar ? `<button type="button" onclick="resAccion('iniciar', ${Number(x.id_solicitud)})">Iniciar uso</button>` : ''}
                    ${['APROBADO', 'ACTIVO'].includes(x.estado) ? `<button type="button" onclick="resAccion('finalizar', ${Number(x.id_solicitud)})">Finalizar</button>` : ''}
                    ${['PENDIENTE', 'APROBADO'].includes(x.estado) ? `<button type="button" onclick="resCancelar(${Number(x.id_solicitud)})">Cancelar</button>` : ''}
                </td>
            </tr>`;
        }).join('') || '<tr><td colspan="7" style="text-align:center;">No hay reservas con ese filtro.</td></tr>';
    }

    async function resAccion(accion, id) {
        const r = await resPost({ action: accion, id_solicitud: id });
        if (r && !r.ok) resAvisoError(r.json);
        resListarAdmin();
    }
    async function resAprobar(id) { await resAccion('aprobar', id); }
    async function resRechazar(id) {
        const motivo = window.pedirTexto ? await window.pedirTexto('Motivo del rechazo (obligatorio):') : prompt('Motivo del rechazo (obligatorio):');
        if (motivo === null || motivo === undefined) return;
        const r = await resPost({ action: 'rechazar', id_solicitud: id, motivo });
        if (r && !r.ok) resAvisoError(r.json);
        resListarAdmin();
    }

    /** Abre el formulario de modificación con los datos de la reserva. */
    function resModificarAdmin(id) {
        const x = resDatosAdmin.find(r => Number(r.id_solicitud) === id);
        if (!x) return;
        document.getElementById('resModId').value = id;
        document.getElementById('resModTitulo').textContent = 'Modificar la reserva n.º ' + id;
        document.getElementById('resModFecha').value = x.fecha;
        document.getElementById('resModIni').value = resHora(x.hora_inicio);
        document.getElementById('resModFin').value = resHora(x.hora_fin);
        document.getElementById('resModItems').value = (x.items || []).map(i => i.nombre + '=' + i.cantidad).join('\n');
        document.getElementById('modalResModificar').classList.add('active');
    }

    /** Un material por línea: «Nombre=cantidad». */
    async function resGuardarModificacion(ev) {
        ev.preventDefault();
        const items = document.getElementById('resModItems').value.split('\n').map(s => s.trim()).filter(Boolean).map(s => {
            const k = s.lastIndexOf('='); return { nombre: s.slice(0, k).trim(), cantidad: parseInt(s.slice(k + 1), 10) || 0 };
        });
        const r = await resPost({
            action: 'modificar', id_solicitud: Number(document.getElementById('resModId').value),
            fecha: document.getElementById('resModFecha').value,
            hora_inicio: document.getElementById('resModIni').value, hora_fin: document.getElementById('resModFin').value, items
        });
        if (r && !r.ok) { resAvisoError(r.json); return; }
        cerrarModal('modalResModificar');
        resListarAdmin();
    }

    async function resListarEsperaAdmin() {
        const r = await pedirJSON(`${RUTA_CONTROLADOR_RESERVAS}?action=lista_espera`);
        if (!r) return;
        document.getElementById('resBodyEspera').innerHTML = r.data.map(x => `
            <tr>
                <td>${esc(x.id_espera)}</td>
                <td>${esc(x.cliente)}<br><small>${esc(x.ci_cliente)}</small></td>
                <td>${esc(x.nombre)}</td><td>${esc(x.cantidad)}</td><td>${esc(x.fecha)}</td>
                <td>${esc(resHora(x.hora_inicio))}–${esc(resHora(x.hora_fin))}</td>
                <td>${esc(x.estado)}</td>
                <td>${x.id_solicitud_generada ? '#' + esc(x.id_solicitud_generada) : '—'}</td>
                <td>${x.estado === 'ESPERANDO' ? `<button type="button" onclick="resCancelarEsperaAdmin(${Number(x.id_espera)})">Quitar</button>` : ''}</td>
            </tr>`).join('') || '<tr><td colspan="9" style="text-align:center;">La lista de espera está vacía.</td></tr>';
    }
    async function resCancelarEsperaAdmin(id) {
        const r = await resPost({ action: 'cancelar_espera', id_espera: id });
        if (r && !r.ok) resAvisoError(r.json);
        resListarEsperaAdmin();
    }
    async function resProcesarEspera() {
        const r = await resPost({ action: 'procesar_espera' });
        if (r && r.ok) alert('Solicitudes generadas desde la lista de espera: ' + r.json.data.atendidas);
        resListarEsperaAdmin();
    }

    async function resListarHistorial() {
        const q = new URLSearchParams({ action: 'historial' });
        const ci = document.getElementById('resHistCi').value.trim(), f = document.getElementById('resHistFecha').value;
        if (ci) q.set('ci_cliente', ci);
        if (f) q.set('fecha', f);
        const r = await pedirJSON(`${RUTA_CONTROLADOR_RESERVAS}?${q}`);
        if (!r) return;
        document.getElementById('resBodyHistorial').innerHTML = r.data.map(x => `
            <tr>
                <td>${esc(x.id_solicitud)}</td>
                <td>${esc(x.cliente)}<br><small>${esc(x.ci_cliente)}</small></td>
                <td>${esc(x.fecha)}</td>
                <td>${esc(resHora(x.hora_inicio))}–${esc(resHora(x.hora_fin))}</td>
                <td>${esc(x.detalle)}</td>
                <td>${esc(String(x.fecha_archivo || '').slice(0, 16))}</td>
            </tr>`).join('') || '<tr><td colspan="6" style="text-align:center;">No hay reservas finalizadas con ese filtro.</td></tr>';
    }
<?php endif; ?>

    async function resCancelar(id) {
        const quiere = window.confirmar ? await window.confirmar('¿Cancelar la reserva n.º ' + id + '?', { peligro: true }) : confirm('¿Cancelar la reserva n.º ' + id + '?');
        if (!quiere) return;
        const r = await resPost({ action: 'cancelar', id_solicitud: id });
        if (r && !r.ok) resAvisoError(r.json);
<?php if (!$esAdminReservas): ?>
        resListarMias(); resConsultarStock(); resListarEspera();
<?php else: ?>
        resListarAdmin();
<?php endif; ?>
    }
</script>
