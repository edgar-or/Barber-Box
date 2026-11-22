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

    // --- CARGAR LA TABLA ---
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
                const entradaCorta = horario.hora_entrada.substring(0, 5);
                const salidaCorta = horario.hora_salida.substring(0, 5);

                tbody.innerHTML += `
                    <tr>
                        <td class="fw-bold text-capitalize">${horario.dia_semana}</td>
                        <td>${entradaCorta}</td>
                        <td>${salidaCorta}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editarHorario('${horario.dia_semana}', '${entradaCorta}', '${salidaCorta}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarHorario('${horario.dia_semana}', '${entradaCorta}', '${salidaCorta}')">
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

    // Deja las dos tarjetas de turno vacías y habilitadas
    function limpiarCampos() {
        ["entrada1", "salida1", "entrada2", "salida2"].forEach(id => {
            const el = document.getElementById(id);
            el.value = "";
            el.disabled = false;
        });
    }

    btnGuardarModal.addEventListener("click", async () => {
        const dia = document.getElementById("comboDia").value;
        const accion = btnGuardarModal.dataset.accion || "guardar_uno";

        const e1 = document.getElementById("entrada1").value;
        const s1 = document.getElementById("salida1").value;
        const e2 = document.getElementById("entrada2").value;
        const s2 = document.getElementById("salida2").value;

        const datosGuardar = new FormData();
        datosGuardar.append("opcion", accion);
        datosGuardar.append("id_empleado", String(idEmpleado));
        datosGuardar.append("dia_semana", dia);

        if (accion === "modificar") {
            const eMod = e1 || e2;
            const sMod = s1 || s2;
            if (!eMod || !sMod) {
                Swal.fire("Atención", "Debes llenar la hora de entrada y salida", "warning");
                return;
            }
            datosGuardar.append("hora_entrada", eMod);
            datosGuardar.append("hora_salida", sMod);
            datosGuardar.append("hora_original_entrada", btnGuardarModal.dataset.horaOriginalEntrada);
            datosGuardar.append("hora_original_salida", btnGuardarModal.dataset.horaOriginalSalida);
        } else {
            if ((e1 && !s1) || (!e1 && s1) || (e2 && !s2) || (!e2 && s2)) {
                Swal.fire("Atención", "Completa la entrada y la salida del turno", "warning");
                return;
            }
            const vacio = (e, s) => (!e && !s) || (e === "00:00" && s === "00:00");
            const turnos = [];
            if (!vacio(e1, s1)) turnos.push({ entrada: e1, salida: s1 });
            if (!vacio(e2, s2)) turnos.push({ entrada: e2, salida: s2 });

            if (turnos.length === 0) {
                Swal.fire("Atención", "Debes llenar al menos un turno", "warning");
                return;
            }
            datosGuardar.append("turnos", JSON.stringify(turnos));
        }

        btnGuardarModal.disabled = true;
        btnGuardarModal.textContent = "Procesando...";

        try {
            const respuesta = await fetch("../DAO/DAOHorario.php", { method: "POST", body: datosGuardar });
            const texto = await respuesta.text();

            let resultado;
            try {
                resultado = JSON.parse(texto);
            } catch (e) {
                console.error("El servidor no devolvió un JSON válido:", texto);

                Swal.fire("Error del Servidor", "Revisa la consola (F12) para ver qué falló en el PHP.", "error");
                return;

                Swal.fire("Error del Servidor", "Ocurrio un error", "error");
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

                // Se limpian los campos para que el turno ya guardado no vuelva a enviarse
                limpiarCampos();
                bootstrap.Modal.getInstance(modalElement).hide();
                cargarHorarios();
            } else {
                Swal.fire("Error", resultado.mensaje, "error");
            }
        } catch (error) {
            console.error("Error crítico:", error);
            Swal.fire("Error", "No se pudo conectar con el servidor.", "error");
        } finally {
            btnGuardarModal.disabled = false;
            btnGuardarModal.textContent = btnGuardarModal.dataset.accion === "modificar" ? "Modificar Horario" : "Guardar Horario";
        }
    });

    // --- EDITAR ---
    window.editarHorario = function (dia, entrada, salida) {
        document.getElementById("comboDia").value = dia;
        document.getElementById("comboDia").disabled = true;

        
        ["entrada1", "salida1", "entrada2", "salida2"].forEach(id => {
            const el = document.getElementById(id);
            el.value = "";
            el.disabled = false;
        });

        const esTarde = entrada >= "12:00";
        const activo = esTarde ? "2" : "1";     
        const inactivo = esTarde ? "1" : "2";  

        document.getElementById("entrada" + activo).value = entrada;
        document.getElementById("salida" + activo).value = salida;
        document.getElementById("entrada" + inactivo).disabled = true;
        document.getElementById("salida" + inactivo).disabled = true;

        btnGuardarModal.textContent = "Modificar Horario";
        btnGuardarModal.dataset.accion = "modificar";
        btnGuardarModal.dataset.horaOriginalEntrada = entrada;
        btnGuardarModal.dataset.horaOriginalSalida = salida;

        new bootstrap.Modal(modalElement).show();
    };

    modalElement.addEventListener('hidden.bs.modal', function () {
        btnGuardarModal.textContent = "Guardar Horario";
        btnGuardarModal.dataset.accion = "guardar_uno";
        document.getElementById("comboDia").disabled = false;
        ["entrada1", "salida1", "entrada2", "salida2"].forEach(id => {
            const el = document.getElementById(id);
            el.value = "";
            el.disabled = false;
        });
    });
});

window.eliminarHorario = async function (dia, entrada, salida) {
    Swal.fire({
        title: '¿Eliminar turno?',
        text: `Se eliminará el turno del ${dia} de ${entrada} a ${salida}`,
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
            datos.append("hora_entrada", entrada);
            datos.append("hora_salida", salida);

            try {
                const respuesta = await fetch("../DAO/DAOHorario.php", { method: "POST", body: datos });
                const res = await respuesta.json();

                if (res.estado) {
                    Swal.fire("Eliminado", "", "success");
                    setTimeout(() => location.reload(), 1500);
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