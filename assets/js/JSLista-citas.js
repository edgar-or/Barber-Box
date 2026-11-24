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
            width: 150,
            headerSort: false,
            headerHozAlign: "center",
            hozAlign: "center",
            formatter: function (cell) {
                const row = cell.getRow().getData();
                const pendiente = row.estado_cita === "Pendiente";
                return `
                    <div class="d-flex justify-content-center gap-1">
                        <button class="table-btn-action btn-detalle" data-id="${row.id_cita}" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </button>
                        ${pendiente ? `
                        <button class="table-btn-action btn-completar" data-id="${row.id_cita}" title="Procesar cobro y completar">
                            <i class="bi bi-cash-coin"></i>
                        </button>
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