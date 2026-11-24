const DAO_URL = "/barberBox/DAO/DAOHome.php";

const listaServicios = document.getElementById("listaServicios");
const totalServicios = document.getElementById("totalServicios");

// Mapa para guardar las galerías de cada servicio y abrirlas en grande
const galeriasPorServicio = new Map();

function resolverRuta(ruta) {
  if (!ruta) return "";
  return ruta.startsWith("/") || ruta.startsWith("http")
    ? ruta
    : `/barberBox/${ruta}`;
}

function obtenerImagenesServicio(s) {
  const lista = Array.isArray(s.imagenes) && s.imagenes.length
    ? s.imagenes
    : (s.imagen ? [s.imagen] : []);

  return lista
    .map((item) => (typeof item === "string" ? item : item?.ruta_imagen))
    .map(resolverRuta)
    .filter(Boolean);
}

function asegurarModalLightbox() {
  if (document.getElementById("modalExpandirImagen")) return;

  const modalHtml = `
    <div class="modal fade" id="modalExpandirImagen" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-black text-white border border-danger rounded-4 overflow-hidden">
          <div class="modal-header border-secondary px-4 py-3">
            <div>
              <h5 class="modal-title fw-bold mb-0" id="lightboxTitulo"></h5>
              <small class="text-white-50" id="lightboxContador"></small>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body p-0 position-relative d-flex align-items-center justify-content-center bg-black" style="min-height: 60vh; max-height: 75vh;">
            <img id="lightboxImagen" src="" alt="" class="img-fluid w-100" style="max-height: 75vh; object-fit: contain;">
            <button type="button" id="lightboxPrev" class="btn btn-dark border border-secondary position-absolute start-0 ms-3 rounded-circle" style="width: 46px; height: 46px;" aria-label="Anterior">
              <i class="bi bi-chevron-left fs-5"></i>
            </button>
            <button type="button" id="lightboxNext" class="btn btn-dark border border-secondary position-absolute end-0 me-3 rounded-circle" style="width: 46px; height: 46px;" aria-label="Siguiente">
              <i class="bi bi-chevron-right fs-5"></i>
            </button>
          </div>
          <div class="modal-footer border-secondary justify-content-between px-4 py-3">
            <span class="text-danger fw-bold fs-5" id="lightboxPrecio"></span>
            <button type="button" id="lightboxAgendar" class="btn btn-danger fw-bold px-4">
              <i class="bi bi-calendar-check me-1"></i>Agendar este servicio
            </button>
          </div>
        </div>
      </div>
    </div>
  `;

  document.body.insertAdjacentHTML("beforeend", modalHtml);

  let estadoActual = { idServicio: null, rutas: [], index: 0, titulo: "", precio: "" };
  const modalEl = document.getElementById("modalExpandirImagen");
  const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);

  function actualizarVistaLightbox() {
    const imgEl = document.getElementById("lightboxImagen");
    const contEl = document.getElementById("lightboxContador");
    const btnPrev = document.getElementById("lightboxPrev");
    const btnNext = document.getElementById("lightboxNext");

    imgEl.src = estadoActual.rutas[estadoActual.index] || "";
    imgEl.alt = `${estadoActual.titulo} - ${estadoActual.index + 1}`;
    contEl.textContent = `Foto ${estadoActual.index + 1} de ${estadoActual.rutas.length}`;

    const multiple = estadoActual.rutas.length > 1;
    btnPrev.style.display = multiple ? "inline-flex" : "none";
    btnNext.style.display = multiple ? "inline-flex" : "none";
  }

  document.getElementById("lightboxPrev").addEventListener("click", () => {
    if (estadoActual.rutas.length <= 1) return;
    estadoActual.index = (estadoActual.index - 1 + estadoActual.rutas.length) % estadoActual.rutas.length;
    actualizarVistaLightbox();
  });

  document.getElementById("lightboxNext").addEventListener("click", () => {
    if (estadoActual.rutas.length <= 1) return;
    estadoActual.index = (estadoActual.index + 1) % estadoActual.rutas.length;
    actualizarVistaLightbox();
  });

  document.getElementById("lightboxAgendar").addEventListener("click", () => {
    if (estadoActual.idServicio) {
      seleccionarServicioParaAgendar(estadoActual.idServicio);
    }
  });

  window.abrirLightboxServicio = function (idServicio, indiceInicial = 0) {
    const data = galeriasPorServicio.get(Number(idServicio));
    if (!data || !data.rutas.length) return;

    estadoActual = {
      idServicio: data.idServicio,
      rutas: data.rutas,
      index: Math.max(0, Math.min(indiceInicial, data.rutas.length - 1)),
      titulo: data.titulo,
      precio: data.precio,
    };

    document.getElementById("lightboxTitulo").textContent = estadoActual.titulo;
    document.getElementById("lightboxPrecio").textContent = estadoActual.precio;
    actualizarVistaLightbox();
    bsModal.show();
  };
}

function renderMediaHome(s, rutas) {
  if (!rutas.length) {
    return `
      <div class="service-media w-100 d-flex align-items-center justify-content-center bg-black" style="height: 260px;">
        <i class="bi bi-scissors text-danger fs-1"></i>
      </div>`;
  }

  const idServ = parseInt(s.id_servicio, 10);
  const carouselId = `homeCarousel-${idServ}`;

  return `
    <div class="service-media w-100 bg-black position-relative" onclick="event.stopPropagation()">
      <div id="${carouselId}" class="carousel slide w-100" data-bs-ride="false">
        <div class="carousel-inner w-100" style="height: 260px;">
          ${rutas.map((ruta, idx) => `
            <div class="carousel-item w-100 h-100 ${idx === 0 ? "active" : ""}">
              <img src="${esc(ruta)}"
                   class="d-block w-100 h-100"
                   style="object-fit: cover; cursor: zoom-in;"
                   alt="${esc(s.nombre_servicio)} - ${idx + 1}"
                   loading="lazy"
                   onclick="abrirLightboxServicio(${idServ}, ${idx})">
            </div>
          `).join("")}
        </div>
        ${rutas.length > 1 ? `
          <button class="carousel-control-prev" type="button" data-bs-target="#${carouselId}" data-bs-slide="prev" aria-label="Anterior">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#${carouselId}" data-bs-slide="next" aria-label="Siguiente">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
          </button>
        ` : ""}
        <button type="button"
                class="btn btn-sm btn-dark border border-secondary position-absolute bottom-0 end-0 m-2 rounded-3"
                title="Expandir imagen"
                onclick="abrirLightboxServicio(${idServ}, 0)">
          <i class="bi bi-arrows-fullscreen text-danger"></i>
        </button>
      </div>
    </div>`;
}

function seleccionarServicioParaAgendar(idServicio) {
  localStorage.setItem("servicioPreseleccionado", String(idServicio));
  window.location.href = `/barberBox/views/cliente-panel.php?servicio=${encodeURIComponent(idServicio)}`;
}

async function cargarServicios() {
  try {
    asegurarModalLightbox();
    galeriasPorServicio.clear();

    const datos = new FormData();
    datos.append("opcion", "consultarServicios");

    const respuesta = await fetch(DAO_URL, {
      method: "POST",
      body: datos,
    });

    if (!respuesta.ok) {
      throw new Error("Error HTTP: " + respuesta.status);
    }

    const registros = await respuesta.json();

    if (!Array.isArray(registros)) {
      throw new Error(registros.mensaje || "Respuesta no válida");
    }

    if (totalServicios) totalServicios.textContent = registros.length;

    if (!registros.length) {
      listaServicios.innerHTML = `
        <div class="col-12 text-center text-white-50">
          <i class="bi bi-info-circle fs-3 d-block mb-2"></i>Pronto publicaremos nuestros servicios.
        </div>`;
      return;
    }

    listaServicios.innerHTML = registros
      .map((s) => {
        const idServ = parseInt(s.id_servicio, 10);
        const rutas = obtenerImagenesServicio(s);

        galeriasPorServicio.set(idServ, {
          idServicio: idServ,
          rutas,
          titulo: s.nombre_servicio,
          precio: `$${s.precio}`,
        });

        return `
          <div class="col-12 col-sm-6 col-lg-4">
            <div class="service-card h-100 d-flex flex-column" style="cursor: pointer;" onclick="seleccionarServicioParaAgendar(${idServ})">
              ${renderMediaHome(s, rutas)}
              <div class="p-4 d-flex flex-column flex-grow-1 text-center">
                <h4 class="fw-bold text-light mb-2">${esc(s.nombre_servicio)}</h4>
                <p class="text-white-50 small flex-grow-1">${esc(s.descripcion)}</p>
                <div class="service-meta mb-2"><i class="bi bi-clock me-1"></i>${parseInt(s.duracion_estimada, 10)} min</div>
                <div class="service-price mb-3">$${esc(s.precio)}</div>
                <button type="button" class="btn btn-danger w-100 fw-bold" onclick="event.stopPropagation(); seleccionarServicioParaAgendar(${idServ})">
                  <i class="bi bi-calendar-check me-1"></i>Agendar este servicio
                </button>
              </div>
            </div>
          </div>`;
      })
      .join("");
  } catch (error) {
    console.error("Error al cargar servicios:", error);
    listaServicios.innerHTML =
      '<div class="col-12 text-center text-danger">No se pudieron cargar los servicios.</div>';
  }
}

document.addEventListener("DOMContentLoaded", async () => {
  if (!listaServicios) return;
  await cargarServicios();
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