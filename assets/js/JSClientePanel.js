document.addEventListener("DOMContentLoaded", async () => {
  const listaServicios = document.getElementById("listaServicios");
  const selBarbero = document.getElementById("selBarbero");
  const fechaCita = document.getElementById("fechaCita");
  const listaHoras = document.getElementById("listaHoras");
  const notaCita = document.getElementById("notaCita");
  const btnAgendar = document.getElementById("btnAgendar");
  const tbodyHistorial = document.getElementById("tbodyHistorial");

  if (!listaServicios) return;

  let catalogo = { servicios: [], barberos: [] };
  let horaElegida = null;
  let citaEditando = null;
  let citasHistorial = [];
  const galeriasPanel = new Map();
  const bannerEdicion = document.getElementById("bannerEdicion");

  const badge = {
    Pendiente: "bg-warning text-dark",
    Completada: "bg-success",
    Cancelada: "bg-secondary",
  };

  const hoy = new Date();
  fechaCita.min = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, "0")}-${String(hoy.getDate()).padStart(2, "0")}`;

  function resolverRutaPanel(ruta) {
    if (!ruta) return "";
    if (ruta.startsWith("/") || ruta.startsWith("http")) return ruta;
    const limpia = ruta.replace(/^(\.\.\/)+/, "");
    return `/barberBox/${limpia}`;
  }

  function obtenerRutasServicioPanel(s) {
    const lista = Array.isArray(s.imagenes) && s.imagenes.length
      ? s.imagenes
      : (s.imagen ? [s.imagen] : []);

    return lista
      .map((item) => (typeof item === "string" ? item : item?.ruta_imagen || item?.ruta))
      .map(resolverRutaPanel)
      .filter(Boolean);
  }

  // Si catalogo no trajo imágenes, intenta obtenerlas desde DAOHome.php (público)
  async function completarImagenesCatalogo(servicios) {
    const necesitaFotos = servicios.some((s) => !Array.isArray(s.imagenes) || s.imagenes.length === 0);
    if (!necesitaFotos) return servicios;

    try {
      const fd = new FormData();
      fd.append("opcion", "consultarServicios");
      const resp = await fetch("/barberBox/DAO/DAOHome.php", {
        method: "POST",
        body: fd,
      });
      if (!resp.ok) return servicios;
      const homeServicios = await resp.json();
      if (!Array.isArray(homeServicios)) return servicios;

      const mapaFotos = new Map();
      homeServicios.forEach((hs) => {
        const imgs = Array.isArray(hs.imagenes) && hs.imagenes.length
          ? hs.imagenes
          : (hs.imagen ? [hs.imagen] : []);
        mapaFotos.set(parseInt(hs.id_servicio, 10), imgs);
      });

      return servicios.map((s) => {
        const id = parseInt(s.id_servicio, 10);
        const actuales = Array.isArray(s.imagenes) ? s.imagenes : [];
        return {
          ...s,
          imagenes: actuales.length ? actuales : (mapaFotos.get(id) || []),
        };
      });
    } catch (_) {
      return servicios;
    }
  }

  function asegurarModalPanel() {
    if (document.getElementById("modalImagenPanel")) return;

    const html = `
      <div class="modal fade" id="modalImagenPanel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content bg-black text-white border border-danger rounded-4 overflow-hidden">
            <div class="modal-header border-secondary px-4 py-3">
              <div>
                <h5 class="modal-title fw-bold mb-0" id="panelLightboxTitulo"></h5>
                <small class="text-white-50" id="panelLightboxContador"></small>
              </div>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0 position-relative d-flex align-items-center justify-content-center bg-black" style="min-height: 50vh; max-height: 70vh;">
              <img id="panelLightboxImg" src="" alt="" class="img-fluid w-100" style="max-height: 70vh; object-fit: contain;">
              <button type="button" id="panelLightboxPrev" class="btn btn-dark border border-secondary position-absolute start-0 ms-3 rounded-circle" style="width: 42px; height: 42px;" aria-label="Anterior">
                <i class="bi bi-chevron-left"></i>
              </button>
              <button type="button" id="panelLightboxNext" class="btn btn-dark border border-secondary position-absolute end-0 me-3 rounded-circle" style="width: 42px; height: 42px;" aria-label="Siguiente">
                <i class="bi bi-chevron-right"></i>
              </button>
            </div>
            <div class="modal-footer border-secondary justify-content-between px-4 py-3">
              <span class="text-danger fw-bold fs-6" id="panelLightboxPrecio"></span>
              <button type="button" class="btn btn-outline-light btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML("beforeend", html);

    let actual = { rutas: [], index: 0, titulo: "", precio: "" };
    const modalEl = document.getElementById("modalImagenPanel");
    const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);

    function pintar() {
      document.getElementById("panelLightboxImg").src = actual.rutas[actual.index] || "";
      document.getElementById("panelLightboxImg").alt = `${actual.titulo} - ${actual.index + 1}`;
      document.getElementById("panelLightboxContador").textContent = `Foto ${actual.index + 1} de ${actual.rutas.length}`;

      const multiple = actual.rutas.length > 1;
      document.getElementById("panelLightboxPrev").style.display = multiple ? "inline-flex" : "none";
      document.getElementById("panelLightboxNext").style.display = multiple ? "inline-flex" : "none";
    }

    document.getElementById("panelLightboxPrev").addEventListener("click", () => {
      if (actual.rutas.length <= 1) return;
      actual.index = (actual.index - 1 + actual.rutas.length) % actual.rutas.length;
      pintar();
    });

    document.getElementById("panelLightboxNext").addEventListener("click", () => {
      if (actual.rutas.length <= 1) return;
      actual.index = (actual.index + 1) % actual.rutas.length;
      pintar();
    });

    window.abrirLightboxPanel = function (idServicio, idxInicial = 0) {
      const data = galeriasPanel.get(Number(idServicio));
      if (!data || !data.rutas.length) return;
      actual = {
        rutas: data.rutas,
        index: Math.max(0, Math.min(idxInicial, data.rutas.length - 1)),
        titulo: data.titulo,
        precio: data.precio,
      };
      document.getElementById("panelLightboxTitulo").textContent = actual.titulo;
      document.getElementById("panelLightboxPrecio").textContent = actual.precio;
      pintar();
      bsModal.show();
    };
  }

  function renderGaleriaPanel(s, rutas) {
    if (!rutas.length) {
      return `
        <div class="d-flex align-items-center justify-content-center bg-dark text-danger rounded-3 border" style="width: 135px; height: 105px; flex-shrink: 0;">
          <i class="bi bi-scissors fs-2"></i>
        </div>`;
    }

    const idServ = parseInt(s.id_servicio, 10);
    const carouselId = `panelCarousel-${idServ}`;

    return `
      <div class="position-relative" style="width: 135px; height: 105px; flex-shrink: 0;" onclick="event.preventDefault(); event.stopPropagation();">
        <div id="${carouselId}" class="carousel slide w-100 h-100 rounded-3 overflow-hidden border bg-dark" data-bs-ride="false">
          <div class="carousel-inner w-100 h-100">
            ${rutas.map((ruta, idx) => `
              <div class="carousel-item w-100 h-100 ${idx === 0 ? "active" : ""}">
                <img src="${esc(ruta)}"
                     class="d-block w-100 h-100"
                     style="object-fit: cover; cursor: zoom-in;"
                     alt="${esc(s.nombre_servicio)} - ${idx + 1}"
                     onclick="abrirLightboxPanel(${idServ}, ${idx})">
              </div>
            `).join("")}
          </div>
          ${rutas.length > 1 ? `
            <button class="carousel-control-prev" type="button" data-bs-target="#${carouselId}" data-bs-slide="prev" style="width: 24px;" aria-label="Anterior">
              <span class="carousel-control-prev-icon" aria-hidden="true" style="width: 14px; height: 14px;"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#${carouselId}" data-bs-slide="next" style="width: 24px;" aria-label="Siguiente">
              <span class="carousel-control-next-icon" aria-hidden="true" style="width: 14px; height: 14px;"></span>
            </button>
          ` : ""}
        </div>
        <button type="button"
                class="btn btn-sm btn-dark border border-secondary position-absolute bottom-0 end-0 m-1 py-0 px-1 rounded-2"
                title="Expandir imagen"
                onclick="abrirLightboxPanel(${idServ}, 0)">
          <i class="bi bi-arrows-fullscreen text-danger" style="font-size: 0.75rem;"></i>
        </button>
      </div>`;
  }

  async function cargarCatalogo() {
    try {
      asegurarModalPanel();
      galeriasPanel.clear();

      const formData = new FormData();
      formData.append("opcion", "catalogo");

      const respuesta = await fetch("../DAO/DAOCita.php", {
        method: "POST",
        body: formData,
      });

      if (!respuesta.ok) throw new Error("Error en la respuesta del servidor");

      const datos = await respuesta.json();
      console.log("Respuesta obtenida del DAO:", datos);

      if (!datos.estado) return;

      const serviciosConFotos = await completarImagenesCatalogo(datos.servicios || []);
      catalogo = { ...datos, servicios: serviciosConFotos };

      listaServicios.innerHTML = catalogo.servicios.length
        ? catalogo.servicios
            .map((s) => {
              const idServ = parseInt(s.id_servicio, 10);
              const rutas = obtenerRutasServicioPanel(s);

              galeriasPanel.set(idServ, {
                idServicio: idServ,
                rutas,
                titulo: s.nombre_servicio,
                precio: money(s.precio),
              });

              return `
                <div class="col-12 col-md-6">
                    <label class="servicio-opcion w-100">
                        <input type="checkbox" value="${s.id_servicio}" class="d-none">
                        <div class="servicio-opcion-body d-flex align-items-start gap-3">
                            ${renderGaleriaPanel(s, rutas)}
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-bold">${esc(s.nombre_servicio)}</div>
                                <div class="small text-muted">${esc(s.descripcion)}</div>
                                <div class="small text-muted mt-1"><i class="bi bi-clock me-1"></i>${parseInt(s.duracion_estimada, 10)} min</div>
                            </div>
                            <div class="servicio-precio">${money(s.precio)}</div>
                            <i class="bi bi-check-circle-fill servicio-check"></i>
                        </div>
                    </label>
                </div>`;
            })
            .join("")
        : '<div class="col-12 text-muted">No hay servicios disponibles por ahora.</div>';

      selBarbero.innerHTML =
        '<option value="">Selecciona un barbero</option>' +
        (datos.barberos || [])
          .map(
            (b) => `<option value="${b.id_empleado}">${esc(b.nombre)}</option>`,
          )
          .join("");
    } catch (error) {
      console.error("Error al cargar el catálogo:", error);
    }
  }

  function serviciosElegidos() {
    return [...listaServicios.querySelectorAll("input:checked")].map((i) =>
      parseInt(i.value, 10),
    );
  }

  function actualizarResumen() {
    const ids = serviciosElegidos();
    const elegidos = catalogo.servicios.filter((s) =>
      ids.includes(parseInt(s.id_servicio, 10)),
    );
    const duracion = elegidos.reduce(
      (a, s) => a + parseInt(s.duracion_estimada, 10),
      0,
    );
    const total = elegidos.reduce((a, s) => a + parseFloat(s.precio), 0);

    document.getElementById("resumenServicios").innerHTML = elegidos.length
      ? elegidos
          .map((s) => {
            const idServ = parseInt(s.id_servicio, 10);
            const rutas = obtenerRutasServicioPanel(s);
            const thumbsHtml = rutas.length
              ? `<div class="d-flex flex-wrap gap-1 mt-1 mb-2">` +
                rutas
                  .map(
                    (ruta, idx) =>
                      `<img src="${esc(ruta)}"
                            alt="${esc(s.nombre_servicio)} ${idx + 1}"
                            class="rounded border border-secondary"
                            style="width: 36px; height: 36px; object-fit: cover; cursor: zoom-in;"
                            onclick="abrirLightboxPanel(${idServ}, ${idx})">`,
                  )
                  .join("") +
                `</div>`
              : "";

            return `
              <li class="mb-2">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="fw-semibold">${esc(s.nombre_servicio)}</span>
                  <span>${money(s.precio)}</span>
                </div>
                ${thumbsHtml}
              </li>`;
          })
          .join("")
      : '<li class="text-muted">Sin servicios seleccionados</li>';

    document.getElementById("resumenDuracion").textContent = duracion + " min";
    document.getElementById("resumenTotal").textContent = money(total);
    document.getElementById("resumenFecha").textContent = fechaCita.value
      ? fechaCita.value.split("-").reverse().join("/")
      : "—";
    document.getElementById("resumenHora").textContent = horaElegida || "—";
    btnAgendar.disabled = !(
      elegidos.length &&
      selBarbero.value &&
      fechaCita.value &&
      horaElegida
    );
  }

  async function cargarHoras() {
    horaElegida = null;
    const ids = serviciosElegidos();

    if (!ids.length || !selBarbero.value || !fechaCita.value) {
      listaHoras.innerHTML =
        '<span class="text-muted">Selecciona servicios, barbero y fecha para ver horarios.</span>';
      actualizarResumen();
      return;
    }

    listaHoras.innerHTML =
      '<span class="text-muted">Buscando horarios...</span>';

    try {
      const formData = new FormData();
      formData.append("opcion", "disponibilidad");
      formData.append("id_empleado", selBarbero.value);
      formData.append("fecha", fechaCita.value);
      ids.forEach((id) => formData.append("servicios[]", id));
      if (citaEditando) formData.append("id_cita", citaEditando.id_cita);

      const respuesta = await fetch("../DAO/DAOCita.php", {
        method: "POST",
        body: formData,
      });

      if (!respuesta.ok) throw new Error("Error en la respuesta del servidor");

      const datos = await respuesta.json();

      listaHoras.innerHTML =
        datos.horas && datos.horas.length
          ? datos.horas
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

  listaServicios.addEventListener("change", cargarHoras);
  selBarbero.addEventListener("change", cargarHoras);
  fechaCita.addEventListener("change", cargarHoras);

  listaHoras.addEventListener("click", (e) => {
    const b = e.target.closest(".btn-hora");
    if (!b) return;
    listaHoras
      .querySelectorAll(".btn-hora")
      .forEach((x) => x.classList.remove("activo"));
    b.classList.add("activo");
    horaElegida = b.dataset.hora;
    actualizarResumen();
  });

  async function cargarHistorial() {
    try {
      const formData = new FormData();
      formData.append("opcion", "historial");

      const respuesta = await fetch("../DAO/DAOCita.php", {
        method: "POST",
        body: formData,
      });

      if (!respuesta.ok) throw new Error("Error en la respuesta del servidor");

      const datos = await respuesta.json();
      citasHistorial = datos.citas || [];
      console.log("Respuesta obtenida del DAO:", datos);

      if (!datos.estado || !datos.citas.length) {
        tbodyHistorial.innerHTML =
          '<tr><td colspan="6" class="text-center text-muted py-4">Aún no tienes citas registradas.</td></tr>';
        return;
      }

      tbodyHistorial.innerHTML = datos.citas
        .map((c) => {
          const [f, h] = c.fecha_hora_inicio.split(" ");
          const futura =
            new Date(c.fecha_hora_inicio.replace(" ", "T")) > new Date();
          const puedeCancelar = c.estado_cita === "Pendiente" && futura;
          return `
                    <tr>
                        <td><div class="fw-semibold">${f.split("-").reverse().join("/")}</div><div class="small text-muted">${h.slice(0, 5)}</div></td>
                        <td>${esc(c.servicios || "—")}</td>
                        <td>${esc(c.barbero)}</td>
                        <td class="text-end fw-semibold">${money(c.total_cita)}</td>
                        <td class="text-center"><span class="badge ${badge[c.estado_cita] || "bg-secondary"}">${esc(c.estado_cita)}</span></td>
                        <td class="text-end">${
                          puedeCancelar
                            ? `
    <button class="btn btn-sm btn-outline-primary btn-modificar me-1" data-id="${c.id_cita}">Modificar</button>
    <button class="btn btn-sm btn-outline-danger btn-cancelar" data-id="${c.id_cita}">Cancelar</button>`
                            : ""
                        }</td>
                    </tr>`;
        })
        .join("");
    } catch (error) {
      console.error("Error al cargar el historial:", error);
      tbodyHistorial.innerHTML =
        '<tr><td colspan="6" class="text-center text-danger py-4">No se pudo cargar el historial.</td></tr>';
    }
  }

  tbodyHistorial.addEventListener("click", (e) => {
    const bMod = e.target.closest(".btn-modificar");
    if (bMod) {
      const cita = citasHistorial.find(
        (c) => String(c.id_cita) === bMod.dataset.id,
      );
      if (cita) iniciarEdicion(cita);
      return;
    }
    const b = e.target.closest(".btn-cancelar");
    if (!b) return;
    cancelarCita(b.getAttribute("data-id"), cargarHistorial);
  });

  function textoBoton() {
    btnAgendar.innerHTML = citaEditando
      ? '<i class="bi bi-save me-2"></i>Guardar cambios'
      : '<i class="bi bi-calendar-check me-2"></i>Confirmar cita';
  }

  async function iniciarEdicion(cita) {
    citaEditando = cita;
    const [f, h] = cita.fecha_hora_inicio.split(" ");
    const ids = String(cita.servicios_ids || "")
      .split(",")
      .map((x) => parseInt(x, 10));

    listaServicios.querySelectorAll("input").forEach((i) => {
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

    const hora = h.slice(0, 5);
    const btnHora = listaHoras.querySelector(`.btn-hora[data-hora="${hora}"]`);
    if (btnHora) {
      btnHora.classList.add("activo");
      horaElegida = hora;
    }
    actualizarResumen();
  }

  function salirEdicion() {
    citaEditando = null;
    bannerEdicion.classList.add("d-none");
    bannerEdicion.classList.remove("d-flex");
    textoBoton();
  }

  document
    .getElementById("btnCancelarEdicion")
    .addEventListener("click", async () => {
      salirEdicion();
      listaServicios
        .querySelectorAll("input")
        .forEach((i) => (i.checked = false));
      notaCita.value = "";
      fechaCita.value = "";
      selBarbero.value = "";
      await cargarHoras();
    });

  btnAgendar.addEventListener("click", async () => {
    btnAgendar.disabled = true;

    try {
      const formData = new FormData();
      formData.append("opcion", citaEditando ? "modificar" : "agendar");
      if (citaEditando) formData.append("id_cita", citaEditando.id_cita);
      formData.append("id_empleado", selBarbero.value);
      formData.append("fecha", fechaCita.value);
      formData.append("hora", horaElegida);
      formData.append("nota", notaCita.value);
      serviciosElegidos().forEach((id) => formData.append("servicios[]", id));

      const respuesta = await fetch("../DAO/DAOCita.php", {
        method: "POST",
        body: formData,
      });

      const resultado = await respuesta.json();

      await Swal.fire({
        icon: resultado.estado ? "success" : "error",
        title: "Barber-Box",
        text: resultado.mensaje,
      });

      if (resultado.estado) {
        salirEdicion();
        listaServicios
          .querySelectorAll("input")
          .forEach((i) => (i.checked = false));
        notaCita.value = "";
        fechaCita.value = "";
        selBarbero.value = "";
        await cargarHoras();
        history.replaceState(null, "", "#historial");
        mostrarTab("historial");
      } else {
        cargarHoras();
      }
    } catch (error) {
      console.error(error);
      Swal.fire({
        icon: "error",
        title: "Barber-Box",
        text: "Error al conectar con el servidor.",
      });
    }
    actualizarResumen();
  });

  function mostrarTab(nombre) {
    const btn = document.getElementById(
      nombre === "historial" ? "tab-historial" : "tab-agendar",
    );
    if (btn) bootstrap.Tab.getOrCreateInstance(btn).show();
    if (nombre === "historial") cargarHistorial();
  }

  document.querySelectorAll("[data-tab]").forEach((a) => {
    a.addEventListener("click", (e) => {
      e.preventDefault();
      history.replaceState(null, "", "#" + a.dataset.tab);
      mostrarTab(a.dataset.tab);
    });
  });

  document
    .getElementById("tab-historial")
    .addEventListener("shown.bs.tab", cargarHistorial);

  await cargarCatalogo();

  const paramsUrl = new URLSearchParams(window.location.search);
  const idPreseleccionado = paramsUrl.get("servicio") || localStorage.getItem("servicioPreseleccionado");

  if (idPreseleccionado) {
    const inputPre = listaServicios.querySelector(`input[type="checkbox"][value="${idPreseleccionado}"]`);
    if (inputPre && !inputPre.checked) {
      inputPre.checked = true;
      inputPre.closest(".servicio-opcion")?.scrollIntoView({ behavior: "smooth", block: "center" });
    }
    localStorage.removeItem("servicioPreseleccionado");
    await cargarHoras();
  } else {
    actualizarResumen();
  }

  if (location.hash === "#historial") mostrarTab("historial");
});

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

function cancelarCita(id, recargar) {
  Swal.fire({
    title: "¿Cancelar la cita?",
    text: "Esta acción no se puede deshacer.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Sí, cancelar",
    cancelButtonText: "Volver",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        const datos = new FormData();
        datos.append("opcion", "cancelar");
        datos.append("id_cita", id);

        const respuesta = await fetch("../DAO/DAOCita.php", {
          method: "POST",
          body: datos,
        });

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