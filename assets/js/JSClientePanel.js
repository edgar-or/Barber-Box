document.addEventListener("DOMContentLoaded", async () => {
    const listaServicios = document.getElementById("listaServicios");
    const selBarbero = document.getElementById("selBarbero");
    const fechaCita = document.getElementById("fechaCita");
    const listaHoras = document.getElementById("listaHoras");
    const notaCita = document.getElementById("notaCita");
    const btnAgendar = document.getElementById("btnAgendar");
    const tbodyHistorial = document.getElementById("tbodyHistorial");

    if (!listaServicios) return;

    let catalogo = { servicios: [], barberos: [] };
    let horaElegida = null;

    const badge = { Pendiente: "bg-warning text-dark", Completada: "bg-success", Cancelada: "bg-secondary" };

    // Fecha mínima: hoy
    const hoy = new Date();
    fechaCita.min = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, "0")}-${String(hoy.getDate()).padStart(2, "0")}`;

    // 1. Catálogo de servicios y barberos
    async function cargarCatalogo() {
        try {
            const formData = new FormData();
            formData.append("opcion", "catalogo");

            const respuesta = await fetch("../DAO/DAOCita.php", {
                method: "POST",
                body: formData
            });

            if (!respuesta.ok) throw new Error("Error en la respuesta del servidor");

            const datos = await respuesta.json();
            console.log("Respuesta obtenida del DAO:", datos);

            if (!datos.estado) return;
            catalogo = datos;

            listaServicios.innerHTML = datos.servicios.length ? datos.servicios.map((s) => `
                <div class="col-12 col-md-6">
                    <label class="servicio-opcion w-100">
                        <input type="checkbox" value="${s.id_servicio}" class="d-none">
                        <div class="servicio-opcion-body">
                            <div>
                                <div class="fw-bold">${esc(s.nombre_servicio)}</div>
                                <div class="small text-muted">${esc(s.descripcion)}</div>
                                <div class="small text-muted"><i class="bi bi-clock me-1"></i>${parseInt(s.duracion_estimada, 10)} min</div>
                            </div>
                            <div class="servicio-precio">${money(s.precio)}</div>
                            <i class="bi bi-check-circle-fill servicio-check"></i>
                        </div>
                    </label>
                </div>`).join("") : '<div class="col-12 text-muted">No hay servicios disponibles por ahora.</div>';

            selBarbero.innerHTML = '<option value="">Selecciona un barbero</option>' +
                datos.barberos.map((b) => `<option value="${b.id_empleado}">${esc(b.nombre)}</option>`).join("");
        } catch (error) {
            console.error("Error al cargar el catálogo:", error);
        }
    }

    // 2. Resumen de la cita
    function serviciosElegidos() {
        return [...listaServicios.querySelectorAll("input:checked")].map((i) => parseInt(i.value, 10));
    }

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

    // 3. Horarios disponibles
    async function cargarHoras() {
        horaElegida = null;
        const ids = serviciosElegidos();

        if (!ids.length || !selBarbero.value || !fechaCita.value) {
            listaHoras.innerHTML = '<span class="text-muted">Selecciona servicios, barbero y fecha para ver horarios.</span>';
            actualizarResumen();
            return;
        }

        listaHoras.innerHTML = '<span class="text-muted">Buscando horarios...</span>';

        try {
            const formData = new FormData();
            formData.append("opcion", "disponibilidad");
            formData.append("id_empleado", selBarbero.value);
            formData.append("fecha", fechaCita.value);
            ids.forEach((id) => formData.append("servicios[]", id));

            const respuesta = await fetch("../DAO/DAOCita.php", {
                method: "POST",
                body: formData
            });

            if (!respuesta.ok) throw new Error("Error en la respuesta del servidor");

            const datos = await respuesta.json();

            listaHoras.innerHTML = datos.horas && datos.horas.length
                ? datos.horas.map((h) => `<button type="button" class="btn-hora" data-hora="${h}">${h}</button>`).join("")
                : '<span class="text-muted">No hay horarios disponibles para ese día. Prueba con otra fecha o barbero.</span>';
        } catch (error) {
            console.error("Error al cargar horarios:", error);
            listaHoras.innerHTML = '<span class="text-danger">No se pudieron cargar los horarios.</span>';
        }
        actualizarResumen();
    }

    listaServicios.addEventListener("change", cargarHoras);
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

    // 4. Historial de citas
    async function cargarHistorial() {
        try {
            const formData = new FormData();
            formData.append("opcion", "historial");

            const respuesta = await fetch("../DAO/DAOCita.php", {
                method: "POST",
                body: formData
            });

            if (!respuesta.ok) throw new Error("Error en la respuesta del servidor");

            const datos = await respuesta.json();
            console.log("Respuesta obtenida del DAO:", datos);

            if (!datos.estado || !datos.citas.length) {
                tbodyHistorial.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Aún no tienes citas registradas.</td></tr>';
                return;
            }

            tbodyHistorial.innerHTML = datos.citas.map((c) => {
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
        } catch (error) {
            console.error("Error al cargar el historial:", error);
            tbodyHistorial.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">No se pudo cargar el historial.</td></tr>';
        }
    }

    tbodyHistorial.addEventListener("click", (e) => {
        const b = e.target.closest(".btn-cancelar");
        if (!b) return;
        cancelarCita(b.getAttribute("data-id"), cargarHistorial);
    });

    // 5. Agendar cita
    btnAgendar.addEventListener("click", async () => {
        btnAgendar.disabled = true;

        try {
            const formData = new FormData();
            formData.append("opcion", "agendar");
            formData.append("id_empleado", selBarbero.value);
            formData.append("fecha", fechaCita.value);
            formData.append("hora", horaElegida);
            formData.append("nota", notaCita.value);
            serviciosElegidos().forEach((id) => formData.append("servicios[]", id));

            const respuesta = await fetch("../DAO/DAOCita.php", {
                method: "POST",
                body: formData
            });

            const resultado = await respuesta.json();

            await Swal.fire({
                icon: resultado.estado ? "success" : "error",
                title: "Barber-Box",
                text: resultado.mensaje
            });

            if (resultado.estado) {
                listaServicios.querySelectorAll("input").forEach((i) => (i.checked = false));
                notaCita.value = "";
                fechaCita.value = "";
                await cargarHoras();
                history.replaceState(null, "", "#historial");
                mostrarTab("historial");
            } else {
                cargarHoras();
            }
        } catch (error) {
            console.error(error);
            Swal.fire({
                icon: "error",
                title: "Barber-Box",
                text: "Error al conectar con el servidor."
            });
        }
        actualizarResumen();
    });

    // 6. Tabs enlazados con el menú lateral
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

    // 7. Inicio
    await cargarCatalogo();
    actualizarResumen();
    if (location.hash === "#historial") mostrarTab("historial");
});

// Funciones fuera del DOMContentLoaded
function esc(texto) {
    return String(texto ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
}

function money(n) {
    return "$" + (parseFloat(n) || 0).toFixed(2);
}

function cancelarCita(id, recargar) {
    Swal.fire({
        title: '¿Cancelar la cita?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'Volver'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const datos = new FormData();
                datos.append("opcion", "cancelar");
                datos.append("id_cita", id);

                const respuesta = await fetch("../DAO/DAOCita.php", {
                    method: "POST",
                    body: datos
                });

                const resultado = await respuesta.json();

                Swal.fire({
                    position: "top-end",
                    icon: resultado.estado ? "success" : "error",
                    title: "Barber-Box",
                    text: resultado.mensaje,
                    showConfirmButton: false,
                    timer: 1500
                });

                if (recargar) recargar();
            } catch (error) {
                console.error(error);
                Swal.fire({
                    icon: "error",
                    title: "Barber-Box",
                    text: "Error al conectar con el servidor."
                });
            }
        }
    });
}