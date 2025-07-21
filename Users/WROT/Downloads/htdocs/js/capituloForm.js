$(document).ready(function () {
    const $form = $("#capituloForm");
    const $tituloInput = $("#titulo");
    const $paginaFinalInput = $("#paginaFinal");
    const $capitulosTableBody = $("#capitulosTable tbody");
    const $ultimaPagina = $("#ultimaPagina");
    const $ultimaPagina1 = $("#ultimaPagina1");

    $form.on("submit", function (event) {
        event.preventDefault();

        const etiquetaSeleccionada = $("input[name='etiqueta']:checked").val();
        if (!etiquetaSeleccionada) {
            alert("Por favor, selecciona una etiqueta antes de agregar un capítulo.");
            return;
        }

        const titulo = $tituloInput.val().trim();
        if (!titulo) {
            alert("El título no puede estar vacío.");
            return;
        }

        const paginaFinal = parseInt($paginaFinalInput.val(), 10);
        if (isNaN(paginaFinal) || paginaFinal < window.siguientePagina) {
            alert("La página final debe ser válida y no menor a la inicial.");
            return;
        }

        const numPaginas = paginaFinal - window.siguientePagina + 1;
        if (numPaginas > 200) {
            alert("No se permite más de 200 folios.");
            return;
        }

        const tituloCompleto = `${etiquetaSeleccionada}: ${titulo}`;

        $.ajax({
            url: 'rene/agregar_capitulo.php',
            type: 'POST',
            data: {
                caja: window.caja,
                carpeta: window.carpeta,
                titulo: tituloCompleto,
                paginaFinal: paginaFinal,
                serie: etiquetaSeleccionada
            },
            dataType: 'json',
success: function (response) {
    if (response.status === 'success') {
        const nuevoCapitulo = response.capitulo;
        const nuevaFila = $(`
            <tr data-id="${nuevoCapitulo.id}" data-num-paginas="${nuevoCapitulo.num_paginas}">
                <td class="drag-column"><span class="drag-icon">&#x21D5;</span></td>
                <td contenteditable="true" class="editable">${nuevoCapitulo.titulo}</td>
                <td>${nuevoCapitulo.pagina_inicio}</td>
                <td>${nuevoCapitulo.pagina_final}</td>
                <td><button class="btn btn-dark btn-sm editar">Editar</button></td>
            </tr>
        `);
        $capitulosTableBody.append(nuevaFila);
        window.siguientePagina = parseInt(nuevoCapitulo.pagina_final) + 1;
        $ultimaPagina.text(`Último folio: ${window.siguientePagina}`);
        $paginaFinalInput.val(window.siguientePagina);
        $tituloInput.val('').focus();

        // 🔔 Aquí centramos la vista nuevamente en el textarea
        document.dispatchEvent(new Event("capitulo-agregado"));
    } else {
        alert(response.message || "Error al agregar el capítulo.");
    }
},

            error: function (xhr) {
                console.error("Error:", xhr.responseText);
                alert("Fallo la solicitud.");
            }
        });
    });
});
