$(function () { 
    // Ya existen window.caja y window.carpeta definidas en el HTML
    window.siguientePagina = parseInt(window.siguientePagina || 0, 10);

    $("#titulo").focus();
    inicializarPaginas();
});

function inicializarPaginas() {
    $.getJSON('../rene/obtener_capitulos.php', {
        id_carpeta: window.id_carpeta
    }, function (response) {
        let maxFinal = 0;
        response.forEach(cap => {
            maxFinal = Math.max(maxFinal, cap.paginaFinal);
        });
        window.siguientePagina = maxFinal + 1;
        $("#ultimaPagina").text(`Último folio: ${window.siguientePagina}`);
        $("#paginaFinal").val(window.siguientePagina);
    });
}
