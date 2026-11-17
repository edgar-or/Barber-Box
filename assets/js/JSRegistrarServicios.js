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

const formulario = document.getElementById("formRegistrarServicio");
const btnGuardar = document.getElementById("btnRegistrar");
const nombre = document.getElementById("nombre");
const descripcion = document.getElementById("descripcion");
const precio = document.getElementById("precio");
const duracion = document.getElementById("duracion"); 
const imagenesServicio = document.getElementById("imagenes_servicio");
const previewGaleria = document.getElementById("preview-galeria");

const campos = [nombre, descripcion, precio, duracion];
const todosLosCampos = [...campos, imagenesServicio];

if (formulario) {
    campos.forEach(function (campo) {
        if (campo) {
            campo.addEventListener("blur", function () {
                validarCampo(campo);
            });
        }
    });

    const camposMayus = [nombre, descripcion]; 
    camposMayus.forEach(function (campo) {
        if (campo) {
            campo.addEventListener("input", function () {
                campo.value = campo.value.toUpperCase();
                validarCampo(campo);
            });
        }
    });

    btnGuardar.addEventListener("click", async function (e) {
        e.preventDefault();

        const camposVisibles = campos.filter(campo => campo && campo.closest('.login-form-group').style.display !== 'none');
        const camposValidos = camposVisibles.map(validarCampo).every(Boolean);

        if (!camposValidos) {
            Swal.fire({
                icon: "warning",
                title: "Atención",
                text: "Por favor, complete todos los campos obligatorios marcados en rojo."
            });
            return;
        }

        // ACCIÓN: GUARDAR
        if (btnGuardar.textContent.trim().includes("Registrar") || btnGuardar.textContent.trim() == "Guardar") {
            try {
                const datos = new FormData(formulario);
                datos.append("opcion", "guardar");

                btnGuardar.disabled = true;
                btnGuardar.textContent = "Procesando...";
                
                const respuesta = await fetch("../DAO/DAOServicios.php", { 
                    method: "POST",
                    body: datos
                });

                if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);

                const resultado = await respuesta.json();
                
                if (resultado.estado) {
                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: "Barber-Box",
                        text: resultado.mensaje,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    limpiarCampos();
                } else {
                    Swal.fire({
                        position: "top-end",
                        icon: "error",
                        title: "Barber-Box",
                        text: resultado.mensaje,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({
                    icon: "error",
                    title: "Barber-Box",
                    text: "Error al conectar con el servidor."
                });
            } finally {
                btnGuardar.disabled = false;
                btnGuardar.textContent = "Registrar Servicio"; 
            }
        }

        // ACCIÓN: MODIFICAR
        if (btnGuardar.textContent.trim() == "Modificar") {
            try {
                const datos = new FormData(formulario);
                datos.append("opcion", "modificar");
                datos.append("idservicio", btnGuardar.dataset.id);

                btnGuardar.disabled = true;
                btnGuardar.textContent = "Procesando...";

                const respuesta = await fetch("../DAO/DAOServicios.php", { 
                    method: "POST",
                    body: datos
                });

                if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);

                const resultado = await respuesta.json();
                if (resultado.estado) {
                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: "Barber-Box", 
                        text: resultado.mensaje,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = "tabla-servicios.php"; 
                    });
                } else {
                    Swal.fire({
                        position: "top-end",
                        icon: "error",
                        title: "Barber-Box",
                        text: resultado.mensaje,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({
                    icon: "error",
                    title: "Barber-Box",
                    text: "No se pudieron modificar los datos."
                });
            } finally {
                btnGuardar.disabled = false;
                btnGuardar.textContent = "Modificar";
            }
        }
    });
}

function limpiarCampos() {
    todosLosCampos.forEach(function (campo) {
        if (campo) {
            campo.value = "";
            campo.classList.remove("is-invalid");
            campo.classList.remove("is-valid");
        }
    });
    if (previewGaleria) {
        previewGaleria.innerHTML = "";
    }
}

document.getElementById('imagenes_servicio').addEventListener('change', function(e) {
            const contenedor = document.getElementById('preview-galeria');
            contenedor.innerHTML = '';

            Array.from(e.target.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    img.className = "rounded border border-secondary";
                    img.style.height = "70px";
                    img.style.width = "70px";
                    img.style.objectFit = "cover";
                    contenedor.appendChild(img);
                }
                reader.readAsDataURL(file);
            });
        });

document.addEventListener("DOMContentLoaded", async () => {
    const parametros = new URLSearchParams(window.location.search);
    const idservicio = parametros.get("id");

    if (idservicio) {
        try {
            const datos = new FormData();
            datos.append("opcion", "consultarIndividual");
            datos.append("idservicio", idservicio);

            Swal.fire({
                title: "Consultando...",
                text: "Espere",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            const respuesta = await fetch("../DAO/DAOServicios.php", { method: "POST", body: datos });
            const resultado = await respuesta.json();
            Swal.close();

            if (resultado.estado) {
                nombre.value = resultado.datos.nombre;
                descripcion.value = resultado.datos.descripcion;
                precio.value = resultado.datos.precio;
                duracion.value = resultado.datos.duracion;

                // Mostrar galería existente si la hay al editar
                if (resultado.datos.imagenes && previewGaleria) {
                    previewGaleria.innerHTML = "";
                    resultado.datos.imagenes.forEach(imgObj => {
                        const img = document.createElement('img');
                        img.src = "/barberBox/" + imgObj.ruta_imagen;
                        img.className = "rounded border border-secondary";
                        img.style.height = "70px";
                        img.style.width = "70px";
                        img.style.objectFit = "cover";
                        previewGaleria.appendChild(img);
                    });
                }

                btnGuardar.textContent = "Modificar";
                btnGuardar.dataset.id = resultado.datos.idservicio; 
            }
        } catch (error) {
            Swal.close();
            console.error(error);
        }
    }
});