<?php
require_once __DIR__ . "/../config/auth.php";
requerirAdminPagina();

$currentPage = 'reportes';

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Módulo de Reportes Gerenciales - Barber-Box</title>
  <link rel="icon" type="image/png" href="../assets/images/favicon.ico">
  <link rel="stylesheet" href="../assets/libs/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/libs/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/libs/flatpickr/flatpickr.min.css">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/reportes.css" media="print">
</head>
<body class="pagina-reportes">
  <?php include './menu/menuLateral.php'; ?>
  <?php include './menu/menu-header.php'; ?>

  <!-- START: Page Header -->
  <div class="page-header">
    <div>
      <h1 class="page-title">Módulo de Reportes y Estadísticas</h1>
      <p class="page-subtitle">Genera reportes detallados, gráficos interactivos y análisis gerenciales de la barbería.</p>
    </div>
  </div>
  <!-- END: Page Header -->

  <!-- START: Panel de Control / Filtros -->
  <div class="card p-4 mb-4 border-light shadow-sm">
    <form id="formFiltroReporte" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label for="tipoReporteSelect" class="form-label-custom fw-bold">Tipo de Reporte</label>
        <select class="form-select" id="tipoReporteSelect">
          <option value="reporteCortes">Reporte de Cortes por Barbero</option>
          <option value="reporteMensual">Resumen de Ventas Mensuales</option>
          <option value="reporteServicios">Servicios Más Rentables</option>
        </select>
      </div>

      <div class="col-md-3 filtros-fechas">
        <label for="fechaInicio" class="form-label-custom">Fecha de Inicio</label>
        <input type="date" class="form-control-custom" id="fechaInicio" name="fechaInicio">
      </div>
      <div class="col-md-3 filtros-fechas">
        <label for="fechaFin" class="form-label-custom">Fecha Fin</label>
        <input type="date" class="form-control-custom" id="fechaFin" name="fechaFin">
      </div>

      <div class="col-md-2">
        <button type="button" id="btnGenerarReporte" class="btn-custom btn-custom-primary w-100">
          <i class="bi bi-file-earmark-bar-graph me-1"></i> Generar
        </button>
      </div>
    </form>
  </div>
  <!-- END: Filtros -->

  <!-- START: Contenedor de Resultados (Gráfico + Tablas) -->
  <div class="card p-4 border-light shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="card-title mb-0" id="tituloReporteActivo">Resultados del Reporte</h5>
      <button type="button" class="btn-custom btn-custom-light btn-sm" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Imprimir / PDF
      </button>
    </div>
    
    <div id="resultadoReporteContainer">
      <div class="text-center text-muted py-5">
        <i class="bi bi-bar-chart-line fs-1 d-block mb-2 opacity-50"></i>
        Selecciona el tipo de reporte deseado y haz clic en "Generar" para visualizar los datos y gráficos estadísticos.
      </div>
    </div>
  </div>
  <!-- END: Resultados -->

  <!-- Scripts base -->
  <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/libs/apexcharts/apexcharts.min.js"></script>
  <script src="../assets/libs/flatpickr/flatpickr.min.js"></script>
  <script src="../assets/js/dashboard.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Lógica JavaScript Unificada y Optimizada -->
  <script>
    const tipoSelect = document.getElementById("tipoReporteSelect");
    const divFechas = document.querySelectorAll(".filtros-fechas");
    const inputFechaInicio = document.getElementById("fechaInicio");
    const inputFechaFin = document.getElementById("fechaFin");

    // Bloquear fechas futuras de forma nativa en el calendario
    const hoyStr = new Date().toISOString().split('T')[0];
    if (inputFechaInicio && inputFechaFin) {
        inputFechaInicio.max = hoyStr;
        inputFechaFin.max = hoyStr;
    }

    // Ocultar fechas solo en ventas mensuales
    tipoSelect.addEventListener("change", (e) => {
        if (e.target.value === "reporteMensual") {
            divFechas.forEach(el => el.style.display = "none");
        } else {
            divFechas.forEach(el => el.style.display = "block");
        }
    });

    let chartInstancia = null; // Variable global para gestionar gráficos

    document.addEventListener("DOMContentLoaded", () => {
        const btnGenerar = document.getElementById("btnGenerarReporte");
        const contenedor = document.getElementById("resultadoReporteContainer");
        const tituloReporte = document.getElementById("tituloReporteActivo");

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
                    console.log("Inicio:", JSON.stringify(fechaInicio), "Fin:", JSON.stringify(fechaFin));


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

                    const respuesta = await fetch("/barberBox/DAO/DAOReportes.php", {
                        method: "POST",
                        body: formData
                    });

                    if (!respuesta.ok) throw new Error("Error en el servidor");

                    const resultado = await respuesta.json();
                    console.log("RESPUESTA COMPLETA:", resultado);

                    if (resultado.estado) {
                        renderizarReporte(resultado.tipo, resultado.datos, contenedor);
                    } else {
                        contenedor.innerHTML = `<div class="alert alert-warning text-center">${resultado.mensaje || 'No se encontraron registros.'}</div>`;
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

    function renderizarReporte(tipo, datos, contenedor) {
        if (chartInstancia) {
            chartInstancia.destroy(); // Limpiar gráfico previo si existe
            chartInstancia = null;
        }

        let htmlCompletoprincipal = "";

        // Reporte de Cortes por Barbero (SIN gráfica, solo estructura limpia de tarjetas y tablas)
        if (tipo === "cortes") {
            let htmlTotalGeneral = 0;
            datos.forEach(barbero => {
                let subtotalBarbero = 0;
                let filasCortes = "";
                barbero.cortes.forEach(corte => {
                    subtotalBarbero += parseFloat(corte.precio);
                    filasCortes += `
                        <tr>
                            <td>${corte.fecha_cita}</td>
                            <td>${corte.nombre_cliente}</td>
                            <td>${corte.nombre_servicio}</td>
                            <td class="text-end fw-semibold">$${parseFloat(corte.precio).toFixed(2)}</td>
                        </tr>
                    `;
                });
                htmlTotalGeneral += subtotalBarbero;

                htmlCompletoprincipal += `
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
                                    <tbody>${filasCortes}</tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            });

            htmlCompletoprincipal += `
                <div class="alert alert-success d-flex justify-content-between align-items-center fs-5 fw-bold">
                    <span>TOTAL GENERAL RECAUDADO:</span>
                    <span>$${htmlTotalGeneral.toFixed(2)}</span>
                </div>
            `;
            contenedor.innerHTML = htmlCompletoprincipal;
        } 
        else {
            // Reportes gerenciales CON gráficos interactivos arriba
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
                    chartInstancia = new ApexCharts(document.querySelector("#" + idGrafico), options);
                    chartInstancia.render();
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
                    chartInstancia = new ApexCharts(document.querySelector("#" + idGrafico), options);
                    chartInstancia.render();
                }, 100);
            }
        }
    }
  </script>
  <link rel="stylesheet" href="../assets/css/tema-barberbox.css?v=5">

</body>
</html>