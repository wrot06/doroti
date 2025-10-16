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

        if (paginaFinal > 200) {
            alert("La página final no puede ser mayor a 200.");
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

        const filaMensaje = $capitulosTableBody.find("tr td[colspan='5']").closest("tr");
        if (filaMensaje.length) filaMensaje.remove();

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

        // 🔹 Actualizamos la próxima página
        window.siguientePagina = parseInt(nuevoCapitulo.pagina_final) + 1;
        $ultimaPagina.text(`Último folio: ${window.siguientePagina}`);

        // 🔹 Reseteamos el input y actualizamos su min dinámico
        $paginaFinalInput.val(window.siguientePagina);
        $paginaFinalInput.attr("min", window.siguientePagina);

        $tituloInput.val('').focus();
        window.textoFinal = ''; 

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
