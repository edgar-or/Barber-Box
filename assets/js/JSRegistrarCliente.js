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

const formulario = document.getElementById("formRegistrarEmpleado");
const btnGuardar = document.getElementById("btnRegistrar");
const nombre = document.getElementById("nombre");
const apellido = document.getElementById("apellido");
const telefono = document.getElementById("telefono");
const correo = document.getElementById("correo");
const usuario = document.getElementById("usuario");
const contrasenia = document.getElementById("contrasenia");
const contrasenia_confirm = document.getElementById("contrasenia_confirm");

const campos = [nombre, apellido, telefono, correo, usuario, contrasenia, contrasenia_confirm];
const todosLosCampos = [...campos];

if (formulario) {
    ////////////////////VALIDACION DE CAMPOS VACIOS
    campos.forEach(function (campo) {
        campo.addEventListener("blur", function () {
            validarCampo(campo);
        });
    });

    ////////////////////PASAR TODO A MAYUSCULAS
    const campos1 = [nombre, apellido]; 
    campos1.forEach(function (campo) {
        campo.addEventListener("input", function () {
            campo.value = campo.value.toUpperCase();
            validarCampo(campo);
        });
    });


    telefono.addEventListener("input", function () {
        let valor = telefono.value.replace(/\D/g, "");
        valor = valor.substring(0, 8);
        if (valor.length > 5) {
            valor = valor.substring(0, 4) + "-" + valor.substring(4);
        }
        telefono.value = valor;
    });

    
    btnGuardar.addEventListener("click", async function () {
     const passCoinciden = contrasenia.value === contrasenia_confirm.value && contrasenia.value.trim() !== "";

     if (!passCoinciden) {
        Swal.fire({
                icon: "warning",
                title: "Atención",
                text: "Contraseña de confirmacion no coincide o está vacía. Por favor, verifique."
            });
            return;
    }

        // Validar todo el formulario
        const camposValidos = campos.map(validarCampo).every(Boolean) && passCoinciden;

        if (!camposValidos) {
            Swal.fire({
                icon: "warning",
                title: "Atención",
                text: "Por favor, complete todos los campos obligatorios marcados en rojo."
            });
            return;
        }

        if (btnGuardar.textContent.trim() == "Registrar") {
            try {
                const datos = new FormData(formulario);
                datos.append("opcion", "guardar");

                btnGuardar.disabled = true;
                btnGuardar.textContent = "Procesando...";

                const respuesta = await fetch("DAO/DAOCliente.php", {
                    method: "POST",
                    body: datos
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
                btnGuardar.textContent = "Guardar";
            }
        }

        if (btnGuardar.textContent.trim() == "Modificar") {
            try {
                const datos = new FormData(formulario);
                datos.append("opcion", "modificar");
                datos.append("idempleado", btnGuardar.dataset.id);

                btnGuardar.disabled = true;
                btnGuardar.textContent = "Procesando...";

                const respuesta = await fetch("DAO/DAOEmpleado.php", {
                    method: "POST",
                    body: datos
                });

                if (!respuesta.ok) {
                    throw new Error("Error HTTP: " + respuesta.status);
                }

                const resultado = await respuesta.json();
                if (resultado.estado) {
                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: "Poseidón",
                        text: resultado.mensaje,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = "empleado.php";
                    });
                } else {
                    Swal.fire({
                        position: "top-end",
                        icon: "error",
                        title: "Poseidón",
                        text: resultado.mensaje,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({
                    icon: "error",
                    title: "Poseidón",
                    text: "No se pudieron modificar los datos."
                });
            } finally {
                btnGuardar.disabled = false;
                btnGuardar.textContent = "Modificar";
            }
        }
    });
}

///////////////////LIMPIAR TODOS LOS CAMPOS///////////
function limpiarCampos() {
    todosLosCampos.forEach(function (campo) {
        campo.value = "";
        campo.classList.remove("is-invalid");
        campo.classList.remove("is-valid");
    });
}

//////////PROCESA AL TERMINAR DE CARGAR LA PAGINA
document.addEventListener("DOMContentLoaded", async () => {
    //ALINEAR EL SELECT DEL CARGO
    try {
        const datos = new FormData();
        datos.append("opcion", "consultar");

        const respuesta = await fetch("DAOCargo.php", { method: "POST", body: datos });
        const resultado = await respuesta.json();
        const selectCargo = document.getElementById("cargo");

        selectCargo.innerHTML = '<option value="0">[SELECCIONE UN CARGO]</option>';
        resultado.forEach(cargoItem => {
            const opcion = document.createElement("option");
            opcion.value = cargoItem.idcargo;
            opcion.textContent = cargoItem.nombre;
            selectCargo.appendChild(opcion);
        });
    } catch (error) {
        console.error("Error al cargar cargos:", error);
    }
    
    //ALINEAR EL SELECT DEL CANTON
    try {
        const datos = new FormData();
        datos.append("opcion", "consultar");

        const respuesta = await fetch("DAOCanton.php", { method: "POST", body: datos });
        const resultado = await respuesta.json();
        const selectCanton = document.getElementById("canton");

        selectCanton.innerHTML = '<option value="0">[SELECCIONE UN CANTON]</option>';
        resultado.forEach(cantonItem => {
            const opcion = document.createElement("option");
            opcion.value = cantonItem.idcanton;
            opcion.textContent = cantonItem.nombre;
            selectCanton.appendChild(opcion);
        });
    } catch (error) {
        console.error("Error al cargar cantones:", error);
    }

    // Obtener el ID de la URL (Modo Edición)
    const parametros = new URLSearchParams(window.location.search);
    const idempleado = parametros.get("id");

    if (idempleado) {
        try {
            const datos = new FormData();
            datos.append("opcion", "consultarIndividual");
            datos.append("idempleado", idempleado);

            Swal.fire({
                title: "Consultando...",
                text: "Espere",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            const respuesta = await fetch("DAOEmpleado.php", { method: "POST", body: datos });
            const resultado = await respuesta.json();
            Swal.close();

            if (resultado.estado) {
                dui.value = resultado.datos.dui;
                nombre.value = resultado.datos.nombre;
                apellido.value = resultado.datos.apellido;
                telefono.value = resultado.datos.telefono;
                isss.value = resultado.datos.isss || "";
                afp.value = resultado.datos.afp || "";
                nup.value = resultado.datos.nup || "";
                renta.value = resultado.datos.renta || "0";
                sueldo.value = resultado.datos.sueldo || "";
                cargo.value = resultado.datos.cargo || "0";
                canton.value = resultado.datos.canton || "0";
                estado.value = resultado.datos.estado || "0";
                fechaIngreso.value = resultado.datos.fecha_ingreso || "";

                btnGuardar.textContent = "Modificar";
                btnGuardar.dataset.id = resultado.datos.idempleado;
            }
        } catch (error) {
            Swal.close();
            console.error(error);
        }
    }
});