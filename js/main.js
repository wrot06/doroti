$(document).ready(function() {
    // Código anterior...

    $("#cerrarCarpeta").click(function() {
        const capitulos = [];

        $("#capitulosTable tbody tr").each(function() {
            const etiqueta = $(this).find(".editable").text().split(": ")[0];
            const titulo = $(this).find(".editable").text().split(": ")[1];
            const inicio = $(this).find("td:nth-child(3)").text();
            const final = $(this).find("td:nth-child(4)").text();
            
            capitulos.push({ etiqueta, titulo, inicio, final });
        });

        $.ajax({
            url: 'subir_capitulos.php',
            type: 'POST',
            data: { capitulos: JSON.stringify(capitulos) },
            success: function(response) {
                alert(response);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert("Error al subir los capítulos: " + textStatus);
            }
        });
    });
});


$(document).ready(function() {
    let siguientePagina = 1; // Inicializar la siguiente página disponible

    // Cargar los datos desde localStorage al cargar la página
    cargarDatosDesdeStorage();

    // Función para agregar un nuevo capítulo
    $("#capituloForm").submit(function(event) {
        event.preventDefault(); // Evitar el envío del formulario

        // Verificar que se ha seleccionado una etiqueta
        const etiquetaSeleccionada = $("input[name='etiqueta']:checked").val();
        if (!etiquetaSeleccionada) {
            alert("Por favor, selecciona una etiqueta antes de agregar un capítulo.");
            return;
        }

        // Obtener el título y la página final del formulario
        const titulo = $("#titulo").val();
        const paginaFinal = parseInt($("#paginaFinal").val());

        // Calcular la página de inicio
        const paginaInicio = siguientePagina;

        // Si la página final es menor que la página de inicio, no agregar el capítulo
        if (paginaFinal < paginaInicio) {
            alert("La página de finalización debe ser igual o mayor que la página de inicio.");
            return;
        }

        if (isNaN(paginaFinal) || paginaFinal <= 0) {
            alert("Por favor, ingresa un número válido para la página final.");
            return;
        }


        // Calcular el número de páginas
        const numPaginas = paginaFinal - paginaInicio + 1;

        // Agregar la nueva fila a la tabla al final
        const nuevaFila = `
            <tr data-num-paginas="${numPaginas}">
                <td class="drag-column"><span class="drag-icon">&#x21D5;</span></td> <!-- Ícono de arrastre -->
                <td contenteditable="true" class="editable">${etiquetaSeleccionada}: ${titulo}</td>
                <td>${paginaInicio}</td>
                <td>${paginaFinal}</td>
                <td><button class="eliminar">Eliminar</button></td> <!-- Botón para eliminar -->
            </tr>
        `;
        $("#capitulosTable tbody").append(nuevaFila);

        // Limpiar el campo de título y la página final, pero mantener la selección de etiqueta
        $("#titulo").val('');
        $("#paginaFinal").val('');

        // Actualizar el orden de las filas
        actualizarPaginas();

        // Mantener el enfoque en el campo de título
        $("#titulo").focus();

        // Guardar los datos en localStorage
        guardarDatosEnStorage();
    });

    // Hacer que las filas de la tabla sean reordenables
    $("#capitulosTable tbody").sortable({
        items: "tr",
        cursor: "move",
        placeholder: "highlight",
        handle: ".drag-icon", // Hacer que el arrastre funcione solo desde la columna del ícono
        update: function(event, ui) {
            // Actualizar el orden de las páginas al mover filas
            actualizarPaginas();
            // Guardar los datos en localStorage
            guardarDatosEnStorage();
        }
    });
    $("#capitulosTable tbody").disableSelection(); // Desactiva la selección de texto

    // Función para actualizar los números de las páginas
    function actualizarPaginas() {
        // Resetear la siguiente página disponible
        siguientePagina = 1; // Volver a la página inicial

        $("#capitulosTable tbody tr").each(function() {
            const $fila = $(this);
            const numPaginas = parseInt($fila.data("num-paginas")); // Obtener el número de páginas del capítulo

            // Actualizar la página de inicio y finalización basándonos en la siguiente página
            const nuevaPaginaInicio = siguientePagina;
            const nuevaPaginaFinal = siguientePagina + numPaginas - 1; // Calcular la página final

            // Actualizar los valores de las páginas
            $fila.find("td:eq(2)").text(nuevaPaginaInicio); // Actualizar página de inicio
            $fila.find("td:eq(3)").text(nuevaPaginaFinal); // Actualizar página de finalización

            // Actualizar la siguiente página disponible
            siguientePagina = nuevaPaginaFinal + 1; // La siguiente página inicia después de la página final actual
        });

        // Mostrar la última página del último capítulo
        $("#ultimaPagina").text(`Folio: ${siguientePagina}`); // Mostrar el último folio
    }

    // Función para eliminar una fila
    $(document).on("click", ".eliminar", function() {
        const $fila = $(this).closest("tr");
        const confirmar = confirm("¿Está seguro de que desea eliminar este capítulo?");
        if (confirmar) {
            $fila.remove(); // Eliminar la fila
            actualizarPaginas(); // Actualizar números de páginas después de eliminar
            // Guardar los datos en localStorage
            guardarDatosEnStorage();
        }
    });

    // Función para cargar los datos desde localStorage
    function cargarDatosDesdeStorage() {
        const capitulos = JSON.parse(localStorage.getItem("capitulos")) || [];
        capitulos.forEach(capitulo => {
            const nuevaFila = `
                <tr data-num-paginas="${capitulo.noFolioFin - capitulo.noFolioInicio + 1}">
                    <td class="drag-column"><span class="drag-icon">&#x21D5;</span></td> <!-- Ícono de arrastre -->
                    <td contenteditable="true" class="editable">${capitulo.descripcionUnidadDocumental}</td>
                    <td>${capitulo.noFolioInicio}</td>
                    <td>${capitulo.noFolioFin}</td>
                    <td><button class="eliminar">Eliminar</button></td> <!-- Botón para eliminar -->
                </tr>
            `;
            $("#capitulosTable tbody").append(nuevaFila);
        });
        actualizarPaginas(); // Actualizar el estado de las páginas
    }
});

function guardarDatosEnStorage() {
    const capitulos = [];
    $("#capitulosTable tbody tr").each(function() {
        const $fila = $(this);
        const descripcion = $fila.find("td:eq(1)").text();
        const inicio = $fila.find("td:eq(2)").text();
        const final = $fila.find("td:eq(3)").text();
        capitulos.push({
            caja: 18, // Establece el valor de Caja según sea necesario
            carpeta: 1, // Establece el valor de Carpeta según sea necesario
            descripcionUnidadDocumental: descripcion,
            noFolioInicio: parseInt(inicio),
            noFolioFin: parseInt(final),
            soporte: 'Físico' // Cambia esto según tus necesidades
        });
    });

    localStorage.setItem("capitulos", JSON.stringify(capitulos));

    // Enviar datos al servidor
    $.ajax({
        url: 'rene/guardarDatos.php', // Cambia la ruta si es necesario
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(capitulos), // Aquí se envía un arreglo de capítulos
        success: function(response) {
            const result = JSON.parse(response);
            if (result.success) {
                alert("Datos guardados en la base de datos.");
            } else {
                alert("Error al guardar en la base de datos: " + result.error);
            }
        },
        error: function(xhr, status, error) {
            alert("Error al hacer la solicitud: " + error);
        }
    });

    

}