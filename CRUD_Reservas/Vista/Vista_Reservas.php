<?php
/**
 * Vista_Reservas.php  (v7)
 * Se incluye en Paginas_Principales/Pagina_Principal.php, DESPUÉS de Vista_Prestamos.php
 * (reutiliza sus ayudas: esc, chipEstado, pedirJSON, postJSON y las clases CSS de los modales).
 *
 *  Cliente:       <Hacer Reserva>      -> elegir fecha, horario y equipos, ver qué está ocupado, y «Mis reservas»
 *  Administrador: <Gestionar Reservas> -> lista por estado/fecha, aprobar / rechazar / cancelar
 *
 * La vista solo muestra y envía: los choques de horario y los estados los decide el servidor
 * (Modelo_Reservas.php). El aviso de <ocupado> en pantalla es una ayuda, no la validación.
 */
$esAdminReservas = (($_SESSION['Rol'] ?? '') === 'ADMINISTRADOR');
?>
<style>
    .res-fila { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:12px; }
    .modal-content .res-fila label { display:flex !important; flex-direction:column; width:auto; font-size:13px; gap:4px; }
    .modal-content .res-fila input { width:auto; }
    .res-equipo { display:flex; align-items:center; gap:6px; border:1px solid #ddd; border-radius:6px; padding:6px 10px; min-width:230px; }
    .res-equipo.ocupado { background:#fdecea; border-color:#e5a5a5; }
    .res-equipo small { color:#8a1c1c; }
    .res-aviso { font-size:12px; color:#8a5a00; }
    .res-acciones button { margin:0 4px 4px 0; }
</style>

<?php if (!$esAdminReservas): ?>
<!-- ======================================================================
     MODAL: HACER RESERVA (CLIENTE)
     ====================================================================== -->
<div class="modal-overlay" id="modalHacerReserva">
    <div class="modal-content" style="max-width:860px;">
        <div class="modal-header">
            <h3>Hacer una reserva</h3>
            <button class="modal-close" onclick="cerrarModal('modalHacerReserva')">&times;</button>
        </div>

        <form id="formReserva" onsubmit="enviarReserva(event)">
            <div class="res-fila">
                <label>Fecha <input type="date" id="resFecha" required onchange="resCargarOcupacion()"></label>
                <label>Desde <input type="time" id="resIni" required oninput="resMarcarOcupados()"></label>
                <label>Hasta <input type="time" id="resFin" required oninput="resMarcarOcupados()"></label>
            </div>
            <p style="margin:6px 0;"><strong>Equipos</strong> <span class="res-aviso">(los marcados en rojo ya están reservados en ese horario)</span></p>
            <div class="series-grid" id="resEquipos"></div>
            <div style="margin-top:14px;">
                <button type="submit" class="btn-guardar">Solicitar reserva</button>
            </div>
        </form>

        <h4 style="margin-top:22px;">Mis reservas</h4>
        <div class="table-responsive">
            <table class="tabla-modal">
                <thead><tr><th>#</th><th>Estado</th><th>Fecha</th><th>Horario</th><th>Equipos</th><th>Detalle</th><th></th></tr></thead>
                <tbody id="resMisReservas"></tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<!-- ======================================================================
     MODAL: GESTIÓN DE RESERVAS (ADMINISTRADOR)
     ====================================================================== -->
<div class="modal-overlay" id="modalGestionReservas">
    <div class="modal-content" style="max-width:1000px;">
        <div class="modal-header">
            <h3>Gestión de Reservas</h3>
            <button class="modal-close" onclick="cerrarModal('modalGestionReservas')">&times;</button>
        </div>
        <div class="res-fila" style="align-items:flex-end;">
            <div class="filtros-estado" id="resFiltros" style="margin:0;"></div>
            <label>Fecha <input type="date" id="resFiltroFecha" onchange="resListarAdmin()"></label>
            <button type="button" onclick="document.getElementById('resFiltroFecha').value=''; resListarAdmin();">Quitar fecha</button>
        </div>
        <div class="table-responsive">
            <table class="tabla-modal">
                <thead><tr><th>#</th><th>Estado</th><th>Cliente</th><th>Fecha</th><th>Horario</th><th>Equipos</th><th>Acciones</th></tr></thead>
                <tbody id="resBodyAdmin"></tbody>
            </table>
        </div>
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

<?php if (!$esAdminReservas): ?>
    /* =====================================================================
       CLIENTE
       ===================================================================== */
    let resOcupadas = [];   // franjas aprobadas del día elegido (todas las equipos)

    async function abrirModalHacerReserva() {
        const manana = new Date(); manana.setDate(manana.getDate() + 1);
        const campo = document.getElementById('resFecha');
        campo.min = resFechaLocal(new Date());
        if (!campo.value) campo.value = resFechaLocal(manana);
        document.getElementById('modalHacerReserva').classList.add('active');

        const eq = await pedirJSON(`${RUTA_CONTROLADOR_RESERVAS}?action=equipos`);
        if (!eq) return;
        document.getElementById('resEquipos').innerHTML = eq.data.map(e => `
            <label class="res-equipo" id="resEq${e.id_material}">
                <input type="checkbox" name="resEquipo" value="${esc(e.id_material)}">
                <span><strong>${esc(e.id_serie)}</strong> · ${esc(e.nombre)}<br><small></small></span>
            </label>`).join('') || '<em>No hay equipos reservables.</em>';
        await resCargarOcupacion();
        resListarMias();
    }

    /** Trae las reservas aprobadas del día elegido y marca los equipos ocupados. */
    async function resCargarOcupacion() {
        const dia = document.getElementById('resFecha').value;
        if (!dia) { resOcupadas = []; resMarcarOcupados(); return; }
        const r = await pedirJSON(`${RUTA_CONTROLADOR_RESERVAS}?action=calendario&desde=${dia}&hasta=${dia}`);
        resOcupadas = r ? r.data : [];
        resMarcarOcupados();
    }

    /** Ayuda visual: mismo criterio que el servidor (empieza antes de que termine y termina después de que empiece). */
    function resMarcarOcupados() {
        const ini = document.getElementById('resIni').value, fin = document.getElementById('resFin').value;
        document.querySelectorAll('#resEquipos .res-equipo').forEach(box => {
            const id = box.id.replace('resEq', '');
            const delDia = resOcupadas.filter(o => String(o.id_material) === id);
            const choca = (ini && fin) ? delDia.filter(o => resHora(o.hora_inicio) < fin && resHora(o.hora_fin) > ini) : [];
            box.classList.toggle('ocupado', choca.length > 0);
            box.querySelector('small').textContent = delDia.length
                ? 'Ocupado: ' + delDia.map(o => resHora(o.hora_inicio) + '–' + resHora(o.hora_fin)).join(', ')
                : 'Libre todo el día';
        });
    }

    async function enviarReserva(ev) {
        ev.preventDefault();
        const equipos = [...document.querySelectorAll('input[name="resEquipo"]:checked')].map(c => c.value);
        const r = await postJSON(RUTA_CONTROLADOR_RESERVAS, {
            action: 'solicitar', equipos,
            fecha: document.getElementById('resFecha').value,
            hora_inicio: document.getElementById('resIni').value,
            hora_fin: document.getElementById('resFin').value
        });
        if (!r) return;
        alert('Solicitud enviada (n.º ' + r.data.id_solicitud + '). Queda pendiente hasta que un administrador la apruebe.');
        document.querySelectorAll('input[name="resEquipo"]').forEach(c => c.checked = false);
        resListarMias();
    }

    async function resListarMias() {
        const r = await pedirJSON(`${RUTA_CONTROLADOR_RESERVAS}?action=mis_reservas`);
        if (!r) return;
        document.getElementById('resMisReservas').innerHTML = r.data.map(x => `
            <tr>
                <td>${esc(x.id_solicitud)}</td>
                <td>${chipEstado(x.estado)}</td>
                <td>${esc(x.fecha)}</td>
                <td>${esc(resHora(x.hora_inicio))}–${esc(resHora(x.hora_fin))}</td>
                <td>${esc(x.equipos)}</td>
                <td>${esc(x.motivo_rechazo || '')}</td>
                <td>${['PENDIENTE', 'APROBADO'].includes(x.estado)
                    ? `<button type="button" onclick="resCancelar(${Number(x.id_solicitud)}, true)">Cancelar</button>` : ''}</td>
            </tr>`).join('') || '<tr><td colspan="7" style="text-align:center;">Todavía no hiciste reservas.</td></tr>';
    }
<?php else: ?>
    /* =====================================================================
       ADMINISTRADOR
       ===================================================================== */
    let resFiltroEstado = 'PENDIENTE';

    async function abrirModalGestionReservas(estado) {
        resFiltroEstado = estado === undefined ? 'PENDIENTE' : estado;
        document.getElementById('modalGestionReservas').classList.add('active');
        resListarAdmin();
    }

    async function resListarAdmin() {
        const estados = [['', 'Todas'], ['PENDIENTE', 'Pendientes'], ['APROBADO', 'Aprobadas'], ['RECHAZADO', 'Rechazadas'], ['CANCELADO', 'Canceladas']];
        document.getElementById('resFiltros').innerHTML = estados.map(([v, t]) =>
            `<button type="button" class="${v === resFiltroEstado ? 'activo' : ''}" onclick="abrirModalGestionReservas('${v}')">${t}</button>`).join('');

        const q = new URLSearchParams({ action: 'listar' });
        if (resFiltroEstado) q.set('estado', resFiltroEstado);
        const dia = document.getElementById('resFiltroFecha').value;
        if (dia) q.set('fecha', dia);

        const r = await pedirJSON(`${RUTA_CONTROLADOR_RESERVAS}?${q}`);
        if (!r) return;
        document.getElementById('resBodyAdmin').innerHTML = r.data.map(x => {
            const pendiente = x.estado === 'PENDIENTE';
            const aviso = (pendiente && x.en_conflicto)
                ? `<br><small class="res-aviso">⚠ Choca con la reserva ${x.conflictos.map(c => '#' + esc(c)).join(', ')}</small>` : '';
            return `<tr>
                <td>${esc(x.id_solicitud)}</td>
                <td>${chipEstado(x.estado)}${x.motivo_rechazo ? `<br><small>${esc(x.motivo_rechazo)}</small>` : ''}</td>
                <td>${esc(x.cliente)}<br><small>${esc(x.ci_cliente)}</small></td>
                <td>${esc(x.fecha)}</td>
                <td>${esc(resHora(x.hora_inicio))}–${esc(resHora(x.hora_fin))}</td>
                <td>${esc(x.equipos)}${aviso}</td>
                <td class="res-acciones">
                    ${pendiente ? `<button type="button" onclick="resAprobar(${Number(x.id_solicitud)})">Aprobar</button>
                                   <button type="button" onclick="resRechazar(${Number(x.id_solicitud)})">Rechazar</button>` : ''}
                    ${['PENDIENTE', 'APROBADO'].includes(x.estado)
                        ? `<button type="button" onclick="resCancelar(${Number(x.id_solicitud)}, false)">Cancelar</button>` : ''}
                </td>
            </tr>`;
        }).join('') || '<tr><td colspan="7" style="text-align:center;">No hay reservas con ese filtro.</td></tr>';
    }

    async function resAprobar(id) {
        if (await postJSON(RUTA_CONTROLADOR_RESERVAS, { action: 'aprobar', id_solicitud: id })) resListarAdmin();
    }
    async function resRechazar(id) {
        const motivo = prompt('Motivo del rechazo (obligatorio):');
        if (motivo === null) return;
        if (await postJSON(RUTA_CONTROLADOR_RESERVAS, { action: 'rechazar', id_solicitud: id, motivo })) resListarAdmin();
    }
<?php endif; ?>

    async function resCancelar(id, esCliente) {
        if (!confirm('¿Cancelar la reserva n.º ' + id + '?')) return;
        if (await postJSON(RUTA_CONTROLADOR_RESERVAS, { action: 'cancelar', id_solicitud: id })) {
            if (esCliente) { resListarMias(); resCargarOcupacion(); }
            else resListarAdmin();
        }
    }
</script>
