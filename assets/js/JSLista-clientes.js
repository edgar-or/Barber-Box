document.addEventListener("DOMContentLoaded", async () => {
    const contenedor = document.getElementById("tablaClientes");
    const paginacion = document.getElementById("table-pagination");
    const contador = document.getElementById("total-registros");
    const filtro = document.getElementById("table-filter");

    if (!contenedor) return;

    // 1. Inicializar la tabla PRIMERO (con arreglo vacío)
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
                },
                "headerFilters": {
                    "default": "Filtrar columna..."
                }
            }
        },
        data: [],
        layout: "fitColumns",
        responsiveLayout: "hide",
        pagination: true,
        paginationElement: paginacion,
        paginationSize: 10,
        paginationSizeSelector: [10, 25, 50, 100],
        movableColumns: true,
        columns: [
            { title: "Corr", field: "correlativo", width: 80, headerHozAlign: "center", hozAlign: "center" },
            { title: "ID", field: "id_cliente", width: 90, headerHozAlign: "center", hozAlign: "center" },
            {
                title: "Cliente",
                field: "nombreCompleto",
                formatter: function (cell) {
                    const row = cell.getRow().getData();
                    return `
                        <div class="table-user-cell">
                            <img src="../assets/images/avatar.png" alt="${row.nombreCompleto}" class="table-user-avatar" onerror="this.src='../assets/images/avatar.png'">
                            <div>
                                <div class="table-user-name">${row.nombreCompleto || 'Cliente sin nombre'}</div>
                                <div class="table-user-sub">${row.nombre || ''} ${row.apellido || ''}</div>
                            </div>
                        </div>
                    `;
                }
            },
            { title: "Nombre", field: "nombre", visible: false },
            { title: "Apellido", field: "apellido", visible: false },
            { title: "Teléfono", field: "telefono" },
            {
                title: "Correo",
                field: "correo",
                formatter: function (cell) {
                    const v = cell.getValue();
                    return v
                        ? `<span class="celda-correo"><i class="bi bi-envelope"></i>${v}</span>`
                        : '<span class="text-muted">—</span>';
                }
            },
            {
                title: "Acciones",
                headerHozAlign: "center",
                hozAlign: "center",
                formatter: function (cell) {
                    const row = cell.getRow().getData();
                    return `
                        <div class="d-flex justify-content-center gap-1">
                            <button class="table-btn-action btn-editar" data-id="${row.id_cliente}" title="Editar cliente">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="table-btn-action delete btn-eliminar" data-id="${row.id_cliente}" title="Eliminar cliente">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                },
                cellClick: function (e, cell) {
                    const target = e.target.closest("button");
                    if (!target) return;

                    const idCliente = target.getAttribute("data-id");

                    if (target.classList.contains("btn-editar")) {
                        editarCliente(idCliente);
                    } else if (target.classList.contains("btn-eliminar")) {
                        eliminarCliente(idCliente, tabla, contador);
                    }
                }
            }
        ]
    });

    // 2. Cargar registros con Swal de carga (patrón POSEIDÓN)
    function actualizarContador() {
        const total = tabla.getDataCount("active");
        if (contador) contador.textContent = total;
    }

    tabla.on("dataLoaded", actualizarContador);
    tabla.on("dataFiltered", actualizarContador);
    tabla.on("dataChanged", actualizarContador);

    try {
        Swal.fire({
            title: "Cargando...",
            text: "Espere",
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });

        const datos = new FormData();
        datos.append("opcion", "consultar");

        const respuesta = await fetch("/barberBox/DAO/DAOCliente.php", {
            method: "POST",
            body: datos
        });

        if (!respuesta.ok) {
            throw new Error("Error HTTP: " + respuesta.status);
        }

        const registros = await respuesta.json();
        Swal.close();

        if (!Array.isArray(registros)) {
            throw new Error(registros.mensaje || "Respuesta no válida");
        }

        await tabla.replaceData(registros);
        actualizarContador();
    } catch (error) {
        Swal.close();
        console.error("Error al cargar clientes:", error);
        contenedor.textContent = "No se pudieron cargar los registros.";
    }

    // 3. Filtro en tiempo real (input, como POSEIDÓN)
    if (filtro) {
        filtro.addEventListener("input", () => {
            const valor = filtro.value.trim();

            if (!valor) {
                tabla.clearFilter();
                return;
            }

            tabla.setFilter([
                [
                    { field: "nombreCompleto", type: "like", value: valor },
                    { field: "telefono", type: "like", value: valor },
                    { field: "correo", type: "like", value: valor }
                ]
            ]);
        });
    }
});

// Declaración de funciones fuera del DOMContentLoaded para evitar ReferenceError
function editarCliente(id) {
    window.location.href = "form-registrar-cliente.php?id=" + id;
}

function eliminarCliente(id, tabla, contador) {
    Swal.fire({
        title: "Poseidón",
        text: "¿Confirmar eliminación de este cliente?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Sí, Confirmo",
        cancelButtonText: "Cancelar"
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const datos = new FormData();
                datos.append("opcion", "eliminar");
                datos.append("idcliente", id);

                const respuesta = await fetch("/barberBox/DAO/DAOCliente.php", {
                    method: "POST",
                    body: datos
                });

                if (!respuesta.ok) {
                    throw new Error("Error de comunicación: " + respuesta.status);
                }

                const resultado = await respuesta.json();

                if (resultado.estado) {
                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: "Poseidón",
                        text: resultado.mensaje,
                        showConfirmButton: false,
                        timer: 1500
                    });

                    // Eliminar la fila de la tabla sin recargar (patrón Barber-Box)
                    if (tabla) {
                        const fila = tabla.getRows().find(row => row.getData().id_cliente == id);
                        if (fila) fila.delete();
                    }

                    if (contador) {
                        const total = tabla.getDataCount("active");
                        contador.textContent = total;
                    }
                } else {
                    Swal.fire({
                        position: "top-end",
                        icon: "error",
                        title: "Poseidón",
                        text: resultado.mensaje,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({
                    icon: "error",
                    title: "Poseidón",
                    text: "Error al conectar con el servidor."
                });
            }
        }
    });
}