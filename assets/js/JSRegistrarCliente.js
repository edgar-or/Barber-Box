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
const codigoVerificacion = document.getElementById("codigoVerificacion");
const btnEnviarCodigo = document.getElementById("btnEnviarCodigo");

const HTML_BTN_REGISTRAR = 'Registrar <i class="bi bi-person-plus"></i>';

const campos = [nombre, apellido, telefono, correo, usuario, contrasenia, contrasenia_confirm, codigoVerificacion];
const todosLosCampos = [...campos];

if (formulario) {
    //////////////////// VALIDACION DE CAMPOS VACIOS
    campos.forEach(function (campo) {
        campo.addEventListener("blur", function () {
            validarCampo(campo);
        });
    });

    //////////////////// PASAR TODO A MAYUSCULAS
    const campos1 = [nombre, apellido];
    campos1.forEach(function (campo) {
        campo.addEventListener("input", function () {
            campo.value = campo.value.toUpperCase();
            validarCampo(campo);
        });
    });

    //////////////////// FORMATO DE TELEFONO
    telefono.addEventListener("input", function () {
        let valor = telefono.value.replace(/\D/g, "");
        valor = valor.substring(0, 8);
        if (valor.length > 5) {
            valor = valor.substring(0, 4) + "-" + valor.substring(4);
        }
        telefono.value = valor;
    });

    //////////////////// BOTON GUARDAR / MODIFICAR
    btnGuardar.addEventListener("click", async function () {
        let passCoinciden = true; // Por defecto true si se está editando

        if (btnGuardar.textContent.trim() == "Registrar") {
            passCoinciden = contrasenia.value === contrasenia_confirm.value && contrasenia.value.trim() !== "";

            if (!passCoinciden) {
                Swal.fire({
                    icon: "warning",
                    title: "Atención",
                    text: "Contraseña de confirmacion no coincide o está vacía. Por favor, verifique."
                });
                return;
            }

        
            const nombreValido      = validarCampo(nombre);
            const apellidoValido    = validarCampo(apellido);
            const telefonoValido    = validarCampo(telefono);
            const correoValido      = validarCampo(correo);
            const usuarioValido     = validarCampo(usuario);
            const passValido        = validarCampo(contrasenia);
            const passConfirmValido = validarCampo(contrasenia_confirm);
            const codigoValido      = validarCampo(codigoVerificacion);

            if (!nombreValido && !apellidoValido && !telefonoValido && !correoValido &&
                !usuarioValido && !passValido && !passConfirmValido && !codigoValido) {
                Swal.fire({
                    icon: "warning",
                    title: "Atención",
                    text: "Por favor, complete todos los campos obligatorios marcados en rojo."
                });
                return;
            }

            if (nombreValido && apellidoValido && telefonoValido && correoValido &&
                usuarioValido && passValido && passConfirmValido && codigoValido && passCoinciden) {
                try {
                    const datos = new FormData(formulario);
                    datos.append("opcion", "guardar");

                    btnGuardar.disabled = true;
                    btnGuardar.textContent = "Procesando...";

                    const respuesta = await fetch("../DAO/DAOCliente.php", {
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
                        });
                        limpiarCampos();
                        setTimeout(() => { window.location.href = "../page-login.php"; }, 1600);
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
                        text: "Error al conectar con el servidor."
                    });
                } finally {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = HTML_BTN_REGISTRAR;
                }
            }
        }

        if (btnGuardar.textContent.trim() == "Modificar") {
            try {
                const datos = new FormData(formulario);
                datos.append("opcion", "modificar");
                datos.append("idcliente", btnGuardar.dataset.id);

                btnGuardar.disabled = true;
                btnGuardar.textContent = "Procesando...";

                const respuesta = await fetch("../DAO/DAOCliente.php", {
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
                        window.location.href = "tabla-clientes.php";
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

/////////////////// LIMPIAR TODOS LOS CAMPOS
function limpiarCampos() {
    todosLosCampos.forEach(function (campo) {
        campo.value = "";
        campo.classList.remove("is-invalid");
        campo.classList.remove("is-valid");
    });
}

////////// PROCESA AL TERMINAR DE CARGAR LA PAGINA
document.addEventListener("DOMContentLoaded", async () => {
    // Obtener el ID de la URL (Modo Edición)
    const parametros = new URLSearchParams(window.location.search);
    const idcliente = parametros.get("id");

    if (idcliente) {
        try {
            const datos = new FormData();
            datos.append("opcion", "consultarIndividual");
            datos.append("idcliente", idcliente);

            Swal.fire({
                title: "Consultando...",
                text: "Espere",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            const respuesta = await fetch("../DAO/DAOCliente.php", { method: "POST", body: datos });
            const resultado = await respuesta.json();
            Swal.close();

            if (resultado.estado) {
                nombre.value = resultado.datos.nombre;
                apellido.value = resultado.datos.apellido;
                telefono.value = resultado.datos.telefono;
                correo.value = resultado.datos.correo;

                if (codigoVerificacion) {
                    codigoVerificacion.required = false;
                    codigoVerificacion.closest('.login-form-group').style.display = 'none';
                    campos.splice(campos.indexOf(codigoVerificacion), 1);
                }
                if (usuario) usuario.closest('.login-form-group').style.display = 'none';
                if (contrasenia) contrasenia.closest('.login-form-group').style.display = 'none';
                if (contrasenia_confirm) contrasenia_confirm.closest('.login-form-group').style.display = 'none';

                btnGuardar.textContent = "Modificar";
                btnGuardar.dataset.id = resultado.datos.idcliente;
            }
        } catch (error) {
            Swal.close();
            console.error(error);
        }
    }
});

/////////////// MOSTRAR / OCULTAR CONTRASEÑA
[["toggle-password", contrasenia], ["toggle-password-confirm", contrasenia_confirm]].forEach(([idBtn, input]) => {
    const btn = document.getElementById(idBtn);
    if (!btn || !input) return;
    btn.addEventListener("click", function () {
        const mostrar = input.type === "password";
        input.type = mostrar ? "text" : "password";
        const icono = this.querySelector("i");
        icono.classList.toggle("bi-eye", !mostrar);
        icono.classList.toggle("bi-eye-slash", mostrar);
        this.setAttribute("aria-label", mostrar ? "Ocultar contraseña" : "Mostrar contraseña");
    });
});

/////////////// ENVIAR CÓDIGO DE VERIFICACIÓN AL CORREO
const HTML_BTN_ENVIAR = '<i class="bi bi-send-fill"></i> <span>Enviar</span>';

if (btnEnviarCodigo) {
    btnEnviarCodigo.addEventListener("click", async function () {
        if (!validarCampo(correo)) {
            Swal.fire({ icon: "warning", title: "Atención", text: "Escribe primero un correo electrónico válido." });
            return;
        }

        btnEnviarCodigo.disabled = true;
        btnEnviarCodigo.innerHTML = "<span>Enviando...</span>";

        let enviado = false;
        try {
            const datos = new FormData();
            datos.append("opcion", "enviarCodigo");
            datos.append("correo", correo.value.trim());

            const respuesta = await fetch("../DAO/DAOCliente.php", { method: "POST", body: datos });
            const resultado = await respuesta.json();
            enviado = resultado.estado;

            Swal.fire({
                icon: enviado ? "success" : "error",
                title: "Barber-Box",
                text: resultado.mensaje
            });
            if (enviado) codigoVerificacion.focus();
        } catch (error) {
            console.error(error);
            Swal.fire({ icon: "error", title: "Barber-Box", text: "Error al conectar con el servidor." });
        }

        if (enviado) {
            let resto = 30;
            btnEnviarCodigo.innerHTML = "<span>30s</span>";
            const timer = setInterval(() => {
                resto--;
                btnEnviarCodigo.innerHTML = `<span>${resto}s</span>`;
                if (resto <= 0) {
                    clearInterval(timer);
                    btnEnviarCodigo.disabled = false;
                    btnEnviarCodigo.innerHTML = '<i class="bi bi-arrow-repeat"></i> <span>Reenviar</span>';
                }
            }, 1000);
        } else {
            btnEnviarCodigo.disabled = false;
            btnEnviarCodigo.innerHTML = HTML_BTN_ENVIAR;
        }
    });
}