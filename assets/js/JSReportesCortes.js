document.addEventListener("DOMContentLoaded", () => {
    const btnGenerar = document.getElementById("btnGenerarReporte");
    const contenedor = document.getElementById("resultadoReporteContainer");

    if (btnGenerar) {
        btnGenerar.addEventListener("click", async (e) => {
            e.preventDefault(); // Evita cualquier recarga de formulario

            const fechaInicio = document.getElementById("fechaInicio").value;
            const fechaFin = document.getElementById("fechaFin").value;

            if (!fechaInicio || !fechaFin) {
                Swal.fire("Atención", "Por favor seleccione la fecha de inicio y la fecha fin.", "warning");
                return;
            }

            if (fechaInicio > fechaFin) {
                Swal.fire("Atención", "La fecha de inicio no puede ser mayor a la fecha fin.", "warning");
                return;
            }

            try {
                btnGenerar.disabled = true;
                btnGenerar.textContent = "Generando...";

                const formData = new FormData();
                formData.append("opcion", "reporteCortes");
                formData.append("fechaInicio", fechaInicio);
                formData.append("fechaFin", fechaFin);

                const respuesta = await fetch("../DAO/DAOReportes.php", {
                    method: "POST",
                    body: formData
                });

                if (!respuesta.ok) throw new Error("Error en el servidor");

                const resultado = await respuesta.json();

                if (resultado.estado) {
                    renderizarReporte(resultado.datos, contenedor);
                } else {
                    contenedor.innerHTML = `<div class="alert alert-warning text-center">No se encontraron cortes realizados en el rango de fechas seleccionado.</div>`;
                }
            } catch (error) {
                console.error("Error al generar reporte:", error);
                Swal.fire("Error", "Ocurrió un error al conectar con el servidor.", "error");
            } finally {
                btnGenerar.disabled = false;
                btnGenerar.innerHTML = `<i class="bi bi-file-earmark-bar-graph me-1"></i> Generar Reporte`;
            }
        });
    }
});

function renderizarReporte(barberosData, contenedor) {
    let htmlTotalGeneral = 0;
    let htmlCompleto = "";

    barberosData.forEach(barbero => {
        let subtotalBarbero = 0;
        
        let filasCortes = "";
        barbero.cortes.forEach(corte => {
            subtotalBarbero += parseFloat(corte.precio);
            filasCortes += `
                <tr>
                    <td>${corte.fecha_cita || 'N/A'}</td>
                    <td>${corte.nombre_cliente || 'Cliente General'}</td>
                    <td>${corte.nombre_servicio}</td>
                    <td class="text-end fw-semibold">$${parseFloat(corte.precio).toFixed(2)}</td>
                </tr>
            `;
        });

        htmlTotalGeneral += subtotalBarbero;

        htmlCompleto += `
            <div class="card mb-4 border border-secondary shadow-none">
                <div class="card-header bg-black text-white d-flex justify-content-between align-items-center py-2 px-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-person-badge text-danger me-2"></i>Barbero: ${barbero.nombre_barbero}</h6>
                    <span class="badge bg-danger fs-6">Subtotal: $${subtotalBarbero.toFixed(2)}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Servicio / Corte</th>
                                    <th class="text-end">Precio</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${filasCortes}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    });

    htmlCompleto += `
        <div class="alert alert-success d-flex justify-content-between align-items-center fs-5 fw-bold">
            <span>TOTAL GENERAL RECAUDADO (RANGO SELECCIONADO):</span>
            <span>$${htmlTotalGeneral.toFixed(2)}</span>
        </div>
    `;

    contenedor.innerHTML = htmlCompleto;
}