const DAO_URL = "/barberBox/DAO/DAOHome.php";

const listaServicios = document.getElementById("listaServicios");
const totalServicios = document.getElementById("totalServicios");

///////////////////SERVICIOS///////////
async function cargarServicios() {
  try {
    const datos = new FormData();
    datos.append("opcion", "consultarServicios");

    const respuesta = await fetch("/barberBox/DAO/DAOHome.php", {
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
      .map(
        (s) => `
          <div class="col-12 col-sm-6 col-lg-4">
            <div class="service-card h-100 d-flex flex-column">
              <div class="service-media">
                ${
                  s.imagen
                    ? `<img src="${esc(s.imagen)}" alt="${esc(s.nombre_servicio)}" loading="lazy">`
                    : '<i class="bi bi-scissors"></i>'
                }
              </div>
              <div class="p-4 d-flex flex-column flex-grow-1 text-center">
                <h4 class="fw-bold text-light mb-2">${esc(s.nombre_servicio)}</h4>
                <p class="text-white-50 small flex-grow-1">${esc(s.descripcion)}</p>
                <div class="service-meta mb-2"><i class="bi bi-clock me-1"></i>${parseInt(s.duracion_estimada, 10)} min</div>
                <div class="service-price">$${esc(s.precio)}</div>
              </div>
            </div>
          </div>`,
      )
      .join("");
  } catch (error) {
    console.error("Error al cargar servicios:", error);
    listaServicios.innerHTML =
      '<div class="col-12 text-center text-danger">No se pudieron cargar los servicios.</div>';
  }
}

//////////PROCESA AL TERMINAR DE CARGAR LA PAGINA
document.addEventListener("DOMContentLoaded", async () => {
  if (!listaServicios) return;

  await cargarServicios();
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