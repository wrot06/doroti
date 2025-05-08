// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    // Enfocar el textarea 'titulo'
    const tituloTextarea = document.getElementById('titulo');
    if (tituloTextarea) {
        tituloTextarea.focus();
        tituloTextarea.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Evitar salto de línea
                if (tituloTextarea.value.trim()) {
                    document.getElementById('paginaFinal').focus();
                }
            }
        });
    }
});

// Código jQuery para el manejo de la página y formularios
$(document).ready(function() {
    let siguientePagina = siguientePaginaInitial; // Usar la variable definida en el inline script
    inicializarPaginas(); // Inicializar las páginas al cargar la página

    // Agregar un nuevo capítulo mediante el formulario
    $("#capituloForm").submit(function(event) {
        event.preventDefault();

        const etiquetaSeleccionada = $("input[name='etiqueta']:checked").val();
        if (!etiquetaSeleccionada) {
            alert("Por favor, selecciona una etiqueta antes de agregar un capítulo.");
            return;
        }

        const titulo = $("#titulo").val().trim();
        const paginaFinal = parseInt($("#paginaFinal").val());

        if (!titulo) {
            alert("El título no puede estar vacío.");
            return;
        }
        if (isNaN(paginaFinal) || paginaFinal < siguientePagina) {
            alert("La página de finalización debe ser un número válido mayor o igual a la página de inicio.");
            return;
        }

        const paginaInicio = siguientePagina;
        const numPaginas = paginaFinal - paginaInicio + 1;

        if (numPaginas > 200) {
            alert("No se puede agregar un número de folio que exceda los 200 folios.");
            return;
        }

        $.ajax({
            url: 'rene/agregar_capitulo.php',
            type: 'POST',
            data: {
                caja: caja,
                carpeta: carpeta,
                titulo: `${etiquetaSeleccionada}: ${titulo}`,
                paginaFinal: paginaFinal
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const nuevoCapitulo = response.capitulo;
                    // Eliminar el mensaje de "No hay capítulos registrados"
                    $("#capitulosTable tbody").find("tr").each(function() {
                        if ($(this).find("td").text().includes("No hay capítulos registrados")) {
                            $(this).remove();
                        }
                    });

                    // Agregar el nuevo capítulo a la tabla
                    $("#capitulosTable tbody").append(`
                        <tr data-id="${nuevoCapitulo.id}" data-num-paginas="${nuevoCapitulo.num_paginas}">
                            <td class="drag-column"><span class="drag-icon">&#x21D5;</span></td>
                            <td contenteditable="true" class="editable">${nuevoCapitulo.titulo}</td>
                            <td>${nuevoCapitulo.pagina_inicio}</td>
                            <td>${nuevoCapitulo.pagina_final}</td>
                            <td><button class="btn btn-success btn-sm eliminar">Eliminar</button></td>
                        </tr>
                    `);

                    siguientePagina = parseInt(nuevoCapitulo.pagina_final, 10) + 1;
                    $("#ultimaPagina").text(`Última página: ${siguientePagina}`);
                    $("#paginaFinal").val(siguientePagina);
                    $("#ultimaPagina1").val(nuevoCapitulo.pagina_final);
                    $("#titulo").val('').focus();

                    $('html, body').animate({
                        scrollTop: $(document).height()
                    }, 500);
                } else {
                    alert(response.message || "Error al agregar el capítulo.");
                }
            },
            error: function(xhr, status, error) {
                alert(`Error: ${xhr.status} - ${xhr.statusText}\nDetalles: ${xhr.responseText}`);
            }
        });
    });

    // Permitir agregar un nuevo capítulo al presionar ENTER en el textarea
    $("#titulo").keypress(function(event) {
        if (event.which === 13) {
            event.preventDefault();
            $("#capituloForm").submit();
            $("#titulo").val('');
        }
    });

    // Función para recalcular páginas e IDs después de reordenar las filas
    function actualizarPaginas() {
        let siguientePaginaLocal = 1;
        let ultimaPaginaCalculada = 0;
        let nuevoId2 = 1;

        $("#capitulosTable tbody tr").each(function() {
            const $fila = $(this);
            const numPaginas = parseInt($fila.attr("data-num-paginas"), 10);
            if (isNaN(numPaginas) || numPaginas < 1) {
                console.error(`Error: Número de páginas inválido en la fila ${$fila.index() + 1}`);
                return;
            }
            const paginaInicio = siguientePaginaLocal;
            const paginaFinal = paginaInicio + numPaginas - 1;
            $("#ultimaPagina1").val(`${paginaFinal}`);

            $fila.find("td:eq(2)").text(paginaInicio);
            $fila.find("td:eq(3)").text(paginaFinal);

            $fila.attr("data-id", nuevoId2);
            $fila.find("td:first").text(nuevoId2);

            siguientePaginaLocal = paginaFinal + 1;
            nuevoId2++;
            ultimaPaginaCalculada = paginaFinal;
        });

        actualizarUltimaPagina(ultimaPaginaCalculada);
    }

    // Actualizar la última página mostrada en la interfaz
    function actualizarUltimaPagina(ultimaPagina) {
        siguientePagina = ultimaPagina + 1;
        $("#ultimaPagina").text(`Última página: ${siguientePagina}`);
        $("#paginaFinal").val(`${siguientePagina}`);
        if (typeof pagina_final !== 'undefined') {
            $("#ultimaPagina1").val(`${ultimaPagina}`);
        } else {
            console.warn("La variable 'pagina_final' no está definida.");
        }
    }

    // Hacer las filas de la tabla ordenables con jQuery UI
    $("#capitulosTable tbody").sortable({
        items: "tr",
        cursor: "move",
        placeholder: "highlight",
        handle: ".drag-icon",
        update: function() {
            actualizarPaginas();
            const nuevoOrden = $("#capitulosTable tbody tr").map(function(index) {
                const $fila = $(this);
                const nuevoId = index + 1;
                $fila.data("id", nuevoId);
                $fila.find("td:first").html('<span class="drag-icon">&#x21D5;</span>');
                const titulo = $fila.find("td:eq(1)").text();
                const inicio = parseInt($fila.find("td:eq(2)").text(), 10);
                const fin = parseInt($fila.find("td:eq(3)").text(), 10);
                const paginas = fin - inicio + 1;
                return { id: nuevoId, titulo: titulo, inicio: inicio, fin: fin, paginas: paginas };
            }).get();

            const jsonData = JSON.stringify({
                cambios: nuevoOrden,
                caja: caja,
                carpeta: carpeta
            });

            $.ajax({
                url: 'rene/actualizar_orden.php',
                method: 'GET',
                data: { data: jsonData },
                success: function(response) {
                    console.log("Actualización exitosa:", response);
                },
                error: function(xhr, status, error) {
                    console.error("Error en la actualización:", error);
                    alert("Error al actualizar: " + error);
                }
            });
        }
    });

    // Eliminar un capítulo
    $(document).on("click", ".eliminar", function() {
        const $fila = $(this).closest("tr");
        const idCapitulo = $fila.data("id");

        if (confirm("¿Está seguro de que desea eliminar este capítulo?")) {
            $.ajax({
                url: 'rene/eliminar_capitulo.php',
                type: 'POST',
                data: {
                    id: idCapitulo,
                    caja: caja,
                    carpeta: carpeta
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            $fila.remove();
                            actualizarPaginas();
                            actualizarColumnasMover();
                        } else {
                            alert(data.message || "Error al eliminar el capítulo.");
                        }
                    } catch (e) {
                        console.error("Error en la respuesta del servidor:", e);
                        alert("Error al procesar la solicitud.");
                    }
                },
                error: function() {
                    alert("Error al eliminar el capítulo. Por favor, intenta nuevamente.");
                }
            });
        }
    });

    // Función para actualizar las columnas de movimiento
    function actualizarColumnasMover() {
        $("#capitulosTable tbody tr").each(function() {
            const $columnaMover = $(this).find(".drag-column");
            if (!$columnaMover.length) {
                $(this).prepend(`<td class="drag-column"><span class="drag-icon">&#x21D5;</span></td>`);
            } else {
                $columnaMover.html('<span class="drag-icon">&#x21D5;</span>');
            }
        });
    }

    // Permitir la edición del título
    $(document).on("blur", ".editable", function() {
        const nuevoTitulo = $(this).text().trim();
        if (!nuevoTitulo) {
            alert("El título no puede estar vacío.");
            $(this).text("Título");
        }
    });
});

// Funciones relacionadas con el reconocimiento de voz
let grabando = false;
let recognition;

function iniciarReconocimiento() {
    if (!('webkitSpeechRecognition' in window)) {
        alert("Lo siento, tu navegador no soporta esta función.");
        return;
    }

    recognition = new webkitSpeechRecognition();
    recognition.lang = 'es-CO';
    recognition.continuous = true;
    recognition.interimResults = false;

    recognition.onresult = function(event) {
        let textarea = document.getElementById('titulo');
        let nuevoTexto = "";

        for (let i = event.resultIndex; i < event.results.length; i++) {
            const resultado = event.results[i];
            if (resultado.isFinal) {
                let textoReconocido = resultado[0].transcript;

textoReconocido = textoReconocido.replace(/\balirio\b/gi, "Alirio");
textoReconocido = textoReconocido.replace(/\bargotti\b/gi, "Argoty");
textoReconocido = textoReconocido.replace(/\bbarreiro\b/gi, "Barreiro");
textoReconocido = textoReconocido.replace(/\bbastidas\b/gi, "Bastidas");
textoReconocido = textoReconocido.replace(/\bbelalcázar\b/gi, "Belalcázar");
textoReconocido = textoReconocido.replace(/\bbravo\b/gi, "Bravo");
textoReconocido = textoReconocido.replace(/\bbrisueno\b/gi, "Risueño");
textoReconocido = textoReconocido.replace(/\bburbano\b/gi, "Burbano");
textoReconocido = textoReconocido.replace(/\bcalpa\b/gi, "Calpa");
textoReconocido = textoReconocido.replace(/\bcalvache\b/gi, "Calvache");
textoReconocido = textoReconocido.replace(/\bcalvacci\b/gi, "Calvachy");
textoReconocido = textoReconocido.replace(/\bcalvachi\b/gi, "Calvachy");
textoReconocido = textoReconocido.replace(/\bcalvachí\b/gi, "Calvachy");
textoReconocido = textoReconocido.replace(/\bcollés\b/gi, "Goyes");
textoReconocido = textoReconocido.replace(/\bcoral\b/gi, "Coral");
textoReconocido = textoReconocido.replace(/\bcorrea\b/gi, "Correa");
textoReconocido = textoReconocido.replace(/\bcortés\b/gi, "Cortés");
textoReconocido = textoReconocido.replace(/\bcadena\b/gi, "Cadena");
textoReconocido = textoReconocido.replace(/\bcoyez\b/gi, "Goyes");
textoReconocido = textoReconocido.replace(/\bderecho\b/gi, "Derecho");
textoReconocido = textoReconocido.replace(/\bdolores\b/gi, "Dolores");
textoReconocido = textoReconocido.replace(/\bedilma\b/gi, "Edilma");
textoReconocido = textoReconocido.replace(/\bespecialización\b/gi, "Especialización");
textoReconocido = textoReconocido.replace(/\berazo\b/gi, "Erazo");
textoReconocido = textoReconocido.replace(/\bgoiles\b/gi, "Goyes");
textoReconocido = textoReconocido.replace(/\bgoyes\b/gi, "Goyes");
textoReconocido = textoReconocido.replace(/\bgoyés\b/gi, "Goyes");
textoReconocido = textoReconocido.replace(/\bgoyez\b/gi, "Goyes");
textoReconocido = textoReconocido.replace(/\bgoyis\b/gi, "Goyes");
textoReconocido = textoReconocido.replace(/\bgoiz\b/gi, "Goyes");
textoReconocido = textoReconocido.replace(/\bhoyos\b/gi, "Hoyos");
textoReconocido = textoReconocido.replace(/\bjojoa\b/gi, "Jojoa");
textoReconocido = textoReconocido.replace(/\blagos\b/gi, "Lagos");
textoReconocido = textoReconocido.replace(/\bleyton\b/gi, "Leyton");
textoReconocido = textoReconocido.replace(/\blibardo\b/gi, "Libardo");
textoReconocido = textoReconocido.replace(/\bmadroñero\b/gi, "Madroñero");
textoReconocido = textoReconocido.replace(/\bmarco\b/gi, "Marco");
textoReconocido = textoReconocido.replace(/\bmaterón\b/gi, "Materón");
textoReconocido = textoReconocido.replace(/\bmiriam\b/gi, "Myriam");
textoReconocido = textoReconocido.replace(/\bmorasurco\b/gi, "Morasurco");
textoReconocido = textoReconocido.replace(/\bmunera\b/gi, "Munera");
textoReconocido = textoReconocido.replace(/\bmaigual\b/gi, "Maigual");
textoReconocido = textoReconocido.replace(/\bmoncayo\b/gi, "Moncayo");
textoReconocido = textoReconocido.replace(/\bnariño\b/gi, "Nariño");
textoReconocido = textoReconocido.replace(/\bnavia\b/gi, "Navia");
textoReconocido = textoReconocido.replace(/\bocaña\b/gi, "Ocaña");
textoReconocido = textoReconocido.replace(/\boliva\b/gi, "Oliva");
textoReconocido = textoReconocido.replace(/\bosejo\b/gi, "Osejo");
textoReconocido = textoReconocido.replace(/\bocara\b/gi, "OCARA");
textoReconocido = textoReconocido.replace(/\bpalacios\b/gi, "Palacios");
textoReconocido = textoReconocido.replace(/\bparedes\b/gi, "Paredes");
textoReconocido = textoReconocido.replace(/\bpasos\b/gi, "Pasos");
textoReconocido = textoReconocido.replace(/\bpinilla\b/gi, "Pinilla");
textoReconocido = textoReconocido.replace(/\bPara\b/gi, "para");
textoReconocido = textoReconocido.replace(/\bramos\b/gi, "Ramos");
textoReconocido = textoReconocido.replace(/\breina\b/gi, "Reina");
textoReconocido = textoReconocido.replace(/\brevelo\b/gi, "Revelo");
textoReconocido = textoReconocido.replace(/\briascos\b/gi, "Riascos");
textoReconocido = textoReconocido.replace(/\bsolarte\b/gi, "Solarte");
textoReconocido = textoReconocido.replace(/\bsotelo\b/gi, "Sotelo");
textoReconocido = textoReconocido.replace(/\btajumbina\b/gi, "Tajumbina");
textoReconocido = textoReconocido.replace(/\buniversidad\b/gi, "Universidad");
textoReconocido = textoReconocido.replace(/\burbano\b/gi, "Urbano");
textoReconocido = textoReconocido.replace(/\bvela\b/gi, "Vela");
textoReconocido = textoReconocido.replace(/\bvillota\b/gi, "Villota");
textoReconocido = textoReconocido.replace(/\bvinueza\b/gi, "Vinueza");
textoReconocido = textoReconocido.replace(/\bviteri\b/gi, "Viteri");
textoReconocido = textoReconocido.replace(/\bzarama\b/gi, "Zarama");

                nuevoTexto += (nuevoTexto ? " " : "") + textoReconocido;
            }
        }
        textarea.value += nuevoTexto.trim() + " ";
        textarea.focus();
        textarea.setSelectionRange(textarea.value.length, textarea.value.length);
    };

    recognition.onend = function() {
        if (grabando) {
            recognition.start();
        }
    };

    recognition.start();
    grabando = true;
    document.getElementById('grabarBoton').classList.add('grabando');
}

function detenerReconocimiento() {
    if (recognition) {
        recognition.stop();
    }
    grabando = false;
    document.getElementById('grabarBoton').classList.remove('grabando');

    const textarea = document.getElementById('titulo');
    textarea.focus();
    textarea.setSelectionRange(textarea.value.length, textarea.value.length);
}

document.getElementById('grabarBoton').addEventListener('click', function() {
    if (!grabando) {
        iniciarReconocimiento();
    } else {
        detenerReconocimiento();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'F2' && !grabando) {
        iniciarReconocimiento();
    }
});

document.addEventListener('keyup', function(event) {
    if (event.key === 'F2' && grabando) {
        detenerReconocimiento();
    }
});
