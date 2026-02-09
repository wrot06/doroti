function actualizarPaginas() {
    let siguiente = 1;

    $("#capitulosTable tbody tr").each(function (index) {
        const $row = $(this);
        let numPaginas = parseInt($row.attr("data-num-paginas"), 10) || 1;
        const inicio = siguiente;
        const fin = inicio + numPaginas - 1;

        $row.find("td:eq(2)").text(inicio);
        $row.find("td:eq(3)").text(fin);
        $row.attr("data-id", index + 1);

        siguiente = fin + 1;
    });

    const ultimoFolio = siguiente;
    window.siguientePagina = siguiente;

    // Actualiza la UI
    $("#ultimaPagina").text(`Último folio: ${ultimoFolio}`);
    $("#paginaFinal").val(siguiente);    // para que el input apunte al siguiente folio
    $("#paginaFinal").attr("min", siguiente); // ajusta también el mínimo
}


$("#capitulosTable tbody").sortable({
    handle: ".drag-icon",
    update: function () {
        actualizarPaginas();

        const nuevoOrden = $("#capitulosTable tbody tr").map(function (index) {
            const $row = $(this);
            return {
                id: index + 1,
                titulo: $row.find("td:eq(1)").text(),
                inicio: parseInt($row.find("td:eq(2)").text(), 10),
                fin: parseInt($row.find("td:eq(3)").text(), 10),
                paginas: parseInt($row.find("td:eq(3)").text()) - parseInt($row.find("td:eq(2)").text()) + 1
            };
        }).get();

        $.ajax({
            url: '../rene/actualizar_orden.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                cambios: nuevoOrden,
                caja: window.caja,
                carpeta: window.carpeta
            })
        });
    }
});

$(document).on("click", ".eliminar", function () {
    const $row = $(this).closest("tr");
    const id = $row.data("id");

    if (confirm("¿Eliminar este capítulo?")) {
        $.post('../rene/eliminar_capitulo.php', { id, caja: window.caja, carpeta: window.carpeta }, function (data) {
            const res = JSON.parse(data);
            if (res.status === 'success') {
                $row.remove();
                actualizarPaginas(); // recalcula todo

                if ($("#capitulosTable tbody tr").length === 0) {
                    $("#capitulosTable tbody").append(`
                        <tr>
                            <td colspan="5" class="text-center">No hay folios registrados. folio inicial: 1</td>
                        </tr>
                    `);
                    window.siguientePagina = 1;
                    $("#paginaFinal").val(1);
                    $("#ultimaPagina").text("Último folio: 0");
                }
            } else {
                alert(res.message || "Error al eliminar.");
            }
        });
    }
});

