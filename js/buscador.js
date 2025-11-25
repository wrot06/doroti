function searchTable() {
    const term = document.getElementById('search').value.toLowerCase().trim();

    const yearStartInput = document.getElementById('anio_inicio').value.trim();
    const yearEndInput   = document.getElementById('anio_final').value.trim();

    const yearStart = yearStartInput ? parseInt(yearStartInput) : null;
    const yearEnd   = yearEndInput ? parseInt(yearEndInput) : null;

    const rows = document.querySelectorAll('#tableBody > tr:not(.panel)');

    rows.forEach(row => {
        const panel = row.nextElementSibling;
        let match = false;

        const fechas = row.querySelectorAll('.fecha'); // FInicial y FFinal
        let yearIni = null;
        let yearFin = null;

        if (fechas.length >= 2) {
            const fIni = fechas[0].textContent.trim();
            const fFin = fechas[1].textContent.trim();

            if (fIni) yearIni = parseInt(fIni.substring(0,4));
            if (fFin) yearFin = parseInt(fFin.substring(0,4));
        }

        // FILTRO POR AÑOS
        let pasaFiltro = true;
        if (yearStart !== null && yearIni !== null && yearIni < yearStart) pasaFiltro = false;
        if (yearEnd   !== null && yearFin !== null && yearFin > yearEnd)   pasaFiltro = false;

        if (!pasaFiltro) {
            row.style.display = 'none';
            if (panel) panel.style.display = 'none';
            return;
        }

        // BUSCADOR DE TEXTO EN FILA
        row.querySelectorAll('td').forEach(cell => {
            if (cell.textContent.toLowerCase().includes(term)) match = true;
        });

        // BUSCADOR EN PANEL
        if (!match && panel && panel.classList.contains('panel')) {
            if (panel.textContent.toLowerCase().includes(term)) match = true;
        }

        // MOSTRAR / OCULTAR
        row.style.display = match ? '' : 'none';
        if (panel) panel.style.display = match ? '' : 'none';
    });
}

// ACORDEÓN
document.querySelectorAll('.accordion').forEach(btn => {
    btn.addEventListener('click', function() {
        const panel = this.closest('tr').nextElementSibling;
        panel.style.display = panel.style.display === 'table-row' ? 'none' : 'table-row';
    });
});
