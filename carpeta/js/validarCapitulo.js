document.addEventListener("DOMContentLoaded", () => {
    const paginaFinal = document.getElementById("paginaFinal");
    if (!paginaFinal) return;

    // valor mínimo (lo definimos en el atributo min del input)
    const proximaPagina = parseInt(paginaFinal.getAttribute("min"), 10);

    paginaFinal.addEventListener("input", () => {
        const valor = parseInt(paginaFinal.value, 10);
        if (valor < proximaPagina) {
            paginaFinal.value = proximaPagina;
        }
    });
});
