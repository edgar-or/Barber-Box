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
                }
            }
        },
        data: [], 
        layout: "fitColumns", // o "fitDataFill"
        responsiveLayout: "hide",
        pagination: true,
        paginationElement: paginacion,
        paginationSize: 10,
        paginationSizeSelector: [10, 25, 50, 100],
        movableColumns: true,
        columns: [
            { title: "ID", field: "id_cliente", width: 70, headerHozAlign: "center", hozAlign: "center" },
            { 
                title: "Cliente", 
                field: "nombreCompleto", 
                formatter: function(cell) {
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
            { title: "Correo", field: "correo" },
            { 
                title: "Acciones", 
                headerHozAlign: "center", 
                hozAlign: "center",
                formatter: function(cell) {
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
                cellClick: function(e, cell) {
                    const target = e.target.closest("button");
                    if (!target) return;

                    const idCliente = target.getAttribute("data-id");
                    
                    if (target.classList.contains("btn-editar")) {
                        editarCliente(idCliente);
                    } else if (target.classList.contains("btn-eliminar")) {
                        eliminarCliente(idCliente);
                    }
                }
            }
        ]
    });

    // 2. Hacer la petición FETCH a DAOCliente.php y cargar con setData()
    try {
        const formData = new FormData();
        formData.append("opcion", "consultar");

        const respuesta = await fetch("../DAO/DAOCliente.php", {
            method: "POST",
            body: formData
        });

        if (!respuesta.ok) throw new Error("Error en la respuesta del servidor");

        const datosClientes = await respuesta.json();
        console.log("Respuesta obtenida del DAO:", datosClientes);

        if (Array.isArray(datosClientes)) {
            await tabla.setData(datosClientes);
            if (contador) {
                contador.textContent = tabla.getDataCount();
            }
        } else {
            console.warn("El DAO no devolvió un arreglo válido:", datosClientes);
        }

    } catch (error) {
        console.error("Error al cargar clientes:", error);
    }

    // 3. Filtro en tiempo real si existe el input
    if (filtro) {
        filtro.addEventListener("keyup", function () {
            tabla.setFilter([
                [
                    { field: "nombreCompleto", type: "like", value: filtro.value },
                    { field: "telefono", type: "like", value: filtro.value }
                ]
            ]);
        });
    }
});

// Declaración de funciones fuera del DOMContentLoaded para evitar ReferenceError
function editarCliente(id) {
    console.log("Editar cliente ID:", id);
}

function eliminarCliente(id) {
    console.log("Eliminar cliente ID:", id);
}