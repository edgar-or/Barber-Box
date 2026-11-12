document.addEventListener("DOMContentLoaded", async () => {
    const parametros = new URLSearchParams(window.location.search);
    const idEmpleado = parametros.get("id");
    const tbody = document.getElementById("tablaHorarios");
    const btnGuardarModal = document.getElementById("btnGuardarHorarioModal");
    const modalElement = document.getElementById('modalHorario');

    if (!idEmpleado) {
        Swal.fire("Error", "No se seleccionó un empleado", "error").then(() => {
            window.location.href = "tabla-empleados.php";
        });
        return;
    }

    document.getElementById("id_empleado").value = idEmpleado;
    document.getElementById("tituloEmpleado").innerText = `Horarios del Empleado #${idEmpleado}`;

    
    async function cargarHorarios() {
        try {
            const datos = new FormData();
            datos.append("opcion", "consultar");
            datos.append("id_empleado", idEmpleado);

            const respuesta = await fetch("../DAO/DAOHorario.php", { method: "POST", body: datos });
            const horariosGuardados = await respuesta.json();

            tbody.innerHTML = "";
            if (horariosGuardados.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">No hay horarios asignados</td></tr>`;
                return;
            }

            horariosGuardados.forEach(horario => {
                let entradaCorta = horario.hora_entrada.substring(0, 5);
                let salidaCorta = horario.hora_salida.substring(0, 5);

                tbody.innerHTML += `
                    <tr>
                        <td class="fw-bold">${horario.dia_semana}</td>
                        <td>${entradaCorta}</td>
                        <td>${salidaCorta}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editarHorario('${horario.dia_semana}', '${entradaCorta}', '${salidaCorta}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarHorario('${horario.dia_semana}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
        } catch (error) {
            console.error("Error al cargar horarios:", error);
        }
    }

    cargarHorarios();

  
    btnGuardarModal.addEventListener("click", async () => {
        const dia = document.getElementById("comboDia").value;
        const entrada = document.getElementById("comboEntrada").value;
        const salida = document.getElementById("comboSalida").value;
        const accion = btnGuardarModal.dataset.accion || "guardar_uno";

        btnGuardarModal.disabled = true;
        btnGuardarModal.textContent = "Procesando...";

        const datosGuardar = new FormData();
        datosGuardar.append("opcion", accion);
        datosGuardar.append("id_empleado", String(idEmpleado)); 
        datosGuardar.append("dia_semana", dia); 
        datosGuardar.append("hora_entrada", entrada);
        datosGuardar.append("hora_salida", salida);

        try {
            const respuesta = await fetch("../DAO/DAOHorario.php", { method: "POST", body: datosGuardar });
            
            const texto = await respuesta.text();
            console.log("Respuesta del PHP:", texto);

            let resultado;
            try {
                resultado = JSON.parse(texto);
            } catch (e) {
                console.error("El servidor no devolvió un JSON válido:", texto);
                Swal.fire("Error del Servidor", "Revisa la consola (F12) para ver qué falló en el PHP.", "error");
                return; 
            }

            if (resultado.estado) {
                Swal.fire({
                    position: "top-end",
                    icon: "success",
                    title: "Éxito",
                    text: resultado.mensaje,
                    showConfirmButton: false,
                    timer: 1500
                });
                
                const modal = bootstrap.Modal.getInstance(modalElement);
                modal.hide();
                cargarHorarios(); 
            } else {
                Swal.fire("Error", resultado.mensaje, "error");
            }
        } catch (error) {
            console.error("Error crítico:", error);
            Swal.fire("Error", "No se pudo conectar con el servidor.", "error");
        } finally {
            btnGuardarModal.disabled = false;
            btnGuardarModal.textContent = "Guardar Horario";
            btnGuardarModal.dataset.accion = "guardar_uno";
        }
    });

    
    window.editarHorario = function(dia, entrada, salida) {
        document.getElementById("comboDia").value = dia;
        document.getElementById("comboDia").disabled = true; 
        document.getElementById("comboEntrada").value = entrada;
        document.getElementById("comboSalida").value = salida;

        btnGuardarModal.textContent = "Modificar Horario";
        btnGuardarModal.dataset.accion = "modificar";

        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    };

    modalElement.addEventListener('hidden.bs.modal', function () {
        btnGuardarModal.textContent = "Guardar Horario";
        btnGuardarModal.dataset.accion = "guardar_uno";
        document.getElementById("comboDia").disabled = false;
    });
});

 
window.eliminarHorario = async function(dia) {
    Swal.fire({
        title: '¿Eliminar horario?',
        text: `Se eliminará el horario del ${dia}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const idEmpleado = document.getElementById("id_empleado").value;
            const datos = new FormData();
            datos.append("opcion", "eliminar");
            datos.append("id_empleado", idEmpleado);
            datos.append("dia_semana", dia);

            try {
                const respuesta = await fetch("../DAO/DAOHorario.php", { method: "POST", body: datos });
                const res = await respuesta.json();
                
                if (res.estado) {
                    Swal.fire("Eliminado", "", "success");
                    location.reload(); 
                } else {
                    Swal.fire("Error", "No se pudo eliminar", "error");
                }
            } catch (error) {
                console.error(error);
                Swal.fire("Error", "Error de conexión", "error");
            }
        }
    });
};