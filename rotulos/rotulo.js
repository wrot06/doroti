// rotulo.js

// Obtener todos los botones de acordeón
const acc = document.querySelectorAll(".accordion");

// Agregar evento de clic a cada botón
acc.forEach((button) => {
    button.addEventListener("click", function () {
        // Alternar el panel correspondiente
        const panel = this.closest('tr').nextElementSibling;
        
        // Cambiar el símbolo del acordeón (de 'v' a '^' y viceversa)
        if (panel.style.display === "table-row") {
            panel.style.display = "none";
            this.textContent = "v"; // Cambia el símbolo a 'v' cuando se cierra
        } else {
            panel.style.display = "table-row";
            this.textContent = "^"; // Cambia el símbolo a '^' cuando se abre
        }
    });
});
