$(function () {
    window.caja = <?= $caja ?>;
    window.carpeta = <?= $carpeta ?>;
    window.siguientePagina = <?= $ultimaPagina + 1 ?>;

    $("#titulo").focus();

    inicializarPaginas();
});

function inicializarPaginas() {
    $.getJSON('rene/obtener_capitulos.php', {
        caja: window.caja,
        carpeta: window.carpeta
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
