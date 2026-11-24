const comisionPorcentaje = document.getElementById("comisionPorcentaje");
const btnGuardarComision = document.getElementById("btnGuardarComision");

async function cargarComision() {
    try {
        const datos = new FormData();
        datos.append("opcion", "consultar");

        const respuesta = await fetch("../DAO/DAOComision.php", { method: "POST", body: datos });
        if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);

        const resultado = await respuesta.json();

        if (resultado.estado) {
            comisionPorcentaje.value = resultado.datos.comision;
            btnGuardarComision.textContent = "Modificar";
            btnGuardarComision.dataset.id = resultado.datos.id_comision;
        } else {
            btnGuardarComision.textContent = "Guardar";
            delete btnGuardarComision.dataset.id;
        }
    } catch (error) {
        console.error("Error al cargar la comisión:", error);
    }
}

btnGuardarComision.addEventListener("click", async function () {
    const valor = comisionPorcentaje.value.trim();

    if (valor === "" || isNaN(valor) || parseFloat(valor) < 0 || parseFloat(valor) > 100) {
        comisionPorcentaje.classList.add("is-invalid");
        Swal.fire({ icon: "warning", title: "Atención", text: "Ingrese una comisión entre 0 y 100." });
        return;
    }
    comisionPorcentaje.classList.remove("is-invalid");

    const modificando = btnGuardarComision.textContent.trim() == "Modificar";

    try {
        const datos = new FormData();
        datos.append("opcion", modificando ? "modificar" : "guardar");
        datos.append("comision", valor);
        if (modificando) datos.append("id_comision", btnGuardarComision.dataset.id);

        btnGuardarComision.disabled = true;
        btnGuardarComision.textContent = "Procesando...";

        const respuesta = await fetch("../DAO/DAOComision.php", { method: "POST", body: datos });
        if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);

        const resultado = await respuesta.json();

        Swal.fire({
            position: "top-end",
            icon: resultado.estado ? "success" : "error",
            title: "Barber-Box",
            text: resultado.mensaje,
            showConfirmButton: false,
            timer: 1500
        });
    } catch (error) {
        console.error(error);
        Swal.fire({ icon: "error", title: "Barber-Box", text: "Error al conectar con el servidor." });
    } finally {
        btnGuardarComision.disabled = false;
        await cargarComision(); // vuelve a poner "Guardar" o "Modificar" según corresponda
    }
});

document.addEventListener("DOMContentLoaded", cargarComision);