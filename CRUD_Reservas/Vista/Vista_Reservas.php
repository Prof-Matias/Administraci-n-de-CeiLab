<?php

$esAdminReservas = (($_SESSION['Rol'] ?? '') === 'ADMINISTRADOR');
$ciAdminReservas = (string)($_SESSION['CI'] ?? '');
$nombreAdminReservas = trim(($_SESSION['Nombre'] ?? '') . ' ' . ($_SESSION['Apellido'] ?? ''));
?>
<style>
    .res-fila { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:12px; align-items:flex-end; }
    .modal-content .res-fila label { display:flex !important; flex-direction:column; width:auto; font-size:13px; gap:4px; }
    .modal-content .res-fila input, .modal-content .res-fila select { width:auto; }
    .res-aviso { font-size:12px; color:#8a5a00; }
    .res-acciones button { margin:0 4px 4px 0; }
    .res-cant { width:70px; }
    .res-sin { color:#8a1c1c; font-weight:600; }
    .res-panel { border:1px solid #e0b252; background:#fff8e6; border-radius:8px; padding:12px 14px; margin:12px 0; }
    .res-panel.error { border-color:#e5a5a5; background:#fdecea; }
    .res-panel, .res-panel h4, .res-panel li, .res-panel strong, .res-panel small { color:#1f2937 !important; }
    body.dark-mode .res-panel { background:#3a2f12; border-color:#8a6d1f; }
    body.dark-mode .res-panel.error { background:#3b1c1c; border-color:#8a3a3a; }
    body.dark-mode .res-panel, body.dark-mode .res-panel h4, body.dark-mode .res-panel li,
    body.dark-mode .res-panel strong, body.dark-mode .res-panel small { color:#f3f4f6 !important; }
    body.dark-mode .res-aviso { color:#ffd374; }
    body.dark-mode .res-sin { color:#ff9b9b; }
    .res-panel h4 { margin:0 0 8px; }
    .res-panel ul { margin:6px 0 10px 18px; padding:0; }
    .res-panel .btn-modal { margin:4px 6px 0 0; }

    /* Pestañas y filtros con los mismos botones que Gestión de Préstamos */
    .res-tabs .btn-modal, #resFiltros .btn-modal { margin:0 4px 6px 0; }
    .res-tabs .btn-modal.activo, #resFiltros .btn-modal.activo { outline:2px solid #333; }
    body.dark-mode .res-tabs .btn-modal.activo, body.dark-mode #resFiltros .btn-modal.activo { outline-color:#e5e7eb; }
    .btn-modal.btn-res-azul { background-color:#0284c7 !important; color:#fff !important; }
    .btn-modal.btn-res-violeta { background-color:#7c3aed !important; color:#fff !important; }

    /* El formulario de la reserva es más ancho que los demás */
    #modalHacerReserva .modal-content { max-width:920px !important; width:94% !important; }
    #formReserva div.res-fila, #formReserva .res-fila { flex-direction:row !important; align-items:flex-end !important; }
    #formReserva .res-fila label { width:auto !important; }
    #formReserva .res-fila input[type="time"] {
        padding:8px 10px; border:1px solid var(--card-border, #cbd5e1); border-radius:8px;
        background:var(--card-bg, #fff); color:var(--text-main, #0f172a); font-size:0.88rem;
    }
    #formReserva .res-cliente-box { background:#f3f4f6; padding:10px 12px; border-radius:8px; }
    body.dark-mode #formReserva .res-cliente-box { background:#1f2937; }
    #formReserva .table-responsive { display:block !important; }
    #formReserva .res-botones { flex-direction:row !important; align-items:center !important; gap:10px !important; }
    #formReserva .res-botones button[type="submit"] { width:auto !important; margin-top:0 !important; padding:10px 22px !important; }

    /* Calendario */
    .cal-contenedor { border:1px solid #cbd5e1; border-radius:8px; padding:16px; background:#fff; width:100%; box-sizing:border-box; }
    body.dark-mode .cal-contenedor { background:#1e293b; border-color:#475569; }
    #formReserva div.cal-header { display:flex !important; flex-direction:row !important; justify-content:space-between !important; align-items:center !important; margin-bottom:12px !important; }
    .cal-header h4 { margin:0; font-size:16px; font-weight:700; color:#1e293b; text-transform:capitalize; }
    body.dark-mode .cal-header h4 { color:#f8fafc; }
    .cal-header button {
        width:36px; height:36px; border-radius:8px; border:1px solid #cbd5e1; background:#f8fafc;
        color:#1e293b; font-weight:700; font-size:16px; cursor:pointer; transition:background-color 0.15s ease;
    }
    .cal-header button:hover { background:#e2e8f0; }
    body.dark-mode .cal-header button { background:#334155; color:#fff; border-color:#475569; }
    #resCalGrid td { transition:background-color 0.15s ease; }
    #resCalGrid td[onclick]:hover { background:#e2e8f0 !important; }
    body.dark-mode #resCalGrid td { color:#f8fafc; border-color:#334155 !important; }
    body.dark-mode #resCalGrid td[onclick] { background:#0f172a !important; }

    /* Horario: aparece al elegir un día */
    #formReserva div.horario-box { display:none !important; padding:14px; background:#f8fafc; border-radius:8px; border:1px solid #e2e8f0; }
    #formReserva div.horario-box.activo { display:flex !important; }
    body.dark-mode #formReserva div.horario-box { background:#0f172a; border-color:#334155; }

    /* El formulario queda sobre Gestión de Reservas y la ventana de modificar sobre las dos */
    #modalHacerReserva { z-index:100010 !important; }
    #modalResModificar { z-index:100020 !important; }
</style>

<!-- Hacer una reserva (cliente y administrador) -->
<div class="modal-overlay" id="modalHacerReserva">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="resTitulo"><?php echo $esAdminReservas ? 'Nueva Reserva (Admin)' : 'Hacer una reserva'; ?></h3>
            <button class="modal-close" onclick="cerrarModal('modalHacerReserva')">&times;</button>
        </div>

        <form id="formReserva" onsubmit="resEnviar(event)">

            <?php if ($esAdminReservas): ?>
            <!-- A nombre de quién es la reserva -->
            <div class="res-cliente-box">
                <label style="width:100%;">Reserva a nombre de:
                    <select id="resAdminCiCliente" required>
                        <option value="">Seleccione un cliente...</option>
                    </select>
                </label>
                <small>La reserva que hace el administrador queda aprobada.</small>
            </div>
            <?php endif; ?>

            <!-- La fecha la completa el calendario -->
            <input type="hidden" id="resFecha" required>

            <div class="cal-contenedor">
                <div class="cal-header">
                    <button type="button" onclick="resCambiarMes(-1)" title="Mes anterior">&lt;</button>
                    <h4 id="resCalTituloMes">Mes Año</h4>
                    <button type="button" onclick="resCambiarMes(1)" title="Mes siguiente">&gt;</button>
                </div>
                <table style="width:100%; border-collapse:separate; border-spacing:4px; table-layout:fixed;">
                    <thead>
                        <tr>
                            <th style="text-align:center; padding:4px; font-size:12px; color:#64748b;">DOM</th>
                            <th style="text-align:center; padding:4px; font-size:12px; color:#64748b;">LUN</th>
                            <th style="text-align:center; padding:4px; font-size:12px; color:#64748b;">MAR</th>
                            <th style="text-align:center; padding:4px; font-size:12px; color:#64748b;">MIÉ</th>
                            <th style="text-align:center; padding:4px; font-size:12px; color:#64748b;">JUE</th>
                            <th style="text-align:center; padding:4px; font-size:12px; color:#64748b;">VIE</th>
                            <th style="text-align:center; padding:4px; font-size:12px; color:#64748b;">SÁB</th>
                        </tr>
                    </thead>
                    <tbody id="resCalGrid"></tbody>
                </table>
            </div>

            <!-- Horario de la reserva -->
            <div class="horario-box" id="resBoxHorario">
                <strong id="resLblFechaSeleccionada">Selecciona un horario:</strong>
                <div class="res-fila" style="margin-bottom:0;">
                    <label>Desde <input type="time" id="resIni" required onchange="resConsultarStock()"></label>
                    <label>Hasta <input type="time" id="resFin" required onchange="resConsultarStock()"></label>
                </div>
            </div>

            <p class="res-aviso" style="margin:4px 0 8px;">
                El stock que ves es el de la franja elegida: descuenta lo reservado y lo prestado en esas horas.
                Elegí cuántas unidades querés de cada material.
            </p>

            <div class="table-responsive">
                <table class="tabla-modal">
                    <thead><tr><th>Material</th><th>En servicio</th><th>Libres</th><th>Reservadas</th><th>Prestadas</th><th>Se liberan</th><th>Cantidad</th></tr></thead>
                    <tbody id="resStock"><tr><td colspan="7" style="text-align:center;">Selecciona un día en el calendario.</td></tr></tbody>
                </table>
            </div>

            <div id="resPanel"></div>

            <div class="res-botones">
                <button type="submit" class="btn-guardar" id="resBtnEnviar"><?php echo $esAdminReservas ? 'Crear reserva' : 'Solicitar reserva'; ?></button>
                <button type="button" class="btn-modal btn-cancelar" id="resBtnCancelarEdicion" style="display:none;" onclick="resCancelarEdicion()">Cancelar edición</button>
            </div>
        </form>

        <?php if (!$esAdminReservas): ?>
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
        <?php endif; ?>
    </div>
</div>

<?php if ($esAdminReservas): ?>
<!-- Gestión de reservas (administrador) -->
<div class="modal-overlay" id="modalGestionReservas">
    <div class="modal-content" style="max-width:1080px;">
        <div class="modal-header">
            <h3>Gestión de Reservas</h3>
            <button class="modal-close" onclick="cerrarModal('modalGestionReservas')">&times;</button>
        </div>
        <div class="res-tabs res-fila">
            <button type="button" class="btn-modal" id="resTabReservas" onclick="resTab('reservas')">Reservas</button>
            <button type="button" class="btn-modal" id="resTabEspera" onclick="resTab('espera')">Lista de espera</button>
            <button type="button" class="btn-modal" id="resTabHistorial" onclick="resTab('historial')">Historial</button>
            <button type="button" class="btn-guardar btn-modal btn-crear" style="margin-left:auto;" onclick="abrirModalHacerReserva()">+ Nueva Reserva</button>
        </div>

        <div id="resSecReservas">
            <div class="res-fila">
                <div class="filtros-estado" id="resFiltros" style="margin:0;"></div>
                <label>Fecha <input type="date" id="resFiltroFecha" onchange="resListarAdmin()"></label>
                <button type="button" class="btn-modal btn-cancelar" onclick="document.getElementById('resFiltroFecha').value=''; resListarAdmin();">Quitar fecha</button>
            </div>
            <div class="table-responsive">
                <table class="tabla-modal">
                    <thead><tr><th>#</th><th>Estado</th><th>Cliente</th><th>Fecha</th><th>Horario</th><th>Materiales</th><th>Acciones</th></tr></thead>
                    <tbody id="resBodyAdmin"></tbody>
                </table>
            </div>
        </div>

        <div id="resSecEspera" style="display:none;">
            <div class="res-fila"><button type="button" class="btn-modal btn-crear" onclick="resProcesarEspera()">Atender lista de espera ahora</button></div>
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
                <button type="button" class="btn-modal btn-cancelar" onclick="document.getElementById('resHistCi').value=''; document.getElementById('resHistFecha').value=''; resListarHistorial();">Limpiar</button>
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
            <label style="display:flex; flex-direction:column; gap:4px; font-size:13px;">Materiales (uno por línea, "Nombre=cantidad")
                <textarea id="resModItems" rows="5" required></textarea>
            </label>
            <div style="margin-top:12px;"><button type="submit" class="btn-guardar">Guardar cambios</button></div>
        </form>
    </div>
</div>
<?php endif; ?>



<script>
    const MESES_NOMBRES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    const RUTA_CONTROLADOR_RESERVAS = '<?php echo dirname($_SERVER["SCRIPT_NAME"]) . "/../CRUD_Reservas/Controlador/Controlador_Reservas.php"; ?>';
    const RES_ES_ADMIN = <?php echo $esAdminReservas ? 'true' : 'false'; ?>;
    const RES_ADMIN_CI = <?php echo json_encode($ciAdminReservas); ?>;
    const RES_ADMIN_NOMBRE = <?php echo json_encode($nombreAdminReservas, JSON_UNESCAPED_UNICODE); ?>;

    let resCalFechaActual = new Date();
    let resEditando = 0;
    let resUltimoPedido = null;

    function resFechaLocal(d) {
        const p = n => String(n).padStart(2, '0');
        return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
    }
    function resHora(h) { return String(h || '').slice(0, 5); }

    async function resPost(cuerpo) {
        try {
            const res = await fetch(RUTA_CONTROLADOR_RESERVAS, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(cuerpo)
            });
            const texto = await res.text();
            let json;
            try { json = JSON.parse(texto); } catch (e) { alert('Respuesta inesperada del servidor:\n' + texto.substring(0, 300)); return null; }
            return { ok: res.ok && json.success !== false, status: res.status, json };
        } catch (err) {
            alert('Error de conexión con el servidor: ' + err.message);
            return null;
        }
    }

    function resAvisoError(json) {
        const lista = (json.errores || []).map(e => '• ' + e).join('\n');
        alert((json.message || 'No se pudo completar la operación.') + (lista ? '\n\n' + lista : ''));
    }

    // Calendario

    function resRenderCalendario() {
        const grid = document.getElementById('resCalGrid');
        const titulo = document.getElementById('resCalTituloMes');
        if (!grid || !titulo) return;

        const anio = resCalFechaActual.getFullYear();
        const mes = resCalFechaActual.getMonth();
        titulo.textContent = `${MESES_NOMBRES[mes]} ${anio}`;

        const primerDiaIndex = new Date(anio, mes, 1).getDay();
        const totalDias = new Date(anio, mes + 1, 0).getDate();
        const hoyStr = resFechaLocal(new Date());
        const fechaSeleccionada = document.getElementById('resFecha').value;

        let html = '';
        let diaContador = 1;
        for (let i = 0; i < 6; i++) {
            let filaHtml = '<tr>';
            let tieneDias = false;
            for (let j = 0; j < 7; j++) {
                if ((i === 0 && j < primerDiaIndex) || diaContador > totalDias) {
                    filaHtml += '<td></td>';
                    continue;
                }
                tieneDias = true;
                const fechaObj = new Date(anio, mes, diaContador);
                const fechaStr = resFechaLocal(fechaObj);
                const diaSemana = fechaObj.getDay();
                // No se reservan días pasados ni fines de semana
                const deshabilitado = fechaStr < hoyStr || diaSemana === 0 || diaSemana === 6;
                const seleccionado = (fechaStr === fechaSeleccionada);

                let estilo = 'text-align:center; padding:8px 0; border:1px solid #e2e8f0; border-radius:6px; font-size:14px;';
                if (deshabilitado) {
                    estilo += ' opacity:0.35; background:#f1f5f9; cursor:not-allowed;';
                } else if (seleccionado) {
                    estilo += ' background:#10b981 !important; color:#fff; font-weight:bold; border-color:#059669; cursor:pointer;';
                } else {
                    estilo += ' background:#fff; cursor:pointer;';
                }
                const click = deshabilitado ? '' : `onclick="resSeleccionarDia('${fechaStr}')"`;
                filaHtml += `<td ${click} style="${estilo}">${diaContador}</td>`;
                diaContador++;
            }
            filaHtml += '</tr>';
            if (tieneDias) html += filaHtml;
        }
        grid.innerHTML = html;
    }

    function resCambiarMes(paso) {
        resCalFechaActual = new Date(resCalFechaActual.getFullYear(), resCalFechaActual.getMonth() + paso, 1);
        resRenderCalendario();
    }

    function resSeleccionarDia(fechaStr) {
        document.getElementById('resFecha').value = fechaStr;
        const partes = fechaStr.split('-');
        document.getElementById('resLblFechaSeleccionada').textContent = `Horario para el ${partes[2]}/${partes[1]}/${partes[0]}:`;
        document.getElementById('resBoxHorario').classList.add('activo');
        resRenderCalendario();

        if (document.getElementById('resIni').value && document.getElementById('resFin').value) {
            resConsultarStock();
        } else {
            document.getElementById('resStock').innerHTML = '<tr><td colspan="7" style="text-align:center;">Ingresa el horario (Desde / Hasta) para ver la disponibilidad.</td></tr>';
        }
    }

    // Formulario

    async function abrirModalHacerReserva() {
        resEditando = 0;
        resCalFechaActual = new Date();
        document.getElementById('resFecha').value = '';
        document.getElementById('resIni').value = '';
        document.getElementById('resFin').value = '';
        document.getElementById('resPanel').innerHTML = '';
        document.getElementById('resBoxHorario').classList.remove('activo');
        document.getElementById('resStock').innerHTML = '<tr><td colspan="7" style="text-align:center;">Selecciona un día en el calendario.</td></tr>';
        document.getElementById('resTitulo').textContent = RES_ES_ADMIN ? 'Nueva Reserva (Admin)' : 'Hacer una reserva';
        document.getElementById('resBtnEnviar').textContent = RES_ES_ADMIN ? 'Crear reserva' : 'Solicitar reserva';
        document.getElementById('resBtnCancelarEdicion').style.display = 'none';
        document.getElementById('modalHacerReserva').classList.add('active');

        // El administrador elige a nombre de quién reserva: un cliente o él mismo
        const selectCliente = document.getElementById('resAdminCiCliente');
        if (selectCliente) {
            selectCliente.innerHTML = '<option value="">Cargando clientes...</option>';
            const r = await resPost({ action: 'cargar_clientes_admin' });
            let opciones = '<option value="">Seleccione un cliente...</option>';
            if (RES_ADMIN_CI) {
                opciones += `<option value="${esc(RES_ADMIN_CI)}">A mi nombre (${esc(RES_ADMIN_NOMBRE || RES_ADMIN_CI)})</option>`;
            }
            if (r && r.ok && Array.isArray(r.json.data)) {
                opciones += r.json.data.map(c => `<option value="${esc(c.ci)}">${esc(c.nombre)} (${esc(c.ci)})</option>`).join('');
            }
            selectCliente.innerHTML = opciones;
        }

        resRenderCalendario();

        if (!RES_ES_ADMIN) {
            resListarMias();
            resListarEspera();
        }
    }

    async function resConsultarStock(conservar) {
        const f = document.getElementById('resFecha').value;
        const i = document.getElementById('resIni').value, h = document.getElementById('resFin').value;
        const cuerpo = document.getElementById('resStock');
        document.getElementById('resPanel').innerHTML = '';
        if (!f || !i || !h) return;

        // Lo que ya estaba cargado no se pierde al cambiar el horario
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
        const pedido = {
            fecha: document.getElementById('resFecha').value,
            hora_inicio: document.getElementById('resIni').value,
            hora_fin: document.getElementById('resFin').value,
            items: resItemsElegidos()
        };
        const selectCliente = document.getElementById('resAdminCiCliente');
        if (selectCliente) {
            pedido.ci_cliente = selectCliente.value;
        }
        return Object.assign(pedido, extra || {});
    }

    // extra: { aceptar_parcial, anotar_espera } cuando se responde al aviso de stock
    async function resEnviar(ev, extra) {
        if (ev) ev.preventDefault();

        const pedido = resPedidoActual(extra);
        if (RES_ES_ADMIN && !resEditando && !pedido.ci_cliente) { alert('Elegí a nombre de quién es la reserva.'); return false; }
        if (!pedido.fecha) { alert('Elegí un día en el calendario.'); return false; }
        if (!pedido.hora_inicio || !pedido.hora_fin) { alert('Indicá la hora de inicio y la de fin.'); return false; }
        if (!pedido.items.length) { alert('Elegí la cantidad de al menos un material.'); return false; }

        const accion = resEditando ? 'modificar' : 'solicitar';
        const payload = Object.assign({ action: accion }, resEditando ? { id_solicitud: resEditando } : {}, pedido);

        const r = await resPost(payload);
        if (!r) return false;
        if (r.ok) {
            resExito(r.json.data || {});
            return false;
        }
        // 409: no alcanza para todo, se ofrecen opciones
        if (r.json.codigo && r.json.detalle) {
            resMostrarAviso(r.json);
            return false;
        }
        resAvisoError(r.json);
        return false;
    }

    function resExito(data) {
        data = data || {};
        let msg;
        if (resEditando) {
            msg = `Reserva n.º ${resEditando} modificada.`;
        } else if (!data.id_solicitud) {
            msg = 'No había stock en esa franja: quedó anotado en la lista de espera.';
        } else if (data.aprobada) {
            msg = `Reserva n.º ${data.id_solicitud} creada y aprobada.`;
        } else if (RES_ES_ADMIN) {
            msg = `Reserva n.º ${data.id_solicitud} creada.` + (data.aviso ? ' ' + data.aviso : '');
        } else {
            msg = `Reserva n.º ${data.id_solicitud} enviada. Queda pendiente hasta que el CeiLab la apruebe.`;
        }
        if (data.id_solicitud && data.espera && data.espera.length) {
            msg += ' Lo que faltaba quedó en la lista de espera.';
        }
        window.avisar ? window.avisar(msg, { tipo: 'ok' }) : alert(msg);

        resCancelarEdicion();
        if (RES_ES_ADMIN) {
            cerrarModal('modalHacerReserva');
            if (document.getElementById('modalGestionReservas').classList.contains('active')) {
                resFiltroEstado = data.aprobada ? 'APROBADO' : 'PENDIENTE';
                resListarAdmin();
            }
        } else {
            document.querySelectorAll('input.res-cant').forEach(c => c.value = 0);
            resConsultarStock();
            resListarMias();
            resListarEspera();
        }
    }

    function resMostrarAviso(json) {
        const parcial = json.codigo === 'STOCK_PARCIAL';
        const items = (json.detalle && json.detalle.items) || [];
        const filas = items.map(i => {
            const prox = (i.proximos || []).map(p => `${esc(p.hora)} (+${esc(p.cantidad)}, ${esc(p.origen)})`).join(', ');
            return `<li><strong>${esc(i.nombre)}</strong>: pediste ${esc(i.solicitado)}, libres ${esc(i.libres)}, faltan ${esc(i.faltan)}.
                    Reservadas ${esc(i.reservadas)}, prestadas ${esc(i.prestadas)}${i.en_mantenimiento ? ', en mantenimiento ' + esc(i.en_mantenimiento) : ''}.
                    ${prox ? '<br>Se van liberando: ' + prox + '.' : ''}</li>`;
        }).join('');
        const titulo = parcial ? 'No alcanza el stock para todo lo pedido' : esc(json.message);
        const botones = parcial
            ? `<button type="button" class="btn-modal btn-crear" onclick="resEnviar(null, {aceptar_parcial: true})">Reservar solo lo disponible</button>
               <button type="button" class="btn-modal btn-res-azul" onclick="resEnviar(null, {aceptar_parcial: true, anotar_espera: true})">Reservar lo disponible y anotar el resto en espera</button>`
            : `<button type="button" class="btn-modal btn-res-azul" onclick="resEnviar(null, {anotar_espera: true})">Anotar en la lista de espera</button>`;
        document.getElementById('resPanel').innerHTML = `
            <div class="res-panel ${parcial ? '' : 'error'}">
                <h4>${titulo}</h4>
                <ul>${filas}</ul>
                ${resEditando ? '' : botones}
                <button type="button" class="btn-modal btn-cancelar" onclick="document.getElementById('resPanel').innerHTML=''">Cambiar el pedido</button>
            </div>`;
    }

    function resCancelarEdicion() {
        resEditando = 0;
        document.getElementById('resTitulo').textContent = RES_ES_ADMIN ? 'Nueva Reserva (Admin)' : 'Hacer una reserva';
        document.getElementById('resBtnEnviar').textContent = RES_ES_ADMIN ? 'Crear reserva' : 'Solicitar reserva';
        document.getElementById('resBtnCancelarEdicion').style.display = 'none';
        document.querySelectorAll('input.res-cant').forEach(c => c.value = 0);
        document.getElementById('resPanel').innerHTML = '';
    }

    async function resCancelar(id) {
        const quiere = window.confirmar ? await window.confirmar('¿Cancelar la reserva n.º ' + id + '?', { peligro: true }) : confirm('¿Cancelar la reserva n.º ' + id + '?');
        if (!quiere) return;
        const r = await resPost({ action: 'cancelar', id_solicitud: id });
        if (r && !r.ok) resAvisoError(r.json);
        if (RES_ES_ADMIN) {
            resListarAdmin();
        } else {
            resListarMias(); resConsultarStock(); resListarEspera();
        }
    }

    <?php if (!$esAdminReservas): ?>
    // Cliente
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
                <td>${esc(x.resumen)}</td>
                <td>${esc(x.motivo_rechazo || '')}</td>
                <td>${x.estado === 'PENDIENTE' ? `<button type="button" class="btn-modal btn-editar" onclick="resModificar(${Number(x.id_solicitud)})">Modificar</button>` : ''}
                    ${['PENDIENTE', 'APROBADO'].includes(x.estado) ? `<button type="button" class="btn-modal btn-eliminar" onclick="resCancelar(${Number(x.id_solicitud)})">Cancelar</button>` : ''}</td>
            </tr>`).join('') || '<tr><td colspan="7" style="text-align:center;">Todavía no hiciste reservas.</td></tr>';
    }

    async function resListarEspera() {
        const r = await pedirJSON(`${RUTA_CONTROLADOR_RESERVAS}?action=mi_espera`);
        if (!r) return;
        document.getElementById('resMiEspera').innerHTML = r.data.map(x => `
            <tr>
                <td>${esc(x.nombre)}</td><td>${esc(x.cantidad)}</td><td>${esc(x.fecha)}</td>
                <td>${esc(resHora(x.hora_inicio))}–${esc(resHora(x.hora_fin))}</td>
                <td>${esc(x.estado)}</td>
                <td>${x.estado === 'ESPERANDO' ? `<button type="button" class="btn-modal btn-eliminar" onclick="resCancelarEspera(${Number(x.id_espera)})">Salir</button>` : ''}</td>
            </tr>`).join('') || '<tr><td colspan="6" style="text-align:center;">No estás en ninguna lista de espera.</td></tr>';
    }

    function resModificar(id) {
        const x = (window.resMias || []).find(r => Number(r.id_solicitud) === id);
        if (!x) return;
        resEditando = id;
        const [a, m] = x.fecha.split('-').map(Number);
        resCalFechaActual = new Date(a, m - 1, 1);
        document.getElementById('resIni').value = resHora(x.hora_inicio);
        document.getElementById('resFin').value = resHora(x.hora_fin);
        document.getElementById('resFecha').value = x.fecha;
        document.getElementById('resBoxHorario').classList.add('activo');
        document.getElementById('resLblFechaSeleccionada').textContent = 'Horario:';
        resRenderCalendario();
        document.getElementById('resTitulo').textContent = 'Modificar la reserva n.º ' + id;
        document.getElementById('resBtnEnviar').textContent = 'Guardar cambios';
        document.getElementById('resBtnCancelarEdicion').style.display = '';
        const previo = {}; (x.items || []).forEach(i => previo[i.nombre] = i.cantidad);
        resConsultarStock(previo);
        document.getElementById('formReserva').scrollIntoView({ behavior: 'smooth' });
    }

    async function resCancelarEspera(id) {
        const r = await resPost({ action: 'cancelar_espera', id_espera: id });
        if (r && !r.ok) resAvisoError(r.json);
        resListarEspera();
    }

    <?php else: ?>
    // Administrador
    let resFiltroEstado = 'PENDIENTE';
    let resDatosAdmin = [];

    async function abrirModalGestionReservas(estado) {
        if (estado !== undefined) resFiltroEstado = estado;
        document.getElementById('modalGestionReservas').classList.add('active');
        resTab('reservas');
    }

    function resTab(cual) {
        ['reservas', 'espera', 'historial'].forEach(t => {
            const nombre = t.charAt(0).toUpperCase() + t.slice(1);
            document.getElementById('resSec' + nombre).style.display = (t === cual) ? '' : 'none';
            document.getElementById('resTab' + nombre).classList.toggle('activo', t === cual);
        });
        if (cual === 'reservas') resListarAdmin();
        if (cual === 'espera') resListarEsperaAdmin();
        if (cual === 'historial') resListarHistorial();
    }

    async function resListarAdmin() {
        const estados = [['', 'Todas'], ['PENDIENTE', 'Pendientes'], ['APROBADO', 'Aprobadas'], ['ACTIVO', 'En uso'],
                         ['FINALIZADO', 'Finalizadas'], ['RECHAZADO', 'Rechazadas'], ['CANCELADO', 'Canceladas']];
        document.getElementById('resFiltros').innerHTML = estados.map(([v, t]) =>
            `<button type="button" class="btn-modal ${v === resFiltroEstado ? 'activo' : ''}" onclick="resFiltroEstado='${v}'; resListarAdmin();">${t}</button>`).join('');

        const q = new URLSearchParams({ action: 'listar' });
        if (resFiltroEstado) q.set('estado', resFiltroEstado);
        const dia = document.getElementById('resFiltroFecha').value;
        if (dia) q.set('fecha', dia);

        const r = await pedirJSON(`${RUTA_CONTROLADOR_RESERVAS}?${q}`);
        if (!r) return;
        resDatosAdmin = r.data;
        document.getElementById('resBodyAdmin').innerHTML = r.data.map(x => `
            <tr>
                <td>${esc(x.id_solicitud)}</td>
                <td>${chipEstado(x.estado)}</td>
                <td>${esc(x.cliente)}<br><small>${esc(x.ci_cliente)}</small></td>
                <td>${esc(x.fecha)}</td>
                <td>${esc(resHora(x.hora_inicio))}–${esc(resHora(x.hora_fin))}</td>
                <td>${esc(x.resumen)}</td>
                <td class="res-acciones">
                    ${x.estado === 'PENDIENTE' ? `<button type="button" class="btn-modal btn-crear" onclick="resAprobar(${Number(x.id_solicitud)})">Aprobar</button><button type="button" class="btn-modal btn-eliminar" onclick="resRechazar(${Number(x.id_solicitud)})">Rechazar</button>` : ''}
                    ${['PENDIENTE', 'APROBADO'].includes(x.estado)   ? `<button type="button" class="btn-modal btn-editar" onclick="resModificarAdmin(${parseInt(x.id_solicitud, 10)})">Modificar</button>` : ''}
                    ${x.estado === 'APROBADO' ? `<button type="button" class="btn-modal btn-res-azul" onclick="resAccion('iniciar', ${Number(x.id_solicitud)})">Iniciar uso</button>` : ''}
                    ${['APROBADO', 'ACTIVO'].includes(x.estado) ? `<button type="button" class="btn-modal btn-res-violeta" onclick="resAccion('finalizar', ${Number(x.id_solicitud)})">Finalizar</button>` : ''}
                    ${['PENDIENTE', 'APROBADO'].includes(x.estado) ? `<button type="button" class="btn-modal btn-cancelar" onclick="resCancelar(${Number(x.id_solicitud)})">Cancelar</button>` : ''}
                </td>
            </tr>`).join('') || '<tr><td colspan="7" style="text-align:center;">No hay reservas con ese filtro.</td></tr>';
    }

    async function resAccion(accion, id) {
        const r = await resPost({ action: accion, id_solicitud: id });
        if (r && !r.ok) resAvisoError(r.json);
        resListarAdmin();
    }
   // --- NUEVO MENSAJE DE PRÉSTAMO APROBADO ---
    async function resAprobar(id) {
        const r = await resPost({ action: 'aprobar', id_solicitud: id });
        if (r && r.ok) {
            if (window.avisar) window.avisar('Préstamo aprobado', { tipo: 'ok' });
            else alert('Préstamo aprobado');
            resListarAdmin();
        } else {
            resAvisoError(r ? r.json : null);
        }
    }

    async function resRechazar(id) {
        const motivo = window.pedirTexto ? await window.pedirTexto('Motivo del rechazo:') : prompt('Motivo del rechazo:');
        if (!motivo) return;
        const r = await resPost({ action: 'rechazar', id_solicitud: id, motivo });
        if (r && !r.ok) resAvisoError(r.json);
        resListarAdmin();
    }

    // --- NUEVA FUNCIÓN QUE ABRE EL FORMULARIO VISUAL COMPLETO ---
    async function resModificarAdmin(id) {
        // 1. Buscar la reserva en la lista cargada
        const x = resDatosAdmin.find(r => Number(r.id_solicitud) === Number(id));
        if (!x) return;

        // 2. Activar el modo edición global
        resEditando = id;

        // 3. Configurar calendario y horas
        const [a, m] = x.fecha.split('-').map(Number);
        resCalFechaActual = new Date(a, m - 1, 1);
        document.getElementById('resIni').value = resHora(x.hora_inicio);
        document.getElementById('resFin').value = resHora(x.hora_fin);
        document.getElementById('resFecha').value = x.fecha;
        document.getElementById('resBoxHorario').classList.add('activo');
        document.getElementById('resLblFechaSeleccionada').textContent = 'Horario:';

        // 4. Cargar lista de clientes en el selector (si es admin) y seleccionar al dueño
        const selectCliente = document.getElementById('resAdminCiCliente');
        if (selectCliente) {
            if (selectCliente.options.length <= 2) {
                selectCliente.innerHTML = '<option value="">Cargando clientes...</option>';
                const rc = await resPost({ action: 'cargar_clientes_admin' });
                let opciones = '<option value="">Seleccione un cliente...</option>';
                if (rc && rc.ok && Array.isArray(rc.json.data)) {
                    opciones += rc.json.data.map(c => `<option value="${c.ci}">${c.nombre} (${c.ci})</option>`).join('');
                }
                selectCliente.innerHTML = opciones;
            }
            selectCliente.value = x.ci_cliente; // Marcar el cliente original
        }

        resRenderCalendario();

        // 5. Ajustar textos del modal
        document.getElementById('resTitulo').textContent = 'Modificar la reserva n.º ' + id;
        document.getElementById('resBtnEnviar').textContent = 'Guardar cambios';
        document.getElementById('resBtnCancelarEdicion').style.display = '';

        // 6. Recuperar los materiales desde el resumen (Ej: "Ceibalita ×1, Router ×2")
        const previo = {};
        if (x.resumen) {
             x.resumen.split(',').forEach(item => {
                 const partes = item.trim().split(/[×xX]/);
                 if (partes.length === 2) {
                     previo[partes[0].trim()] = partes[1].trim();
                 } else {
                     previo[item.trim()] = 1;
                 }
             });
        }

        // 7. Cargar la tabla de stock interactiva inyectando los valores previos
        await resConsultarStock(previo);

        // 8. Abrir el modal principal
        document.getElementById('modalHacerReserva').classList.add('active');
    }

async function resGuardarModificacionInteractiva(e) {
    if (e) e.preventDefault();

    const id = window.reservaEditandoId;
    if (!id) return;

    const fecha = (document.getElementById('resCliFecha') || document.getElementById('resFecha')).value;
    const horaInicio = (document.getElementById('resCliIni') || document.getElementById('resHoraInicio')).value;
    const horaFin = (document.getElementById('resCliFin') || document.getElementById('resHoraFin')).value;

    // Recolectar cantidades de la grilla/tarjetas de materiales
    const materialesSeleccionados = [];
    document.querySelectorAll('.cant-material, [id^="cant_"]').forEach(input => {
        const cantidad = parseInt(input.value, 10);
        if (cantidad > 0) {
            const idMaterial = input.id.replace('cant_', '') || input.dataset.id;
            materialesSeleccionados.push({
                id_material: idMaterial,
                cantidad: cantidad
            });
        }
    });

    const r = await resPost({
        action: 'modificar',
        id_solicitud: Number(id),
        fecha: fecha,
        hora_inicio: horaInicio,
        hora_fin: horaFin,
        materiales: materialesSeleccionados
    });

    if (r && r.ok) {
        if (typeof cerrarModal === 'function') cerrarModal('modalResCliente');
        if (window.showCustomAlert) showCustomAlert('Reserva modificada correctamente.', 'Éxito', 'exito');
        
        // Recargar la tabla de administración
        resListarAdmin();
    } else {
        if (window.resAvisoError) resAvisoError(r ? r.json : null);
    }
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
                <td>${x.estado === 'ESPERANDO' ? `<button type="button" class="btn-modal btn-eliminar" onclick="resCancelarEsperaAdmin(${Number(x.id_espera)})">Quitar</button>` : ''}</td>
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
</script>
