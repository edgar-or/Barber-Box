document.addEventListener("DOMContentLoaded", async () => {
    const contenedor = document.getElementById("tablaServicios");
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
        layout: "fitColumns",
        responsiveLayout: "hide",
        pagination: true,
        paginationElement: paginacion, 
        paginationSize: 10,
        paginationSizeSelector: [10, 25, 50, 100],
        movableColumns: true,
        columns: [
            { title: "ID", field: "id_servicio", width: 70, headerHozAlign: "center", hozAlign: "center" },
            { title: "Servicio", field: "nombre_servicio", width: 200 },
            { title: "Descripción", field: "descripcion" },
            { 
                title: "Precio", 
                field: "precio", 
                width: 110,
                hozAlign: "right",
                headerHozAlign: "right",
                formatter: function(cell) {
                    const valor = parseFloat(cell.getValue()) || 0;
                    return `$${valor.toFixed(2)}`;
                }
            },
            { 
                title: "Duración", 
                field: "duracion_estimada", 
                width: 120,
                hozAlign: "center",
                headerHozAlign: "center",
                formatter: function(cell) {
                    return `${cell.getValue()} min`;
                }
            },
            { 
                title: "Estado", 
                field: "estado_servicio", 
                width: 100,
                hozAlign: "center",
                headerHozAlign: "center",
                formatter: function(cell) {
                    const estado = cell.getValue();
                    return estado === 'A' 
                        ? '<span class="badge bg-success">Activo</span>' 
                        : '<span class="badge bg-danger">Inactivo</span>';
                }
            },
            { 
                title: "Acciones", 
                width: 130,
                headerHozAlign: "center", 
                hozAlign: "center",
                formatter: function(cell) {
                    const row = cell.getRow().getData();
                    return `
                        <div class="d-flex justify-content-center gap-1">
                            <button class="table-btn-action btn-editar" data-id="${row.id_servicio}" title="Editar servicio">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="table-btn-action delete btn-eliminar" data-id="${row.id_servicio}" title="Eliminar servicio">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                },
                cellClick: function(e, cell) {
                    const target = e.target.closest("button");
                    if (!target) return;

                    const idServicio = target.getAttribute("data-id");
                    
                    if (target.classList.contains("btn-editar")) {
                        editarServicio(idServicio);
                    } else if (target.classList.contains("btn-eliminar")) {
                        eliminarServicio(idServicio, tabla); 
                    }
                }
            }
        ]
    });

    // 2. Hacer la petición FETCH al DAO de Servicios
    try {
        const formData = new FormData();
        formData.append("opcion", "consultar");

        const respuesta = await fetch("../DAO/DAOServicios.php", {
            method: "POST",
            body: formData
        });

        if (!respuesta.ok) throw new Error("Error en la respuesta del servidor");

        const datosServicios = await respuesta.json();
        console.log("Respuesta obtenida del DAO:", datosServicios);

        if (Array.isArray(datosServicios)) {
            await tabla.setData(datosServicios);
            if (contador) {
                contador.textContent = tabla.getDataCount();
            }
        } else {
            console.warn("El DAO no devolvió un arreglo válido:", datosServicios);
        }

    } catch (error) {
        console.error("Error al cargar servicios:", error);
    }

    // 3. Filtro en tiempo real si existe el input
    if (filtro) {
        filtro.addEventListener("keyup", function () {
            tabla.setFilter([
                [
                    { field: "nombre_servicio", type: "like", value: filtro.value },
                    { field: "descripcion", type: "like", value: filtro.value }
                ]
            ]);
        });
    }
});

// Funciones globales para Editar y Eliminar
function editarServicio(id) {
    window.location.href = "form-registrar-servicios.php?id=" + id;
}

function eliminarServicio(id, tabla) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¡El servicio será eliminado permanentemente!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const datos = new FormData();
                datos.append("opcion", "eliminar");
                datos.append("idservicio", id);

                const respuesta = await fetch("../DAO/DAOServicios.php", {
                    method: "POST",
                    body: datos
                });

                const resultado = await respuesta.json();

                if (resultado.estado) {
                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: "Barber-Box",
                        text: resultado.mensaje,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    
                    if (tabla) {
                        const fila = tabla.getRows().find(row => row.getData().id_servicio == id);
                        if (fila) fila.delete();
                    }

                } else {
                    Swal.fire({
                        position: "top-end",
                        icon: "error",
                        title: "Barber-Box",
                        text: resultado.mensaje,
                        showConfirmButton: false,
                        timer: 1500
                    });
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