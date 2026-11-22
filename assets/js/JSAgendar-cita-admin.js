const DAO_URL = "../DAO/DAOCitaAdmin.php";

function validarCampo(campo) {
  let vacio = false;
  if (campo.tagName === "SELECT") {
    vacio = campo.value === "" || campo.value === "0";
  } else {
    vacio = campo.value.trim() === "";
  }

  const valido = !vacio && campo.checkValidity();
  campo.classList.toggle("is-invalid", !valido);
  campo.classList.toggle("is-valid", valido);

  return valido;
}

const listaServicios = document.getElementById("listaServicios");
const selBarbero = document.getElementById("selBarbero");
const fechaCita = document.getElementById("fechaCita");
const listaHoras = document.getElementById("listaHoras");
const notaCita = document.getElementById("notaCita");
const btnAgendar = document.getElementById("btnAgendar");
const tbodyHistorial = document.getElementById("tbodyHistorial");
const bannerEdicion = document.getElementById("bannerEdicion");

const buscarCliente = document.getElementById("buscarCliente");
const resultadosCliente = document.getElementById("resultadosCliente");
const cajaCliente = document.getElementById("clienteElegido");
const btnQuitarCliente = document.getElementById("btnQuitarCliente");
const filtroFecha = document.getElementById("filtroFecha");
const filtroBarbero = document.getElementById("filtroBarbero");

let catalogoServicios = [];
let horaElegida = null;
let citaEditando = null; // cita en modo edición (o null)
let clienteElegido = null; // { id_cliente, nombre }
let timerBusqueda = null;

const badge = {
  Pendiente: "bg-warning text-dark",
  Completada: "bg-success",
  Cancelada: "bg-secondary",
};

if (listaServicios) {
  ////////////////////VALIDACION DE CAMPOS VACIOS
  const campos = [selBarbero, fechaCita];
  campos.forEach(function (campo) {
    campo.addEventListener("blur", function () {
      validarCampo(campo);
    });
  });

  ////////////////////FECHA
  // Obtener la fecha actual en formato YYYY-MM-DD
  const hoy = new Date();
  const anio = hoy.getFullYear();
  const mes = String(hoy.getMonth() + 1).padStart(2, "0");
  const dia = String(hoy.getDate()).padStart(2, "0");

  const fechaActual = `${anio}-${mes}-${dia}`;

  // No permitir fechas pasadas
  fechaCita.min = fechaActual;

  fechaCita.addEventListener("change", function () {
    if (this.value && this.value < fechaActual) {
      Swal.fire({
        icon: "warning",
        title: "Fecha inválida",
        text: "No puedes seleccionar una fecha anterior a hoy.",
      });

      this.value = "";
    }
    cargarHoras();
  });

  listaServicios.addEventListener("change", cargarHoras);
  selBarbero.addEventListener("change", cargarHoras);

  ////////////////////BUSQUEDA DE CLIENTE POR NOMBRE/////////
  buscarCliente.addEventListener("input", function () {
    clearTimeout(timerBusqueda);
    const texto = buscarCliente.value.trim();

    if (texto.length < 2) {
      resultadosCliente.classList.add("d-none");
      return;
    }

    timerBusqueda = setTimeout(async function () {
      try {
        const datos = new FormData();
        datos.append("opcion", "consultarClientes");
        datos.append("q", texto);

        const respuesta = await fetch(DAO_URL, {
          method: "POST",
          body: datos,
        });

        if (!respuesta.ok) {
          throw new Error("Error HTTP: " + respuesta.status);
        }

        const resultado = await respuesta.json();

        if (!Array.isArray(resultado)) {
          throw new Error(resultado.mensaje || "Respuesta no válida");
        }

        resultadosCliente.innerHTML = resultado.length
          ? resultado
              .map(
                (c) =>
                  `<button type="button" class="list-group-item list-group-item-action" data-id="${c.id_cliente}" data-nombre="${esc(c.nombre)}">${esc(c.nombre)}</button>`,
              )
              .join("")
          : '<div class="list-group-item text-muted">Sin resultados</div>';
        resultadosCliente.classList.remove("d-none");
      } catch (error) {
        console.error("Error al buscar clientes:", error);
      }
    }, 300);
  });

  resultadosCliente.addEventListener("click", function (e) {
    const boton = e.target.closest("button[data-id]");
    if (boton) {
      elegirCliente({
        id_cliente: boton.dataset.id,
        nombre: boton.dataset.nombre,
      });
    }
  });

  btnQuitarCliente.addEventListener("click", function () {
    if (!citaEditando) quitarCliente(); // en edición el cliente no se cambia
  });

  // Cierra la lista de resultados al hacer clic fuera
  document.addEventListener("click", function (e) {
    if (
      !e.target.closest("#buscarCliente") &&
      !e.target.closest("#resultadosCliente")
    ) {
      resultadosCliente.classList.add("d-none");
    }
  });

  ////////////////////SELECCION DE HORA/////////
  listaHoras.addEventListener("click", function (e) {
    const boton = e.target.closest(".btn-hora");
    if (!boton) return;

    listaHoras.querySelectorAll(".btn-hora").forEach(function (x) {
      x.classList.remove("activo");
    });
    boton.classList.add("activo");
    horaElegida = boton.dataset.hora;
    actualizarResumen();
  });

  ////////////////////TABLA DE CITAS: MODIFICAR / CANCELAR/////////
  tbodyHistorial.addEventListener("click", function (e) {
    const botonModificar = e.target.closest(".btn-modificar");
    if (botonModificar) {
      iniciarEdicion(botonModificar.dataset.id);
      return;
    }

    const botonCancelar = e.target.closest(".btn-cancelar");
    if (botonCancelar) {
      cancelarCita(botonCancelar.dataset.id, cargarHistorial);
    }
  });

  ////////////////////FILTROS DE LA AGENDA/////////
  filtroFecha.addEventListener("change", cargarHistorial);
  filtroBarbero.addEventListener("change", cargarHistorial);
  document
    .getElementById("btnLimpiarFiltros")
    .addEventListener("click", function () {
      filtroFecha.value = "";
      filtroBarbero.value = "";
      cargarHistorial();
    });

  /////////////////////////BOTON DESCARTAR EDICION//////
  document
    .getElementById("btnCancelarEdicion")
    .addEventListener("click", async function () {
      salirEdicion();
      limpiarCampos();
      await cargarHoras();
    });

  /////////////////////////BOTON GUARDAR//////
  btnAgendar.addEventListener("click", async function () {
    if (!clienteElegido) {
      Swal.fire({
        icon: "warning",
        title: "Atención",
        text: "Seleccione el cliente de la cita.",
      });
      return;
    }

    const barberoValido = validarCampo(selBarbero);
    const fechaValida = validarCampo(fechaCita);
    const serviciosValidos = serviciosElegidos().length > 0;
    const horaValida = horaElegida !== null;

    if (!barberoValido || !fechaValida || !serviciosValidos || !horaValida) {
      Swal.fire({
        icon: "warning",
        title: "Atención",
        text: "Por favor, seleccione los servicios, el barbero, la fecha y la hora de la cita.",
      });
      return;
    }

    try {
      const datos = new FormData();
      datos.append("opcion", citaEditando ? "modificar" : "guardar");
      if (citaEditando) datos.append("id_cita", citaEditando.id_cita);
      datos.append("id_cliente", clienteElegido.id_cliente);
      datos.append("id_empleado", selBarbero.value);
      datos.append("fecha", fechaCita.value);
      datos.append("hora", horaElegida);
      datos.append("nota", notaCita.value);
      serviciosElegidos().forEach(function (id) {
        datos.append("servicios[]", id);
      });

      btnAgendar.disabled = true;
      btnAgendar.textContent = "Procesando...";

      const respuesta = await fetch(DAO_URL, {
        method: "POST",
        body: datos,
      });

      if (!respuesta.ok) {
        throw new Error("Error HTTP: " + respuesta.status);
      }

      const resultado = await respuesta.json();

      if (resultado.estado) {
        Swal.fire({
          position: "top-end",
          icon: "success",
          title: "Barber-Box",
          text: resultado.mensaje,
          showConfirmButton: false,
          timer: 1500,
        });

        salirEdicion();
        limpiarCampos();
        await cargarHoras();
        history.replaceState(null, "", "#historial");
        mostrarTab("historial");
      } else {
        Swal.fire({
          position: "top-end",
          icon: "error",
          title: "Barber-Box",
          text: resultado.mensaje,
          showConfirmButton: false,
          timer: 2500,
        });
        cargarHoras();
      }
    } catch (error) {
      console.error(error);
      Swal.fire({
        icon: "error",
        title: "Barber-Box",
        text: "Error al conectar con el servidor.",
      });
    } finally {
      textoBoton();
      actualizarResumen();
    }
  });

  ////////////////////TABS ENLAZADOS CON EL MENU LATERAL/////////
  document.querySelectorAll("[data-tab]").forEach(function (a) {
    a.addEventListener("click", function (e) {
      e.preventDefault();
      history.replaceState(null, "", "#" + a.dataset.tab);
      mostrarTab(a.dataset.tab);
    });
  });

  document
    .getElementById("tab-historial")
    .addEventListener("shown.bs.tab", cargarHistorial);
}

///////////////////LIMPIAR TODOS LOS CAMPOS///////////
function limpiarCampos() {
  listaServicios.querySelectorAll("input").forEach(function (i) {
    i.checked = false;
  });
  notaCita.value = "";

  [selBarbero, fechaCita].forEach(function (campo) {
    campo.value = "";
    campo.classList.remove("is-invalid");
    campo.classList.remove("is-valid");
  });
}

///////////////////SERVICIOS Y BARBEROS///////////
async function cargarServicios() {
  try {
    const datos = new FormData();
    datos.append("opcion", "consultarServicios");

    const respuesta = await fetch(DAO_URL, {
      method: "POST",
      body: datos,
    });

    if (!respuesta.ok) {
      throw new Error("Error HTTP: " + respuesta.status);
    }

    const resultado = await respuesta.json();

    if (!Array.isArray(resultado)) {
      throw new Error(resultado.mensaje || "Respuesta no válida");
    }

    catalogoServicios = resultado;

    listaServicios.innerHTML = resultado.length
      ? resultado
          .map(
            (s) => `
                <div class="col-12 col-md-6">
                    <label class="servicio-opcion w-100">
                        <input type="checkbox" value="${s.id_servicio}" class="d-none">
                        <div class="servicio-opcion-body">
                            <div>
                                <div class="fw-bold">${esc(s.nombre_servicio)}</div>
                                <div class="small text-muted">${esc(s.descripcion)}</div>
                                <div class="small text-muted"><i class="bi bi-clock me-1"></i>${parseInt(s.duracion_estimada, 10)} min</div>
                            </div>
                            <div class="servicio-precio">${money(s.precio)}</div>
                            <i class="bi bi-check-circle-fill servicio-check"></i>
                        </div>
                    </label>
                </div>`,
          )
          .join("")
      : '<div class="col-12 text-muted">No hay servicios disponibles por ahora.</div>';
  } catch (error) {
    console.error("Error al cargar servicios:", error);
    listaServicios.innerHTML =
      '<div class="col-12 text-danger">No se pudieron cargar los servicios.</div>';
  }
}

async function cargarBarberos() {
  try {
    const datos = new FormData();
    datos.append("opcion", "consultarBarberos");

    const respuesta = await fetch(DAO_URL, {
      method: "POST",
      body: datos,
    });

    if (!respuesta.ok) {
      throw new Error("Error HTTP: " + respuesta.status);
    }

    const resultado = await respuesta.json();

    if (!Array.isArray(resultado)) {
      throw new Error(resultado.mensaje || "Respuesta no válida");
    }

    selBarbero.innerHTML = '<option value="">[SELECCIONE UN BARBERO]</option>';
    filtroBarbero.innerHTML = '<option value="">Todos los barberos</option>';

    resultado.forEach((barbero) => {
      const opcion1 = document.createElement("option");
      opcion1.value = barbero.id_empleado;
      opcion1.textContent = barbero.nombre;
      selBarbero.appendChild(opcion1);

      const opcion2 = document.createElement("option");
      opcion2.value = barbero.id_empleado;
      opcion2.textContent = barbero.nombre;
      filtroBarbero.appendChild(opcion2);
    });
  } catch (error) {
    console.error("Error al cargar barberos:", error);
  }
}

///////////////////CLIENTE ELEGIDO///////////
function elegirCliente(cliente) {
  clienteElegido = cliente;
  document.getElementById("clienteNombre").textContent = cliente.nombre;
  cajaCliente.classList.remove("d-none");
  cajaCliente.classList.add("d-flex");
  buscarCliente.classList.add("d-none");
  resultadosCliente.classList.add("d-none");
  buscarCliente.value = "";
  actualizarResumen();
}

function quitarCliente() {
  clienteElegido = null;
  cajaCliente.classList.add("d-none");
  cajaCliente.classList.remove("d-flex");
  buscarCliente.classList.remove("d-none");
  actualizarResumen();
}

///////////////////RESUMEN DE LA CITA///////////
function serviciosElegidos() {
  return [...listaServicios.querySelectorAll("input:checked")].map(function (i) {
    return parseInt(i.value, 10);
  });
}

function actualizarResumen() {
  const ids = serviciosElegidos();
  const elegidos = catalogoServicios.filter(function (s) {
    return ids.includes(parseInt(s.id_servicio, 10));
  });
  const duracion = elegidos.reduce(function (a, s) {
    return a + parseInt(s.duracion_estimada, 10);
  }, 0);
  const total = elegidos.reduce(function (a, s) {
    return a + parseFloat(s.precio);
  }, 0);

  document.getElementById("resumenServicios").innerHTML = elegidos.length
    ? elegidos
        .map(
          (s) =>
            `<li class="d-flex justify-content-between"><span>${esc(s.nombre_servicio)}</span><span>${money(s.precio)}</span></li>`,
        )
        .join("")
    : '<li class="text-muted">Sin servicios seleccionados</li>';
  document.getElementById("resumenCliente").textContent = clienteElegido
    ? clienteElegido.nombre
    : "—";
  document.getElementById("resumenDuracion").textContent = duracion + " min";
  document.getElementById("resumenTotal").textContent = money(total);
  document.getElementById("resumenFecha").textContent = fechaCita.value
    ? fechaCita.value.split("-").reverse().join("/")
    : "—";
  document.getElementById("resumenHora").textContent = horaElegida || "—";

  // El botón se habilita al elegir el cliente; el resto se valida al guardar
  btnAgendar.disabled = !clienteElegido;
}

///////////////////HORARIOS DISPONIBLES///////////
async function cargarHoras() {
  horaElegida = null;
  const ids = serviciosElegidos();

  if (!ids.length || !selBarbero.value || !fechaCita.value) {
    listaHoras.innerHTML =
      '<span class="text-muted">Selecciona servicios, barbero y fecha para ver horarios.</span>';
    actualizarResumen();
    return;
  }

  listaHoras.innerHTML = '<span class="text-muted">Buscando horarios...</span>';

  try {
    const datos = new FormData();
    datos.append("opcion", "consultarDisponibilidad");
    datos.append("id_empleado", selBarbero.value);
    datos.append("fecha", fechaCita.value);
    ids.forEach(function (id) {
      datos.append("servicios[]", id);
    });
    if (citaEditando) datos.append("id_cita", citaEditando.id_cita);

    const respuesta = await fetch(DAO_URL, {
      method: "POST",
      body: datos,
    });

    if (!respuesta.ok) {
      throw new Error("Error HTTP: " + respuesta.status);
    }

    const horas = await respuesta.json();

    if (!Array.isArray(horas)) {
      throw new Error(horas.mensaje || "Respuesta no válida");
    }

    listaHoras.innerHTML = horas.length
      ? horas
          .map(
            (h) =>
              `<button type="button" class="btn-hora" data-hora="${h}">${h}</button>`,
          )
          .join("")
      : '<span class="text-muted">No hay horarios disponibles para ese día. Prueba con otra fecha o barbero.</span>';
  } catch (error) {
    console.error("Error al cargar horarios:", error);
    listaHoras.innerHTML =
      '<span class="text-danger">No se pudieron cargar los horarios.</span>';
  }
  actualizarResumen();
}

///////////////////AGENDA DE CITAS///////////
async function cargarHistorial() {
  try {
    Swal.fire({
      title: "Cargando...",
      text: "Espere",
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => Swal.showLoading(),
    });

    const datos = new FormData();
    datos.append("opcion", "consultar");
    datos.append("filtro_fecha", filtroFecha.value);
    datos.append("filtro_empleado", filtroBarbero.value);

    const respuesta = await fetch(DAO_URL, {
      method: "POST",
      body: datos,
    });

    if (!respuesta.ok) {
      throw new Error("Error HTTP: " + respuesta.status);
    }

    const registros = await respuesta.json();
    Swal.close();

    if (!Array.isArray(registros)) {
      throw new Error(registros.mensaje || "Respuesta no válida");
    }

    if (!registros.length) {
      tbodyHistorial.innerHTML =
        '<tr><td colspan="7" class="text-center text-muted py-4">No hay citas para mostrar.</td></tr>';
      return;
    }

    tbodyHistorial.innerHTML = registros
      .map((c) => {
        const [f, h] = c.fecha_hora_inicio.split(" ");
        const futura =
          new Date(c.fecha_hora_inicio.replace(" ", "T")) > new Date();
        const puedeEditar = c.estado_cita === "Pendiente" && futura;
        return `
            <tr>
              <td><div class="fw-semibold">${f.split("-").reverse().join("/")}</div><div class="small text-muted">${h.slice(0, 5)}</div></td>
              <td>${esc(c.cliente)}</td>
              <td>${esc(c.servicios || "—")}</td>
              <td>${esc(c.barbero)}</td>
              <td class="text-end fw-semibold">$${esc(c.total_cita)}</td>
              <td class="text-center"><span class="badge ${badge[c.estado_cita] || "bg-secondary"}">${esc(c.estado_cita)}</span></td>
              <td class="text-end">${
                puedeEditar
                  ? `<button class="btn btn-sm btn-outline-primary btn-modificar me-1" data-id="${c.id_cita}">Modificar</button>
                     <button class="btn btn-sm btn-outline-danger btn-cancelar" data-id="${c.id_cita}">Cancelar</button>`
                  : ""
              }</td>
            </tr>`;
      })
      .join("");
  } catch (error) {
    Swal.close();
    console.error("Error al cargar registros:", error);
    tbodyHistorial.innerHTML =
      '<tr><td colspan="7" class="text-center text-danger py-4">No se pudieron cargar los registros.</td></tr>';
  }
}

///////////////////MODO EDICION///////////
function textoBoton() {
  btnAgendar.innerHTML = citaEditando
    ? '<i class="bi bi-save me-2"></i>Guardar cambios'
    : '<i class="bi bi-calendar-check me-2"></i>Confirmar cita';
}

async function iniciarEdicion(idcita) {
  try {
    const datos = new FormData();
    datos.append("opcion", "consultarIndividual");
    datos.append("id_cita", idcita);

    Swal.fire({
      title: "Consultando...",
      text: "Espere",
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => Swal.showLoading(),
    });

    const respuesta = await fetch(DAO_URL, {
      method: "POST",
      body: datos,
    });

    if (!respuesta.ok) {
      throw new Error("Error HTTP: " + respuesta.status);
    }

    const resultado = await respuesta.json();
    Swal.close();

    if (!resultado.estado) {
      Swal.fire({
        position: "top-end",
        icon: "warning",
        title: "Barber-Box",
        text: "No se encontró la cita",
        showConfirmButton: false,
        timer: 1500,
      });
      return;
    }

    const cita = resultado.datos;
    citaEditando = cita;

    const [f, h] = cita.fecha_hora_inicio.split(" ");
    const ids = String(cita.servicios_ids || "")
      .split(",")
      .map((x) => parseInt(x, 10));

    elegirCliente({ id_cliente: cita.id_cliente, nombre: cita.cliente });
    btnQuitarCliente.classList.add("d-none");

    listaServicios.querySelectorAll("input").forEach(function (i) {
      i.checked = ids.includes(parseInt(i.value, 10));
    });
    selBarbero.value = cita.id_empleado;
    fechaCita.value = f;
    notaCita.value = cita.nota || "";

    bannerEdicion.classList.remove("d-none");
    bannerEdicion.classList.add("d-flex");
    textoBoton();
    mostrarTab("agendar");

    await cargarHoras();

    // Preselecciona la hora actual de la cita si sigue en la lista
    const hora = h.slice(0, 5);
    const botonHora = listaHoras.querySelector(`.btn-hora[data-hora="${hora}"]`);
    if (botonHora) {
      botonHora.classList.add("activo");
      horaElegida = hora;
    }
    actualizarResumen();
  } catch (error) {
    Swal.close();
    console.error(error);
    Swal.fire({
      icon: "error",
      title: "Barber-Box",
      text: "No se pudo consultar la cita.",
    });
  }
}

function salirEdicion() {
  citaEditando = null;
  bannerEdicion.classList.add("d-none");
  bannerEdicion.classList.remove("d-flex");
  btnQuitarCliente.classList.remove("d-none");
  quitarCliente();
  textoBoton();
}

function mostrarTab(nombre) {
  const boton = document.getElementById(
    nombre === "historial" ? "tab-historial" : "tab-agendar",
  );
  // Al abrir la pestaña de agenda, el evento shown.bs.tab carga los datos
  if (boton) bootstrap.Tab.getOrCreateInstance(boton).show();
}

///////////////////CANCELAR CITA///////////
function cancelarCita(id, recargar) {
  Swal.fire({
    title: "Barber-Box",
    text: "Confirmar cancelación de la cita?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Si, Confirmo",
    cancelButtonText: "Volver",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        const datos = new FormData();
        datos.append("id_cita", id);
        datos.append("opcion", "cancelar");

        const respuesta = await fetch(DAO_URL, {
          method: "POST",
          body: datos,
        });

        if (!respuesta.ok) {
          throw new Error("Error HTTP: " + respuesta.status);
        }

        const resultado = await respuesta.json();

        Swal.fire({
          position: "top-end",
          icon: resultado.estado ? "success" : "error",
          title: "Barber-Box",
          text: resultado.mensaje,
          showConfirmButton: false,
          timer: 1500,
        });

        if (recargar) recargar();
      } catch (error) {
        console.error(error);
        Swal.fire({
          icon: "error",
          title: "Barber-Box",
          text: "Error al conectar con el servidor.",
        });
      }
    }
  });
}

//////////PROCESA AL TERMINAR DE CARGAR LA PAGINA
document.addEventListener("DOMContentLoaded", async () => {
  if (!listaServicios) return;

  await cargarServicios();
  await cargarBarberos();
  actualizarResumen();

  if (location.hash === "#historial") mostrarTab("historial");
});

//////////FUNCIONES DE APOYO
function esc(texto) {
  return String(texto ?? "").replace(
    /[&<>"']/g,
    (c) =>
      ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[
        c
      ],
  );
}

function money(n) {
  return "$" + (parseFloat(n) || 0).toFixed(2);
}