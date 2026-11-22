document.addEventListener("DOMContentLoaded", async () => {
    const contenedor = document.getElementById("tablaEmpleados");
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
            { title: "ID", field: "id_empleado", width: 90, headerHozAlign: "center", hozAlign: "center" },
            {
                title: "Empleado",
                field: "nombreCompleto",
                formatter: function (cell) {
                    const row = cell.getRow().getData();
                    return `
                        <div class="table-user-cell">
                            <img src="../assets/images/avatar.png" alt="${row.nombreCompleto}" class="table-user-avatar" onerror="this.src='../assets/images/avatar.png'">
                            <div>
                                <div class="table-user-name">${row.nombreCompleto || 'Empleado sin nombre'}</div>
                                <div class="table-user-sub">${row.nombre || ''} ${row.apellido || ''}</div>
                            </div>
                        </div>
                    `;
                }
            },
            { title: "Nombre", field: "nombre", visible: false },
            { title: "Apellido", field: "apellido", visible: false },
            { title: "Teléfono", field: "telefono" },
            { title: "DUI", field: "dui", width: 120 },
            {
                title: "Correo",
                field: "correo",
                formatter: function (cell) {
                    const v = cell.getValue();
                    return v ? `<span class="celda-correo"><i class="bi bi-envelope"></i>${v}</span>` : '<span class="text-muted">—</span>';
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
                      <button class="table-btn-action btn-editar" data-id="${row.id_empleado}" title="Editar empleado">
                     <i class="bi bi-pencil"></i>
                     </button>
                     <button class="table-btn-action delete btn-eliminar" data-id="${row.id_empleado}" title="Eliminar empleado">
                      <i class="bi bi-trash"></i>
                     </button>
                      <button class="table-btn-action btn-horario" data-id="${row.id_empleado}" title="Gestionar Horario">
                       <i class="bi bi-clock"></i>
                        </button>
                        </div>
                    `;
                },
                cellClick: function (e, cell) {
                    const target = e.target.closest("button");
                    if (!target) return;

                    const idEmpleado = target.getAttribute("data-id");

                    if (target.classList.contains("btn-editar")) {
                        editarEmpleado(idEmpleado);
                    } else if (target.classList.contains("btn-eliminar")) {
                        eliminarEmpleado(idEmpleado, tabla);
                    } else if (target.classList.contains("btn-horario")) {
                        window.location.href = "form-horarios-empleado.php?id=" + idEmpleado;
                    }
                }
            }
        ]
    });

    // 2. Hacer la petición FETCH
    try {
        const formData = new FormData();
        formData.append("opcion", "consultar");

        const respuesta = await fetch("../DAO/DAOEmpleado.php", {
            method: "POST",
            body: formData
        });

        if (!respuesta.ok) throw new Error("Error en la respuesta del servidor");

        const datosEmpleados = await respuesta.json();
        console.log("Respuesta obtenida del DAO:", datosEmpleados);

        if (Array.isArray(datosEmpleados)) {
            await tabla.setData(datosEmpleados);
            if (contador) {
                contador.textContent = tabla.getDataCount();
            }
        } else {
            console.warn("El DAO no devolvió un arreglo válido:", datosEmpleados);
        }

    } catch (error) {
        console.error("Error al cargar empleados:", error);
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
function editarEmpleado(id) {
    window.location.href = "form-registrar-empleados.php?id=" + id;
}

function eliminarEmpleado(id, tabla) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¡El empleado será eliminado permanentemente!",
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
                datos.append("idempleado", id);

                const respuesta = await fetch("../DAO/DAOEmpleado.php", {
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
                        const fila = tabla.getRows().find(row => row.getData().id_empleado == id);
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
    const tabla = new Tabulator(contenedor, {
        locale: "es-es",
        langs: { /* ... tu configuración de idioma ... */ },
        data: [], 
        layout: "fitColumns",
        responsiveLayout: "hide",
        pagination: true,
        paginationElement: paginacion,
        paginationSize: 10,
        paginationSizeSelector: [10, 25, 50, 100],
        movableColumns: true,
        
        // ✅ AGREGAR ESTAS DOS LÍNEAS PARA EL MODO OSCURO EN TABULATOR
        theme: "midnight", // o "dark" si tienes la versión más nueva
        // ------------------------------------------------

        columns: [
            // ... tus columnas
        ]
    });