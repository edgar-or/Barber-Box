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

document.addEventListener("DOMContentLoaded", () => {
    // 2. Obtención de elementos DENTRO de la carga de la página
    const formLogin = document.getElementById("form-login");
    const btnLogin = document.getElementById("btn-login");
    const txtUsuario = document.getElementById("usuario");
    const txtClave = document.getElementById("clave");

    // Mostrar / ocultar contraseña
    const btnTogglePassword = document.getElementById("toggle-password");
    if (btnTogglePassword && txtClave) {
        btnTogglePassword.addEventListener("click", function (e) {
            e.preventDefault();
            const mostrar = txtClave.type === "password";
            txtClave.type = mostrar ? "text" : "password";

            const icono = this.querySelector("i");
            if (icono) {
                icono.classList.toggle("bi-eye", !mostrar);
                icono.classList.toggle("bi-eye-slash", mostrar);
            }
            this.setAttribute("aria-label", mostrar ? "Ocultar contraseña" : "Mostrar contraseña");
            txtClave.focus();
        });
    }



    if(formLogin) {
    // Arreglo de campos
    const campos = [txtUsuario, txtClave];

    // 3. Tu lógica de eventos individual (blur/input)
    campos.forEach((campo) => {
        if (campo) {
            // Validar cuando el usuario sale del input (blur)
            campo.addEventListener("blur", () => validarCampo(campo));

            // Quitar clase de error mientras escribe (input)
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

            btnLogin.disabled = true;
            btnLogin.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> Validando...`;

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
                btnLogin.disabled = false;
                btnLogin.textContent = "Iniciar Sesión";
            }

        } catch (error) {
            console.error("Error en la petición de login:", error);
            Swal.fire({
                icon: "error",
                title: "Error de Conexión",
                text: "No se pudo conectar con el servidor."
            });
            btnLogin.disabled = false;
            btnLogin.textContent = "Iniciar Sesión";
        }
    });
}

    const btnCerrarSesion = document.getElementById("btn-cerrarSesion");

    if (btnCerrarSesion) {
        btnCerrarSesion.addEventListener("click", (e) => {
            e.preventDefault(); // Evitar navegación predeterminada

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
                            // Redireccionar al login tras destruir la sesión
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