function validarCampo(campo) {
    if (!campo) return false;

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

document.addEventListener("DOMContentLoaded", () => {
    const formLogin = document.getElementById("form-login");
    const btnLogin = document.getElementById("btn-login");
    const txtUsuario = document.getElementById("usuario");
    const txtClave = document.getElementById("clave");

    function restaurarBotonLogin() {
        if (btnLogin) {
            btnLogin.disabled = false;
            btnLogin.textContent = "Iniciar Sesión";
        }
    }

    function limpiarCredenciales() {
        if (formLogin) {
            formLogin.reset();
        }
        [txtUsuario, txtClave].forEach((campo) => {
            if (campo) {
                campo.value = "";
                campo.classList.remove("is-valid", "is-invalid");
            }
        });
    }

    // Mostrar / ocultar contraseña
    const btnTogglePassword = document.getElementById("toggle-password");
    if (btnTogglePassword && txtClave) {
        btnTogglePassword.addEventListener("click", (e) => {
            e.preventDefault();
            const mostrar = txtClave.type === "password";
            txtClave.type = mostrar ? "text" : "password";

            const icono = btnTogglePassword.querySelector("i");
            if (icono) {
                icono.classList.toggle("bi-eye", !mostrar);
                icono.classList.toggle("bi-eye-slash", mostrar);
            }
            btnTogglePassword.setAttribute(
                "aria-label",
                mostrar ? "Ocultar contraseña" : "Mostrar contraseña"
            );
            txtClave.focus();
        });
    }

    if (formLogin) {
        limpiarCredenciales();
        setTimeout(limpiarCredenciales, 50);
        window.addEventListener("pageshow", () => {
            limpiarCredenciales();
            setTimeout(limpiarCredenciales, 50);
        });

        const campos = [txtUsuario, txtClave];

        campos.forEach((campo) => {
            if (campo) {
                campo.addEventListener("blur", () => validarCampo(campo));
                campo.addEventListener("input", () => {
                    if (campo.value.trim() !== "") {
                        campo.classList.remove("is-invalid");
                    }
                });
            }
        });

        formLogin.addEventListener("submit", async (e) => {
            e.preventDefault();

            const camposValidos = campos.map(validarCampo).every(Boolean);
            if (!camposValidos) {
                Swal.fire({
                    icon: "warning",
                    title: "Atención",
                    text: "Por favor, complete todos los campos requeridos."
                });
                return;
            }

            try {
                const datos = new FormData(formLogin);
                datos.append("opcion", "iniciar_sesion");

                if (btnLogin) {
                    btnLogin.disabled = true;
                    btnLogin.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> Validando...`;
                }

                const respuesta = await fetch("/barberBox/DAO/DAOLogin.php", {
                    method: "POST",
                    body: datos
                });

                if (!respuesta.ok) {
                    throw new Error("Error HTTP: " + respuesta.status);
                }

                const resultado = await respuesta.json();

                if (resultado.estado) {
                    Swal.fire({
                        position: "center",
                        icon: "success",
                        title: "Acceso Permitido",
                        text: resultado.mensaje,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = resultado.redirect;
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error de Autenticación",
                        text: resultado.mensaje
                    });
                    restaurarBotonLogin();
                }
            } catch (error) {
                console.error("Error en la petición de login:", error);
                Swal.fire({
                    icon: "error",
                    title: "Error de Conexión",
                    text: "No se pudo conectar con el servidor."
                });
                restaurarBotonLogin();
            }
        });
    }

    const btnCerrarSesion = document.getElementById("btn-cerrarSesion");

    if (btnCerrarSesion) {
        btnCerrarSesion.addEventListener("click", (e) => {
            e.preventDefault();

            Swal.fire({
                title: "¿Desea cerrar sesión?",
                text: "Tendrá que ingresar sus credenciales nuevamente.",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#dc3545",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Sí, salir",
                cancelButtonText: "Cancelar"
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const datos = new FormData();
                        datos.append("opcion", "cerrar_sesion");

                        const respuesta = await fetch("/barberBox/DAO/DAOLogin.php", {
                            method: "POST",
                            body: datos
                        });

                        const resultado = await respuesta.json();

                        if (resultado.estado) {
                            window.location.href = resultado.redirect;
                        } else {
                            Swal.fire("Error", "No se pudo cerrar la sesión.", "error");
                        }
                    } catch (error) {
                        console.error("Error al cerrar sesión:", error);
                        // Redirección forzada de respaldo
                        window.location.href = "/barberBox/page-login.php";
                    }
                }
            });
        });
    }
});