document.addEventListener("DOMContentLoaded", () => {
    const API = "../DAO/DAOCita.php";

    const listaServicios = document.getElementById("listaServicios");
    const selBarbero = document.getElementById("selBarbero");
    const fechaCita = document.getElementById("fechaCita");
    const listaHoras = document.getElementById("listaHoras");
    const notaCita = document.getElementById("notaCita");
    const btnAgendar = document.getElementById("btnAgendar");
    const tbodyHistorial = document.getElementById("tbodyHistorial");

    let catalogo = { servicios: [], barberos: [] };
    let horaElegida = null;

    const esc = (t) => String(t ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
    const money = (n) => "$" + (parseFloat(n) || 0).toFixed(2);

    async function api(datos) {
        const fd = new FormData();
        Object.entries(datos).forEach(([k, v]) => {
            if (Array.isArray(v)) v.forEach((x) => fd.append(k + "[]", x));
            else fd.append(k, v);
        });
        const r = await fetch(API, { method: "POST", body: fd });
        return r.json();
    }

    const serviciosElegidos = () =>
        [...listaServicios.querySelectorAll("input:checked")].map((i) => parseInt(i.value, 10));

    // ---------- Tabs enlazados con el menú lateral ----------
    function mostrarTab(nombre) {
        const btn = document.getElementById(nombre === "historial" ? "tab-historial" : "tab-agendar");
        if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
        if (nombre === "historial") cargarHistorial();
    }
    document.querySelectorAll("[data-tab]").forEach((a) => {
        a.addEventListener("click", (e) => {
            e.preventDefault();
            history.replaceState(null, "", "#" + a.dataset.tab);
            mostrarTab(a.dataset.tab);
        });
    });
    document.getElementById("tab-historial").addEventListener("shown.bs.tab", cargarHistorial);

    // ---------- Catálogo ----------
    async function cargarCatalogo() {
        const r = await api({ opcion: "catalogo" });
        if (!r.estado) return;
        catalogo = r;

        listaServicios.innerHTML = r.servicios.length ? r.servicios.map((s) => `
            <div class="col-12 col-md-6">
                <label class="servicio-opcion w-100">
                    <input type="checkbox" value="${s.id_servicio}" class="d-none">
                    <div class="servicio-opcion-body">
                        <div>
                            <div class="fw-bold">${esc(s.nombre_servicio)}</div>
                            <div class="small text-muted">${esc(s.descripcion || "")}</div>
                            <div class="small text-muted"><i class="bi bi-clock me-1"></i>${parseInt(s.duracion_estimada, 10)} min</div>
                        </div>
                        <div class="servicio-precio">${money(s.precio)}</div>
                        <i class="bi bi-check-circle-fill servicio-check"></i>
                    </div>
                </label>
            </div>`).join("") : '<div class="col-12 text-muted">No hay servicios disponibles por ahora.</div>';

        selBarbero.innerHTML = '<option value="">Selecciona un barbero</option>' +
            r.barberos.map((b) => `<option value="${b.id_empleado}">${esc(b.nombre)}</option>`).join("");
    }

    // ---------- Resumen y disponibilidad ----------
    function actualizarResumen() {
        const ids = serviciosElegidos();
        const elegidos = catalogo.servicios.filter((s) => ids.includes(parseInt(s.id_servicio, 10)));
        const duracion = elegidos.reduce((a, s) => a + parseInt(s.duracion_estimada, 10), 0);
        const total = elegidos.reduce((a, s) => a + parseFloat(s.precio), 0);

        document.getElementById("resumenServicios").innerHTML = elegidos.length
            ? elegidos.map((s) => `<li class="d-flex justify-content-between"><span>${esc(s.nombre_servicio)}</span><span>${money(s.precio)}</span></li>`).join("")
            : '<li class="text-muted">Sin servicios seleccionados</li>';
        document.getElementById("resumenDuracion").textContent = duracion + " min";
        document.getElementById("resumenTotal").textContent = money(total);
        document.getElementById("resumenFecha").textContent = fechaCita.value ? fechaCita.value.split("-").reverse().join("/") : "—";
        document.getElementById("resumenHora").textContent = horaElegida || "—";
        btnAgendar.disabled = !(elegidos.length && selBarbero.value && fechaCita.value && horaElegida);
    }

    async function cargarHoras() {
        horaElegida = null;
        const ids = serviciosElegidos();

        if (!ids.length || !selBarbero.value || !fechaCita.value) {
            listaHoras.innerHTML = '<span class="text-muted">Selecciona servicios, barbero y fecha para ver horarios.</span>';
            actualizarResumen();
            return;
        }

        listaHoras.innerHTML = '<span class="text-muted">Buscando horarios...</span>';
        const r = await api({ opcion: "disponibilidad", id_empleado: selBarbero.value, fecha: fechaCita.value, servicios: ids });

        listaHoras.innerHTML = r.horas && r.horas.length
            ? r.horas.map((h) => `<button type="button" class="btn-hora" data-hora="${h}">${h}</button>`).join("")
            : '<span class="text-muted">No hay horarios disponibles para ese día. Prueba con otra fecha o barbero.</span>';
        actualizarResumen();
    }

    listaServicios.addEventListener("change", () => { cargarHoras(); });
    selBarbero.addEventListener("change", cargarHoras);
    fechaCita.addEventListener("change", cargarHoras);

    listaHoras.addEventListener("click", (e) => {
        const b = e.target.closest(".btn-hora");
        if (!b) return;
        listaHoras.querySelectorAll(".btn-hora").forEach((x) => x.classList.remove("activo"));
        b.classList.add("activo");
        horaElegida = b.dataset.hora;
        actualizarResumen();
    });

    // Fecha mínima: hoy
    const hoy = new Date();
    fechaCita.min = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, "0")}-${String(hoy.getDate()).padStart(2, "0")}`;

    // ---------- Agendar ----------
    btnAgendar.addEventListener("click", async () => {
        btnAgendar.disabled = true;
        try {
            const r = await api({
                opcion: "agendar",
                id_empleado: selBarbero.value,
                fecha: fechaCita.value,
                hora: horaElegida,
                nota: notaCita.value,
                servicios: serviciosElegidos()
            });

            await Swal.fire({ icon: r.estado ? "success" : "error", title: "Barber-Box", text: r.mensaje });
            if (r.estado) {
                listaServicios.querySelectorAll("input").forEach((i) => (i.checked = false));
                notaCita.value = "";
                fechaCita.value = "";
                await cargarHoras();
                history.replaceState(null, "", "#historial");
                mostrarTab("historial");
            } else {
                cargarHoras();
            }
        } catch (err) {
            console.error(err);
            Swal.fire({ icon: "error", title: "Barber-Box", text: "Error al conectar con el servidor." });
        }
        actualizarResumen();
    });

    // ---------- Historial ----------
    const badge = { Pendiente: "bg-warning text-dark", Completada: "bg-success", Cancelada: "bg-secondary" };

    async function cargarHistorial() {
        const r = await api({ opcion: "historial" });
        if (!r.estado || !r.citas.length) {
            tbodyHistorial.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Aún no tienes citas registradas.</td></tr>';
            return;
        }
        tbodyHistorial.innerHTML = r.citas.map((c) => {
            const [f, h] = c.fecha_hora_inicio.split(" ");
            const futura = new Date(c.fecha_hora_inicio.replace(" ", "T")) > new Date();
            const puedeCancelar = c.estado_cita === "Pendiente" && futura;
            return `
                <tr>
                    <td><div class="fw-semibold">${f.split("-").reverse().join("/")}</div><div class="small text-muted">${h.slice(0, 5)}</div></td>
                    <td>${esc(c.servicios || "—")}</td>
                    <td>${esc(c.barbero)}</td>
                    <td class="text-end fw-semibold">${money(c.total_cita)}</td>
                    <td class="text-center"><span class="badge ${badge[c.estado_cita] || "bg-secondary"}">${esc(c.estado_cita)}</span></td>
                    <td class="text-end">${puedeCancelar ? `<button class="btn btn-sm btn-outline-danger btn-cancelar" data-id="${c.id_cita}">Cancelar</button>` : ""}</td>
                </tr>`;
        }).join("");
    }

    tbodyHistorial.addEventListener("click", async (e) => {
        const b = e.target.closest(".btn-cancelar");
        if (!b) return;
        const ok = await Swal.fire({
            title: "¿Cancelar la cita?", icon: "warning", showCancelButton: true,
            confirmButtonColor: "#dc3545", confirmButtonText: "Sí, cancelar", cancelButtonText: "Volver"
        });
        if (!ok.isConfirmed) return;
        const r = await api({ opcion: "cancelar", id_cita: b.dataset.id });
        Swal.fire({ icon: r.estado ? "success" : "error", title: "Barber-Box", text: r.mensaje, timer: 1500, showConfirmButton: false });
        cargarHistorial();
    });

    // ---------- Inicio ----------
    cargarCatalogo().then(actualizarResumen);
    if (location.hash === "#historial") mostrarTab("historial");
});
