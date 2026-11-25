document.addEventListener("DOMContentLoaded", async () => {
    const contenedor = document.getElementById("tablaCitas");
    const paginacion = document.getElementById("table-pagination");
    const contador = document.getElementById("total-registros");
    const filtro = document.getElementById("table-filter");
    const filtroEstado = document.getElementById("filtroEstado");
    const esAdmin = window.CITAS_ES_ADMIN === true;

    if (!contenedor) return;

    // 1. Columnas (el barbero solo se muestra al administrador)
    const columnas = [
        {
            title: "Fecha",
            field: "fecha_hora_inicio",
            width: 140,
            formatter: function (cell) {
                const d = fmtFecha(cell.getValue());
                return `
                    <div class="table-user-cell">
                        <div>
                            <div class="table-user-name">${d.f}</div>
                            <div class="table-user-sub">${d.h}</div>
                        </div>
                    </div>
                `;
            }
        },
        {
            title: "Cliente",
            field: "cliente",
            formatter: function (cell) {
                const row = cell.getRow().getData();
                return `
                    <div class="table-user-cell">
                        <div>
                            <div class="table-user-name">${esc(row.cliente)}</div>
                            <div class="table-user-sub">${esc(row.telefono)}</div>
                        </div>
                    </div>
                `;
            }
        }
    ];

    if (esAdmin) {
        columnas.push({ title: "Barbero", field: "barbero" });
    }

    columnas.push(
        {
            title: "Servicios",
            field: "servicios",
            formatter: function (cell) {
                return `<span title="${esc(cell.getValue())}">${esc(cell.getValue() || "—")}</span>`;
            }
        },
        {
            title: "Total",
            field: "total_cita",
            width: 100,
            headerHozAlign: "right",
            hozAlign: "right",
            formatter: function (cell) {
                return `<strong class="celda-precio">${money(cell.getValue())}</strong>`;
            }
        },
        {
            title: "Estado",
            field: "estado_cita",
            width: 150,
            headerHozAlign: "center",
            hozAlign: "center",
            formatter: function (cell) {
                const estado = cell.getValue();
                return `<span class="badge-table ${claseEstado[estado] || ""}">${esc(estado)}</span>`;
            }
        },
        {
            title: "Acciones",
            width: esAdmin ? 150 : 195,
            headerSort: false,
            headerHozAlign: "center",
            hozAlign: "center",
            formatter: function (cell) {
                const row = cell.getRow().getData();
                const pendiente = row.estado_cita === "Pendiente";
                // Una cita en curso también se puede modificar (servicio adicional): vale de hoy en adelante
                const hoy = new Date();
                const hoyStr = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, "0")}-${String(hoy.getDate()).padStart(2, "0")}`;
                const deHoyEnAdelante = row.fecha_hora_inicio.split(" ")[0] >= hoyStr;

                // Solo el barbero modifica servicios, y solo en citas pendientes de hoy en adelante
                const puedeModificar = pendiente && deHoyEnAdelante && !esAdmin;

                return `
                    <div class="d-flex justify-content-center gap-1">
                        <button class="table-btn-action btn-detalle" data-id="${row.id_cita}" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </button>
                        ${pendiente ? `
                        <button class="table-btn-action btn-completar" data-id="${row.id_cita}" title="Procesar cobro y completar">
                            <i class="bi bi-cash-coin"></i>
                        </button>` : ""}
                        ${puedeModificar ? `
                        <button class="table-btn-action btn-modificar" data-id="${row.id_cita}" title="Modificar servicios">
                            <i class="bi bi-pencil"></i>
                        </button>` : ""}
                        ${pendiente ? `
                        <button class="table-btn-action delete btn-cancelar" data-id="${row.id_cita}" title="Cancelar cita">
                            <i class="bi bi-x-circle"></i>
                        </button>` : ""}
                    </div>
                `;
            },
            cellClick: function (e, cell) {
                const target = e.target.closest("button");
                if (!target) return;

                const idCita = target.getAttribute("data-id");

                if (target.classList.contains("btn-detalle")) {
                    verDetalle(idCita);
                } else if (target.classList.contains("btn-completar")) {
                    // Redirige directamente a la vista dedicada de pago
                    window.location.href = `cobrar-cita.php?id=${idCita}`;
                } else if (target.classList.contains("btn-modificar")) {
                    abrirModificar(cell.getRow().getData(), recargarTabla);
                } else if (target.classList.contains("btn-cancelar")) {
                    cambiarEstado(idCita, "Cancelada", recargarTabla);
                }
            }
        }
    );

    // 2. Inicializar la tabla PRIMERO (con arreglo vacío)
    const tabla = new Tabulator(contenedor, {
        locale: "es-es",
        langs: {
            "es-es": {
                "pagination": {
                    "page_size": "Registros por página",
                    "first": "Primera",
                    "first_title": "Primera página",
                    "last": "Última",
                    "last_title": "Última página",
                    "prev": "Anterior",
                    "prev_title": "Página anterior",
                    "next": "Siguiente",
                    "next_title": "Página siguiente",
                    "all": "Todos",
                    "counter": {
                        "showing": "Mostrando",
                        "of": "de",
                        "rows": "registros",
                        "pages": "páginas"
                    }
                },
                "data": {
                    "loading": "Cargando...",
                    "error": "Error al cargar los datos"
                }
            }
        },
        data: [],
        layout: "fitColumns",
        placeholder: "No hay citas para mostrar",
        pagination: true,
        paginationElement: paginacion,
        paginationSize: 10,
        paginationSizeSelector: [10, 25, 50, 100],
        initialSort: [{ column: "fecha_hora_inicio", dir: "desc" }],
        columns: columnas
    });

    // 3. Hacer la petición FETCH a DAOCitas.php y cargar con setData()
    async function cargarCitas() {
        try {
            const formData = new FormData();
            formData.append("opcion", "consultar");

            const respuesta = await fetch("../DAO/DAOCitas.php", {
                method: "POST",
                body: formData
            });

            if (!respuesta.ok) throw new Error("Error en la respuesta del servidor");

            const datosCitas = await respuesta.json();

            if (datosCitas.estado && Array.isArray(datosCitas.citas)) {
                await tabla.setData(datosCitas.citas);
                if (contador) {
                    contador.textContent = tabla.getDataCount();
                }
            } else {
                console.warn("El DAO no devolvió un arreglo válido:", datosCitas);
            }
        } catch (error) {
            console.error("Error al cargar citas:", error);
        }
    }

    // Recarga los datos y vuelve a aplicar los filtros activos
    async function recargarTabla() {
        await cargarCitas();
        aplicarFiltros();
    }

    // 4. Filtros en tiempo real (texto + estado)
    function aplicarFiltros() {
        const texto = filtro ? filtro.value.trim().toLowerCase() : "";
        const estado = filtroEstado ? filtroEstado.value : "";

        tabla.setFilter(function (datos) {
            if (estado && datos.estado_cita !== estado) return false;
            if (!texto) return true;

            return [datos.cliente, datos.barbero, datos.servicios, datos.telefono]
                .some((v) => String(v || "").toLowerCase().includes(texto));
        });

        if (contador) {
            contador.textContent = tabla.getDataCount("active");
        }
    }

    if (filtro) filtro.addEventListener("keyup", aplicarFiltros);
    if (filtroEstado) filtroEstado.addEventListener("change", aplicarFiltros);

    await cargarCitas();
});

// Declaración de funciones fuera del DOMContentLoaded para evitar ReferenceError
const claseEstado = { Pendiente: "pending", Completada: "success", Cancelada: "failed" };

function esc(texto) {
    return String(texto ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
}

function money(n) {
    return "$" + (parseFloat(n) || 0).toFixed(2);
}

function fmtFecha(fechaHora) {
    const [f, h] = fechaHora.split(" ");
    return { f: f.split("-").reverse().join("/"), h: h.slice(0, 5) };
}

async function verDetalle(id) {
    try {
        const datos = new FormData();
        datos.append("opcion", "detalle");
        datos.append("id_cita", id);

        const respuesta = await fetch("../DAO/DAOCitas.php", {
            method: "POST",
            body: datos
        });

        if (!respuesta.ok) throw new Error("Error en la respuesta del servidor");

        const resultado = await respuesta.json();

        if (!resultado.estado) {
            Swal.fire({
                icon: "error",
                title: "Barber-Box",
                text: resultado.mensaje
            });
            return;
        }

        const c = resultado.cita;
        const ini = fmtFecha(c.fecha_hora_inicio);
        const fin = fmtFecha(c.fecha_hora_fin);

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
                ${resultado.detalle.map((d) => `<li><span>${esc(d.nombre_servicio)} <small class="text-muted">· ${parseInt(d.duracion_estimada, 10)} min</small></span><strong>${money(d.precio_unitario)}</strong></li>`).join("")}
                <li class="total"><span>Total</span><strong>${money(c.total_cita)}</strong></li>
            </ul>`;

        bootstrap.Modal.getOrCreateInstance(document.getElementById("modalDetalle")).show();
    } catch (error) {
        console.error(error);
        Swal.fire({
            icon: "error",
            title: "Barber-Box",
            text: "Error al conectar con el servidor."
        });
    }
}

function cambiarEstado(id, estado, recargar) {
    const cancelar = estado === "Cancelada";

    Swal.fire({
        title: "¿Cancelar la cita?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Sí, cancelar",
        cancelButtonText: "Volver"
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const datos = new FormData();
                datos.append("opcion", "cambiarEstado");
                datos.append("id_cita", id);
                datos.append("estado", estado);

                const respuesta = await fetch("../DAO/DAOCitas.php", {
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

                if (resultado.estado && recargar) {
                    await recargar();
                }
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

// ================== MODIFICAR SERVICIOS DE UNA CITA (solo barbero) ==================
let catalogoServicios = [];
let citaModificando = null;
let recargarAlGuardar = null;
let tokenVerificacion = 0;

// Crea el modal una sola vez (no hace falta tocar tabla-citas.php)
function asegurarModalModificar() {
    if (document.getElementById("modalModificar")) return;

    const html = `
      <div class="modal fade" id="modalModificar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title fw-bold">Modificar servicios de la cita</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <div id="modificarInfo" class="mb-3"></div>
              <div id="modificarServicios" class="row g-2"></div>
              <div id="modificarResumen" class="mt-3"></div>
              <div id="modificarAviso" class="mt-2"></div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
              <button type="button" class="btn btn-danger fw-bold" id="btnGuardarModificar" disabled>Guardar cambios</button>
            </div>
          </div>
        </div>
      </div>`;

    document.body.insertAdjacentHTML("beforeend", html);

    // Cada vez que se marca o desmarca un servicio se vuelve a verificar la disponibilidad
    document.getElementById("modificarServicios").addEventListener("change", verificarServicios);
    document.getElementById("btnGuardarModificar").addEventListener("click", guardarModificar);
}

async function cargarCatalogoServicios() {
    const datos = new FormData();
    datos.append("opcion", "catalogo");

    const respuesta = await fetch("../DAO/DAOCitas.php", {
        method: "POST",
        body: datos
    });

    if (!respuesta.ok) {
        throw new Error("Error HTTP: " + respuesta.status);
    }

    const resultado = await respuesta.json();

    if (!resultado.estado || !Array.isArray(resultado.servicios)) {
        throw new Error(resultado.mensaje || "Respuesta no válida");
    }

    catalogoServicios = resultado.servicios;
}

function serviciosMarcados() {
    return [...document.querySelectorAll("#modificarServicios input:checked")].map((i) =>
        parseInt(i.value, 10)
    );
}

async function abrirModificar(cita, recargar) {
    try {
        asegurarModalModificar();

        if (!catalogoServicios.length) {
            await cargarCatalogoServicios();
        }

        citaModificando = cita;
        recargarAlGuardar = recargar;

        const ids = String(cita.servicios_ids || "")
            .split(",")
            .map((x) => parseInt(x, 10));
        const ini = fmtFecha(cita.fecha_hora_inicio);

        document.getElementById("modificarInfo").innerHTML = `
            <div class="small text-muted">Cliente</div>
            <div class="fw-semibold">${esc(cita.cliente)}</div>
            <div class="small text-muted mt-2">Cita del ${ini.f} a las ${ini.h}. La fecha y la hora no cambian: solo puedes agregar o quitar servicios.</div>`;

        document.getElementById("modificarServicios").innerHTML = catalogoServicios
            .map((s) => `
                <div class="col-12 col-md-6">
                    <div class="form-check border rounded p-2 ps-5">
                        <input class="form-check-input" type="checkbox" id="modSrv-${s.id_servicio}" value="${s.id_servicio}"
                            ${ids.includes(parseInt(s.id_servicio, 10)) ? "checked" : ""}>
                        <label class="form-check-label w-100 d-flex justify-content-between gap-2" for="modSrv-${s.id_servicio}">
                            <span>${esc(s.nombre_servicio)} <small class="text-muted">· ${parseInt(s.duracion_estimada, 10)} min</small></span>
                            <strong>${money(s.precio)}</strong>
                        </label>
                    </div>
                </div>`)
            .join("");

        bootstrap.Modal.getOrCreateInstance(document.getElementById("modalModificar")).show();
        await verificarServicios();
    } catch (error) {
        console.error(error);
        Swal.fire({
            icon: "error",
            title: "Barber-Box",
            text: "No se pudieron cargar los servicios."
        });
    }
}

// Muestra duración, total y hora de fin, y comprueba que la misma hora de inicio siga libre
async function verificarServicios() {
    const btnGuardar = document.getElementById("btnGuardarModificar");
    const aviso = document.getElementById("modificarAviso");
    const resumen = document.getElementById("modificarResumen");
    const token = ++tokenVerificacion;

    const ids = serviciosMarcados();
    const elegidos = catalogoServicios.filter((s) => ids.includes(parseInt(s.id_servicio, 10)));
    const duracion = elegidos.reduce((a, s) => a + parseInt(s.duracion_estimada, 10), 0);
    const total = elegidos.reduce((a, s) => a + parseFloat(s.precio), 0);

    const [fecha, hora] = citaModificando.fecha_hora_inicio.split(" ");
    const horaActual = hora.slice(0, 5);

    const fin = new Date(citaModificando.fecha_hora_inicio.replace(" ", "T"));
    fin.setMinutes(fin.getMinutes() + duracion);
    const horaFin = String(fin.getHours()).padStart(2, "0") + ":" + String(fin.getMinutes()).padStart(2, "0");

    resumen.innerHTML = `
        <div class="d-flex justify-content-between"><span>Duración</span><strong>${duracion} min</strong></div>
        <div class="d-flex justify-content-between"><span>Termina a las</span><strong>${ids.length ? horaFin : "—"}</strong></div>
        <div class="d-flex justify-content-between"><span>Total</span><strong>${money(total)}</strong></div>`;

    if (!ids.length) {
        aviso.innerHTML = '<div class="alert alert-warning py-2 mb-0">La cita debe tener al menos un servicio.</div>';
        btnGuardar.disabled = true;
        return;
    }

    btnGuardar.disabled = true;
    aviso.innerHTML = '<span class="text-muted">Verificando disponibilidad...</span>';

    try {
        const datos = new FormData();
        datos.append("opcion", "disponibilidad");
        datos.append("id_cita", citaModificando.id_cita);
        datos.append("fecha", fecha);
        ids.forEach((id) => datos.append("servicios[]", id));

        const respuesta = await fetch("../DAO/DAOCitas.php", {
            method: "POST",
            body: datos
        });

        if (!respuesta.ok) {
            throw new Error("Error HTTP: " + respuesta.status);
        }

        const resultado = await respuesta.json();

        // Si el usuario marcó otro servicio mientras tanto, se descarta esta respuesta
        if (token !== tokenVerificacion) return;

        const cabe = resultado.estado && Array.isArray(resultado.horas) && resultado.horas.includes(horaActual);

        if (cabe) {
            aviso.innerHTML = '<div class="alert alert-success py-2 mb-0"><i class="bi bi-check-circle me-1"></i>Con esos servicios la cita cabe en su horario.</div>';
            btnGuardar.disabled = false;
        } else {
            aviso.innerHTML = '<div class="alert alert-danger py-2 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Con esos servicios la cita choca con otra cita o se pasa del horario del barbero.</div>';
            btnGuardar.disabled = true;
        }
    } catch (error) {
        if (token !== tokenVerificacion) return;
        console.error(error);
        aviso.innerHTML = '<div class="alert alert-danger py-2 mb-0">No se pudo verificar la disponibilidad.</div>';
        btnGuardar.disabled = true;
    }
}

async function guardarModificar() {
    const btnGuardar = document.getElementById("btnGuardarModificar");
    const ids = serviciosMarcados();

    if (!ids.length) {
        Swal.fire({
            icon: "warning",
            title: "Atención",
            text: "La cita debe tener al menos un servicio."
        });
        return;
    }

    try {
        const datos = new FormData();
        datos.append("opcion", "modificar");
        datos.append("id_cita", citaModificando.id_cita);
        ids.forEach((id) => datos.append("servicios[]", id));

        btnGuardar.disabled = true;
        btnGuardar.textContent = "Procesando...";

        const respuesta = await fetch("../DAO/DAOCitas.php", {
            method: "POST",
            body: datos
        });

        if (!respuesta.ok) {
            throw new Error("Error HTTP: " + respuesta.status);
        }

        const resultado = await respuesta.json();

        Swal.fire({
            position: "top-end",
            icon: resultado.estado ? "success" : "error",
            title: "Barber-Box",
            text: resultado.mensaje,
            showConfirmButton: false,
            timer: resultado.estado ? 1500 : 3000
        });

        if (resultado.estado) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById("modalModificar")).hide();
            if (recargarAlGuardar) await recargarAlGuardar();
        } else {
            await verificarServicios();
        }
    } catch (error) {
        console.error(error);
        Swal.fire({
            icon: "error",
            title: "Barber-Box",
            text: "Error al conectar con el servidor."
        });
        btnGuardar.disabled = false;
    } finally {
        btnGuardar.textContent = "Guardar cambios";
    }
}