// rotulo.js

function toggleAccordion(button) {
    const row = button.closest('tr');
    if (!row) return;
    
    // Buscar el siguiente elemento hermano que tenga la clase 'panel'
    // Esto evita que se rompa si InfinityFree inyecta scripts o anuncios entre las filas
    let panel = row.nextElementSibling;
    while (panel && !panel.classList.contains('panel')) {
        panel = panel.nextElementSibling;
    }
    if (!panel) return;
    
    if (panel.style.display === "none" || panel.style.display === "") {
        panel.style.display = "table-row";
        button.textContent = "^";
    } else {
        panel.style.display = "none";
        button.textContent = "v";
    }
}
