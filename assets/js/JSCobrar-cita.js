document.addEventListener("DOMContentLoaded", async () => {
    const idCita = document.getElementById("idCita").value;
    const metodoPago = document.getElementById("metodoPago");
    const seccionEfectivo = document.getElementById("seccionEfectivo");
    const montoRecibido = document.getElementById("montoRecibido");
    const inputTotal = document.getElementById("totalCita");
    const labelTotal = document.getElementById("labelTotal");
    const textoVuelto = document.getElementById("textoVuelto");
    const formCobro = document.getElementById("formProcesarCobro");
    const btnConfirmar = document.getElementById("btnConfirmarCobro");

    let total = 0;

    try {
        const datos = new FormData();
        datos.append("opcion", "detalle");
        datos.append("id_cita", idCita);

        const resp = await fetch("../DAO/DAOCita.php", {
            method: "POST",
            body: datos
        });
        const resultado = await resp.json();

        if (!resultado.estado || resultado.cita.estado_cita !== "Pendiente") {
            window.location.href = "tabla-citas.php";
            return;
        }

        const c = resultado.cita;
        total = parseFloat(c.total_cita) || 0;

        document.getElementById("subtituloCliente").textContent = c.cliente;
        document.getElementById("infoCliente").textContent = c.cliente;
        document.getElementById("infoBarbero").textContent = c.barbero;
        inputTotal.value = total.toFixed(2);
        labelTotal.textContent = `$${total.toFixed(2)}`;

        const tbody = document.getElementById("listaServiciosCobro");
        tbody.innerHTML = resultado.detalle.map((d) => `
            <tr>
                <td>${d.nombre_servicio}</td>
                <td class="text-end fw-semibold">$${(parseFloat(d.precio_unitario) || 0).toFixed(2)}</td>
            </tr>
        `).join("");
    } catch (error) {
        console.error(error);
        Swal.fire("Error", "No se pudieron cargar los datos de la cita.", "error");
    }

    metodoPago.addEventListener("change", () => {
        if (metodoPago.value === "Efectivo") {
            seccionEfectivo.style.display = "block";
            montoRecibido.required = true;
            montoRecibido.value = "";
            textoVuelto.textContent = "$0.00";
            textoVuelto.className = "display-6 fw-bold text-secondary";
        } else {
            seccionEfectivo.style.display = "none";
            montoRecibido.required = false;
            montoRecibido.value = total.toFixed(2);
            textoVuelto.textContent = "$0.00";
            textoVuelto.className = "display-6 fw-bold text-secondary";
        }
    });

    montoRecibido.addEventListener("input", () => {
        const recibido = parseFloat(montoRecibido.value) || 0;
        const cambio = recibido - total;

        if (cambio >= 0) {
            textoVuelto.textContent = `$${cambio.toFixed(2)}`;
            textoVuelto.className = "display-6 fw-bold text-success";
        } else {
            textoVuelto.textContent = `Faltan $${Math.abs(cambio).toFixed(2)}`;
            textoVuelto.className = "display-6 fw-bold text-danger";
        }
    });

    formCobro.addEventListener("submit", async (e) => {
        e.preventDefault();

        const recibido = parseFloat(montoRecibido.value) || 0;
        if (metodoPago.value === "Efectivo" && recibido < total) {
            Swal.fire("Monto Insuficiente", "El dinero recibido es menor al total del servicio.", "warning");
            return;
        }

        try {
            btnConfirmar.disabled = true;
            btnConfirmar.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Procesando...`;

            const datos = new FormData();
            datos.append("opcion", "registrarPagoYCompletar");
            datos.append("id_cita", idCita);
            datos.append("metodo_pago", metodoPago.value);
            datos.append("total_cita", total);

            const respuesta = await fetch("../DAO/DAOCita.php", {
                method: "POST",
                body: datos
            });

            const resultado = await respuesta.json();

            if (resultado.estado) {
                Swal.fire({
                    icon: "success",
                    title: "¡Pago Exitoso!",
                    text: resultado.mensaje || "La cita ha sido cobrada correctamente.",
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = "tabla-citas.php";
                });
            } else {
                Swal.fire("Error", resultado.mensaje || "No se pudo procesar el pago.", "error");
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i> Cobrar y Finalizar`;
            }
        } catch (error) {
            console.error(error);
            Swal.fire("Error de Conexión", "No se pudo comunicar con el servidor.", "error");
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i> Cobrar y Finalizar`;
        }
    });
});