document.addEventListener("DOMContentLoaded", async () => {
    const contenedor = document.getElementById("tablaCitas");
    const contador = document.getElementById("total-registros");
    const filtro = document.getElementById("table-filter");
    const filtroEstado = document.getElementById("filtroEstado");
    const esAdmin = window.CITAS_ES_ADMIN === true;
    const API = "../DAO/DAOCitas.php";

    const esc = (t) => String(t ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
    const money = (n) => "$" + (parseFloat(n) || 0).toFixed(2);
    const fmtFecha = (s) => { const [f, h] = s.split(" "); return { f: f.split("-").reverse().join("/"), h: h.slice(0, 5) }; };
    const claseEstado = { Pendiente: "pending", Completada: "success", Cancelada: "failed" };

    async function api(datos) {
        const fd = new FormData();
        Object.entries(datos).forEach(([k, v]) => fd.append(k, v));
        const r = await fetch(API, { method: "POST", body: fd });
        return r.json();
    }

    const columnas = [
        { title: "ID", field: "id_cita", width: 80, hozAlign: "center", headerHozAlign: "center" },
        {
            title: "Fecha", field: "fecha_hora_inicio", width: 140,
            formatter: (cell) => { const d = fmtFecha(cell.getValue()); return `<div class="table-user-cell"><div><div class="table-user-name">${d.f}</div><div class="table-user-sub">${d.h}</div></div></div>`; }
        },
        {
            title: "Cliente", field: "cliente",
            formatter: (cell) => { const r = cell.getRow().getData(); return `<div class="table-user-cell"><div><div class="table-user-name">${esc(r.cliente)}</div><div class="table-user-sub">${esc(r.telefono)}</div></div></div>`; }
        }
    ];
    if (esAdmin) columnas.push({ title: "Barbero", field: "barbero" });
    columnas.push(
        { title: "Servicios", field: "servicios", formatter: (c) => `<span title="${esc(c.getValue())}">${esc(c.getValue() || "—")}</span>` },
        { title: "Total", field: "total_cita", width: 100, hozAlign: "right", headerHozAlign: "right", formatter: (c) => `<strong class="celda-precio">${money(c.getValue())}</strong>` },
        { title: "Estado", field: "estado_cita", width: 150, hozAlign: "center", headerHozAlign: "center", formatter: (c) => `<span class="badge-table ${claseEstado[c.getValue()] || ""}">${esc(c.getValue())}</span>` },
        {
            title: "Acciones", width: 150, headerSort: false, hozAlign: "center", headerHozAlign: "center",
            formatter: (cell) => {
                const r = cell.getRow().getData();
                const pend = r.estado_cita === "Pendiente";
                return `<div class="d-flex justify-content-center gap-1">
                    <button class="table-btn-action btn-detalle" data-id="${r.id_cita}" title="Ver detalle"><i class="bi bi-eye"></i></button>
                    ${pend ? `<button class="table-btn-action btn-completar" data-id="${r.id_cita}" title="Marcar como completada"><i class="bi bi-check2-circle"></i></button>
                    <button class="table-btn-action delete btn-cancelar" data-id="${r.id_cita}" title="Cancelar cita"><i class="bi bi-x-circle"></i></button>` : ""}
                </div>`;
            },
            cellClick: (e, cell) => {
                const b = e.target.closest("button");
                if (!b) return;
                if (b.classList.contains("btn-detalle")) verDetalle(b.dataset.id);
                else if (b.classList.contains("btn-completar")) cambiarEstado(b.dataset.id, "Completada");
                else if (b.classList.contains("btn-cancelar")) cambiarEstado(b.dataset.id, "Cancelada");
            }
        }
    );

    const tabla = new Tabulator(contenedor, {
        data: [],
        layout: "fitColumns",
        placeholder: "No hay citas para mostrar",
        pagination: true,
        paginationSize: 10,
        paginationSizeSelector: [10, 25, 50, 100],
        initialSort: [{ column: "fecha_hora_inicio", dir: "desc" }],
        columns: columnas
    });

    async function cargar() {
        const r = await api({ opcion: "consultar" });
        if (r.estado) {
            await tabla.setData(r.citas);
            contador.textContent = tabla.getDataCount();
        }
    }

    function aplicarFiltros() {
        const texto = filtro.value.trim().toLowerCase();
        const estado = filtroEstado.value;
        tabla.setFilter((d) => {
            if (estado && d.estado_cita !== estado) return false;
            if (!texto) return true;
            return [d.cliente, d.barbero, d.servicios, d.telefono].some((v) => String(v || "").toLowerCase().includes(texto));
        });
        contador.textContent = tabla.getDataCount("active");
    }
    filtro.addEventListener("keyup", aplicarFiltros);
    filtroEstado.addEventListener("change", aplicarFiltros);

    async function verDetalle(id) {
        const r = await api({ opcion: "detalle", id_cita: id });
        if (!r.estado) { Swal.fire({ icon: "error", title: "Barber-Box", text: r.mensaje }); return; }
        const c = r.cita, ini = fmtFecha(c.fecha_hora_inicio), fin = fmtFecha(c.fecha_hora_fin);

        document.getElementById("detalleCuerpo").innerHTML = `
            <div class="detalle-grid">
                <div><span>Cliente</span><strong>${esc(c.cliente)}</strong></div>
                <div><span>Teléfono</span><strong>${esc(c.telefono)}</strong></div>
                <div><span>Correo</span><strong>${esc(c.correo || "—")}</strong></div>
                <div><span>Barbero</span><strong>${esc(c.barbero)}</strong></div>
                <div><span>Fecha</span><strong>${ini.f}</strong></div>
                <div><span>Horario</span><strong>${ini.h} – ${fin.h}</strong></div>
                <div><span>Estado</span><strong><span class="badge-table ${claseEstado[c.estado_cita] || ""}">${esc(c.estado_cita)}</span></strong></div>
                <div><span>Nota</span><strong>${esc(c.nota || "—")}</strong></div>
            </div>
            <h6 class="fw-bold mt-4 mb-2">Servicios</h6>
            <ul class="list-unstyled detalle-servicios">
                ${r.detalle.map((d) => `<li><span>${esc(d.nombre_servicio)} <small class="text-muted">· ${parseInt(d.duracion_estimada, 10)} min</small></span><strong>${money(d.precio_unitario)}</strong></li>`).join("")}
                <li class="total"><span>Total</span><strong>${money(c.total_cita)}</strong></li>
            </ul>`;
        bootstrap.Modal.getOrCreateInstance(document.getElementById("modalDetalle")).show();
    }

    async function cambiarEstado(id, estado) {
        const ok = await Swal.fire({
            title: estado === "Completada" ? "¿Marcar como completada?" : "¿Cancelar la cita?",
            icon: "question", showCancelButton: true,
            confirmButtonText: "Sí", cancelButtonText: "Volver"
        });
        if (!ok.isConfirmed) return;
        const r = await api({ opcion: "cambiarEstado", id_cita: id, estado });
        Swal.fire({ icon: r.estado ? "success" : "error", title: "Barber-Box", text: r.mensaje, timer: 1500, showConfirmButton: false });
        if (r.estado) { await cargar(); aplicarFiltros(); }
    }

    cargar();
});
