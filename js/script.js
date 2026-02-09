$(document).ready(function () {
    let siguientePagina = 1;

    // Cargar datos al iniciar la página
    cargarDatosDesdeStorage();

    $("#capituloForm").submit(function (event) {
        event.preventDefault();

        const etiquetaSeleccionada = $("input[name='etiqueta']:checked").val();
        if (!etiquetaSeleccionada) {
            alert("Por favor, selecciona una etiqueta antes de agregar un capítulo.");
            return;
        }

        const titulo = $("#titulo").val();
        const paginaFinal = parseInt($("#paginaFinal").val());
        const paginaInicio = siguientePagina;

        if (paginaFinal < paginaInicio) {
            alert("La página de finalización debe ser igual o mayor que la página de inicio.");
            return;
        }

        const numPaginas = paginaFinal - paginaInicio + 1;
        const nuevaFila = `
            <tr data-num-paginas="${numPaginas}">
                <td class="drag-column"><span class="drag-icon">&#x21D5;</span></td>
                <td contenteditable="true" class="editable">${etiquetaSeleccionada}: ${titulo}</td>
                <td>${paginaInicio}</td>
                <td>${paginaFinal}</td>
                <td><button class="eliminar">Eliminar</button></td>
            </tr>
        `;
        $("#capitulosTable tbody").append(nuevaFila);

        $("#titulo").val('');
        $("#paginaFinal").val('');
        actualizarPaginas();
        guardarDatosEnStorage();
    });

    // Habilitar reordenamiento de filas
    $("#capitulosTable tbody").sortable({
        items: "tr",
        cursor: "move",
        placeholder: "highlight",
        handle: ".drag-icon",
        update: function () {
            actualizarPaginas();
            guardarDatosEnStorage(); // Solo actualiza en localStorage
        }
    });
    $("#capitulosTable tbody").disableSelection();

    function actualizarPaginas() {
        siguientePagina = 1;

        $("#capitulosTable tbody tr").each(function () {
            const $fila = $(this);
            const numPaginas = parseInt($fila.data("num-paginas"));

            const nuevaPaginaInicio = siguientePagina;
            const nuevaPaginaFinal = siguientePagina + numPaginas - 1;

            $fila.find("td:eq(2)").text(nuevaPaginaInicio);
            $fila.find("td:eq(3)").text(nuevaPaginaFinal);

            siguientePagina = nuevaPaginaFinal + 1;
        });

        $("#ultimaPagina").text(`Folio: ${siguientePagina}`);
    }

    $(document).on("click", ".eliminar", function () {
        const $fila = $(this).closest("tr");
        const confirmar = confirm("¿Está seguro de que desea eliminar este capítulo?");
        if (confirmar) {
            $fila.remove();
            actualizarPaginas();
            guardarDatosEnStorage();
        }
    });

    // Cargar datos desde localStorage
    function cargarDatosDesdeStorage() {
        const capitulos = JSON.parse(localStorage.getItem("capitulos")) || [];
        capitulos.forEach(capitulo => {
            const nuevaFila = `
                <tr data-num-paginas="${capitulo.noFolioFin - capitulo.noFolioInicio + 1}">
                    <td class="drag-column"><span class="drag-icon">&#x21D5;</span></td>
                    <td contenteditable="true" class="editable">${capitulo.descripcionUnidadDocumental}</td>
                    <td>${capitulo.noFolioInicio}</td>
                    <td>${capitulo.noFolioFin}</td>
                    <td><button class="eliminar">Eliminar</button></td>
                </tr>
            `;
            $("#capitulosTable tbody").append(nuevaFila);
        });
        actualizarPaginas();
    }

    // Guardar en localStorage
    function guardarDatosEnStorage() {
        const capitulos = [];
        $("#capitulosTable tbody tr").each(function () {
            const $fila = $(this);
            const descripcion = $fila.find("td:eq(1)").text();
            const inicio = $fila.find("td:eq(2)").text();
            const final = $fila.find("td:eq(3)").text();
            capitulos.push({
                caja: <?= json_encode($caja); ?>,
                carpeta: <?= json_encode($car2); ?>,
                descripcionUnidadDocumental: descripcion,
                noFolioInicio: parseInt(inicio),
                noFolioFin: parseInt(final),
                soporte: 'Físico'
            });
        });

        localStorage.setItem("capitulos", JSON.stringify(capitulos));
    }

    // Guardar en base de datos al cerrar la carpeta
    $("#cerrarCarpeta").click(function () {
        const capitulos = JSON.parse(localStorage.getItem("capitulos")) || [];
        if (capitulos.length === 0) {
            alert("No hay datos para guardar.");
            return;
        }

        $.ajax({
            url: 'rene/guardarDatos.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(capitulos),
            success: function (response) {
                const result = JSON.parse(response);
                if (result.success) {
                    alert("Datos guardados en la base de datos.");
                } else {
                    alert("Error al guardar en la base de datos: " + result.error);
                }
            },
            error: function (xhr, status, error) {
                alert("Error al hacer la solicitud: " + error);
            }
        });
    });
});
