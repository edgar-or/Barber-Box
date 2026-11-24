document.addEventListener("DOMContentLoaded", () => {
    const tipoSelect = document.getElementById("tipoReporteSelect");
    const divFechas = document.querySelectorAll(".filtros-fechas");
    const inputFechaInicio = document.getElementById("fechaInicio");
    const inputFechaFin = document.getElementById("fechaFin");
    const btnGenerar = document.getElementById("btnGenerarReporte");
    const contenedor = document.getElementById("resultadoReporteContainer");
    const tituloReporte = document.getElementById("tituloReporteActivo");

    // Bloquear fechas futuras de forma nativa en el calendario
    const hoyStr = new Date().toISOString().split('T')[0];
    if (inputFechaInicio && inputFechaFin) {
        inputFechaInicio.max = hoyStr;
        inputFechaFin.max = hoyStr;
    }

    // Ocultar fechas solo en ventas mensuales
    if (tipoSelect) {
        tipoSelect.addEventListener("change", (e) => {
            if (e.target.value === "reporteMensual") {
                divFechas.forEach(el => el.style.display = "none");
            } else {
                divFechas.forEach(el => el.style.display = "block");
            }
        });
    }

    let chartInstancia = null; // Variable global para gestionar gráficos

    if (btnGenerar) {
        btnGenerar.addEventListener("click", async (e) => {
            e.preventDefault(); 

            const tipoRep = tipoSelect.value;
            const formData = new FormData();
            formData.append("opcion", tipoRep);

            if (tipoRep !== "reporteMensual") {
                const fechaInicio = inputFechaInicio.value;
                const fechaFin = inputFechaFin.value;

                if (!fechaInicio || !fechaFin) {
                    Swal.fire("Atención", "Por favor seleccione la fecha de inicio y la fecha fin.", "warning");
                    return;
                }

                if (fechaInicio > fechaFin) {
                    Swal.fire("Atención", "La fecha de inicio no puede ser mayor a la fecha fin.", "warning");
                    return;
                }

                if (fechaInicio > hoyStr || fechaFin > hoyStr) {
                    Swal.fire("Atención", "No se pueden consultar reportes de fechas futuras.", "warning");
                    return;
                }

                formData.append("fechaInicio", fechaInicio);
                formData.append("fechaFin", fechaFin);

                if (tipoRep === "reporteCortes") {
                    tituloReporte.textContent = `Reporte de Cortes por Barbero (${fechaInicio} al ${fechaFin})`;
                } else {
                    tituloReporte.textContent = `Servicios Más Rentables (${fechaInicio} al ${fechaFin})`;
                }
            } else {
                tituloReporte.textContent = "Resumen Histórico de Ventas Mensuales";
            }

            try {
                btnGenerar.disabled = true;
                btnGenerar.textContent = "Generando...";

                const respuesta = await fetch("../DAO/DAOReportes.php", {
                    method: "POST",
                    body: formData
                });

                if (!respuesta.ok) throw new Error("Error en el servidor");

                const resultado = await respuesta.json();

                if (resultado.estado) {
                    renderizarReporte(resultado.tipo, resultado.datos, contenedor, chartInstancia, (nuevaInstancia) => {
                        chartInstancia = nuevaInstancia;
                    });
                } else {
                    contenedor.innerHTML = `<div class="alert alert-warning text-center">${resultado.mensaje || 'No se encontraron registros en el rango seleccionado.'}</div>`;
                }
            } catch (error) {
                console.error("Error al generar reporte:", error);
                Swal.fire("Error", "Ocurrió un error al conectar con el servidor.", "error");
            } finally {
                btnGenerar.disabled = false;
                btnGenerar.innerHTML = `<i class="bi bi-file-earmark-bar-graph me-1"></i> Generar`;
            }
        });
    }
});

function renderizarReporte(tipo, datos, contenedor, chartInstanciaActual, actualizarInstancia) {
    if (chartInstanciaActual) {
        chartInstanciaActual.destroy(); // Limpiar gráfico previo si existe
        actualizarInstancia(null);
    }

    let htmlCompletoprincipal = "";

    // 1. Reporte de Cortes por Barbero con cálculo de comisiones
    if (tipo === "cortes") {
        let htmlTotalGeneralVentas = 0;
        let htmlTotalGeneralComisiones = 0;

        datos.forEach(barbero => {
            let subtotalBarberoVentas = 0;
            let subtotalBarberoComision = 0;
            let filasCortes = "";

            barbero.cortes.forEach(corte => {
                subtotalBarberoVentas += parseFloat(corte.precio);
                subtotalBarberoComision += parseFloat(corte.comision);

                filasCortes += `
                    <tr>
                        <td>${corte.fecha_cita || 'N/A'}</td>
                        <td>${corte.nombre_cliente || 'Cliente General'}</td>
                        <td>${corte.nombre_servicio}</td>
                        <td class="text-end">$${parseFloat(corte.precio).toFixed(2)}</td>
                        <td class="text-end text-success fw-semibold">$${parseFloat(corte.comision).toFixed(2)}</td>
                    </tr>
                `;
            });

            htmlTotalGeneralVentas += subtotalBarberoVentas;
            htmlTotalGeneralComisiones += subtotalBarberoComision;

            htmlCompletoprincipal += `
                <div class="card mb-4 border border-secondary shadow-none">
                    <div class="card-header bg-black text-white d-flex justify-content-between align-items-center py-2 px-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-person-badge text-danger me-2"></i>Barbero: ${barbero.nombre_barbero} <span class="badge bg-secondary ms-2">Comisión: ${barbero.porcentaje_comision}%</span></h6>
                        <div>
                            <span class="badge bg-light text-dark fs-6 me-2">Venta: $${subtotalBarberoVentas.toFixed(2)}</span>
                            <span class="badge bg-success fs-6">Comisión a Pagar: $${subtotalBarberoComision.toFixed(2)}</span>
                        </div>
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
                                        <th class="text-end">Comisión</th>
                                    </tr>
                                </thead>
                                <tbody>${filasCortes}</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        });

        htmlCompletoprincipal += `
            <div class="alert alert-success d-flex flex-column flex-md-row justify-content-between align-items-center fs-5 fw-bold gap-2">
                <span>TOTAL GENERAL (VENTAS / COMISIONES):</span>
                <div>
                    <span class="me-3">Ventas: $${htmlTotalGeneralVentas.toFixed(2)}</span>
                    <span class="text-dark">Comisiones: $${htmlTotalGeneralComisiones.toFixed(2)}</span>
                </div>
            </div>
        `;
        contenedor.innerHTML = htmlCompletoprincipal;
    } 
    else {
        // 2. Reportes con Gráficos Interactivos (Ventas Mensuales y Servicios Más Rentables)
        let idGrafico = "chartContainer_" + Date.now();
        let htmlGrafico = `<div id="${idGrafico}" class="mb-4 p-3 bg-white border rounded shadow-sm"></div>`;

        if (tipo === "mensual") {
            let filas = "";
            let sumaTotal = 0;
            let meses = [];
            let montosMensuales = [];

            datos.forEach(row => {
                sumaTotal += parseFloat(row.total_recaudado);
                meses.push(row.anio_mes);
                montosMensuales.push(parseFloat(row.total_recaudado));
                filas += `
                    <tr>
                        <td class="fw-bold">${row.anio_mes}</td>
                        <td class="text-center">${row.total_citas} citas</td>
                        <td class="text-end fw-semibold text-success">$${parseFloat(row.total_recaudado).toFixed(2)}</td>
                    </tr>
                `;
            });

            htmlCompletoprincipal = htmlGrafico + `
                <div class="card border border-secondary shadow-none">
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Año - Mes</th>
                                    <th class="text-center">Citas Completadas</th>
                                    <th class="text-end">Total Recaudado</th>
                                </tr>
                            </thead>
                            <tbody>${filas}</tbody>
                        </table>
                    </div>
                </div>
                <div class="alert alert-success d-flex justify-content-between align-items-center fs-5 fw-bold mt-3">
                    <span>ACUMULADO HISTÓRICO TOTAL:</span>
                    <span>$${sumaTotal.toFixed(2)}</span>
                </div>
            `;
            contenedor.innerHTML = htmlCompletoprincipal;

            setTimeout(() => {
                const options = {
                    series: [{ name: 'Ventas ($)', data: montosMensuales.reverse() }],
                    chart: { type: 'area', height: 280, toolbar: { show: false } },
                    colors: ['#198754'],
                    dataLabels: { enabled: true },
                    stroke: { curve: 'smooth' },
                    xaxis: { categories: meses.reverse() },
                    title: { text: 'Tendencia Histórica de Ventas Mensuales', align: 'left', style: { fontWeight: 'bold' } }
                };
                let nuevaInstancia = new ApexCharts(document.querySelector("#" + idGrafico), options);
                nuevaInstancia.render();
                actualizarInstancia(nuevaInstancia);
            }, 100);
        }
        else if (tipo === "servicios") {
            let filas = "";
            let sumaTotal = 0;
            let nombresServicios = [];
            let ingresosServicios = [];

            datos.forEach(row => {
                sumaTotal += parseFloat(row.ingreso_total);
                nombresServicios.push(row.nombre_servicio);
                ingresosServicios.push(parseFloat(row.ingreso_total));
                filas += `
                    <tr>
                        <td class="fw-bold">${row.nombre_servicio}</td>
                        <td class="text-center">${row.cantidad_veces} veces</td>
                        <td class="text-end fw-semibold text-success">$${parseFloat(row.ingreso_total).toFixed(2)}</td>
                    </tr>
                `;
            });

            htmlCompletoprincipal = htmlGrafico + `
                <div class="card border border-secondary shadow-none">
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Nombre del Servicio</th>
                                    <th class="text-center">Veces Realizado</th>
                                    <th class="text-end">Ingresos Totales</th>
                                </tr>
                            </thead>
                            <tbody>${filas}</tbody>
                        </table>
                    </div>
                </div>
                <div class="alert alert-success d-flex justify-content-between align-items-center fs-5 fw-bold mt-3">
                    <span>INGRESO TOTAL POR SERVICIOS:</span>
                    <span>$${sumaTotal.toFixed(2)}</span>
                </div>
            `;
            contenedor.innerHTML = htmlCompletoprincipal;

            setTimeout(() => {
                const options = {
                    series: ingresosServicios,
                    chart: { type: 'donut', height: 300 },
                    labels: nombresServicios,
                    responsive: [{ breakpoint: 480, options: { chart: { width: 200 }, legend: { position: 'bottom' } } }],
                    title: { text: 'Distribución de Ingresos por Servicio', align: 'left', style: { fontWeight: 'bold' } }
                };
                let nuevaInstancia = new ApexCharts(document.querySelector("#" + idGrafico), options);
                nuevaInstancia.render();
                actualizarInstancia(nuevaInstancia);
            }, 100);
        }
    }
}