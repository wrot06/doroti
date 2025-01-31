<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require "rene/conexion3.php"; // Archivo de conexión a la base de datos

session_start();

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['cerrar_seccion'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Validar y asignar valores POST a variables de sesión
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['caja'] = filter_input(INPUT_POST, 'Caja', FILTER_SANITIZE_NUMBER_INT);
    $_SESSION['car2'] = filter_input(INPUT_POST, 'Car2', FILTER_SANITIZE_NUMBER_INT);
}

$caja = $_SESSION['caja'] ?? null;
$carpeta = $_SESSION['car2'] ?? null;

// Validar que $caja y $carpeta sean válidos
if (is_null($caja) || is_null($carpeta) || !is_numeric($caja) || !is_numeric($carpeta)) {
    die("Parámetros inválidos. Por favor, selecciona Caja y Carpeta.");
}

if ($conec->connect_error) {
    die("Error de conexión: " . $conec->connect_error);
}

// Preparar consulta SQL para obtener los capítulos
$sql_get_capitulos = "SELECT id2, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas FROM IndiceTemp WHERE Caja = ? AND Carpeta = ?";
$stmt_get_capitulos = $conec->prepare($sql_get_capitulos);
$stmt_get_capitulos->bind_param("ii", $caja, $carpeta);
$stmt_get_capitulos->execute();
$result_capitulos = $stmt_get_capitulos->get_result();

$capitulos = [];
$ultimaPagina = 0; // Inicializar última página

while ($row = $result_capitulos->fetch_assoc()) {
    $capitulos[] = $row;
    $ultimaPagina = max($ultimaPagina, (int)$row['NoFolioFin']); // Determinar el mayor valor de NoFolioFin
}

$stmt_get_capitulos->close();
$conec->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indice Documental</title>
    <link rel="stylesheet" href="css/estiloindice.css">
    <link rel="stylesheet" href="css/botongrabar.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
</head>
<body>

<div class="container mt-4"> <!-- Reduce el margen superior -->

<h5 class="mb-2 d-inline">
    Caja <?= htmlspecialchars($caja, ENT_QUOTES, 'UTF-8'); ?> | Carpeta <?= htmlspecialchars($carpeta, ENT_QUOTES, 'UTF-8'); ?>
</h5>
<form action="tcarpeta.php" method="POST" style="display: inline;">
    <input type="hidden" name="caja" value="<?= htmlspecialchars($caja, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="carpeta" value="<?= htmlspecialchars($carpeta, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" id="ultimaPagina1" name="folios" value="<?= $ultimaPagina ?>">    
    <button type="submit" class="btn btn-primary btn-sm" style="padding: .25rem .5rem; font-size: .75rem; margin-left: 10px;">Terminar Carpeta</button>
</form>




    <table class="table table-bordered table-sm" id="capitulosTable"> <!-- Añadido class 'table-sm' -->
        <thead class="thead-light">
            <tr>
                <th></th>
                <th>Descripción</th>
                <th>Inicio</th>
                <th>Final</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($capitulos)): ?>
                <tr>
                    <td colspan="5" class="text-center">No hay capítulos registrados. Página inicial: 1</td>
                </tr>
            <?php else: ?>
                <?php foreach ($capitulos as $capitulo): ?>
                    <?php
                    // Calcular el número de páginas
                    $numPaginas = (int)$capitulo['NoFolioFin'] - (int)$capitulo['NoFolioInicio'] + 1;
                    ?>
                    <tr data-id="<?= htmlspecialchars($capitulo['id2'], ENT_QUOTES, 'UTF-8'); ?>" 
                        data-num-paginas="<?= $numPaginas; ?>">
                        <td class="drag-column"><span class="drag-icon">&#x21D5;</span></td>
                        <td contenteditable="true" class="editable"><?= htmlspecialchars($capitulo['DescripcionUnidadDocumental'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($capitulo['NoFolioInicio'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($capitulo['NoFolioFin'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><button class="btn btn-success btn-sm eliminar">Eliminar</button></td> <!-- Añadido class 'btn-sm' -->
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

<div class="etiquetas mb-1" style="font-size: 0.75rem;">
    <div class="etiquetas-container d-flex flex-wrap align-items-center" style="gap: 5px;">
    <?php
        // Incluir el archivo de conexión
        require "rene/conexion3.php";

        try {
            // Realizar la consulta
            $result = $conec->query("SELECT nombre FROM Serie");

            // Comprobar si la consulta se realizó correctamente
            if (!$result) {
                throw new Exception("Error en la consulta: " . $conec->error);
            }

            // Obtener las etiquetas
            $etiquetas = [];
            while ($row = $result->fetch_assoc()) {
                $etiquetas[] = $row['nombre'];
            }

            // Mostrar las etiquetas
            echo '<div class="etiquetas mb-1" style="font-size: 0.75rem;">';
            echo '<div class="etiquetas-container d-flex flex-wrap align-items-center" style="gap: 5px;">';

            foreach ($etiquetas as $etiqueta) {
                echo "<div class='form-check form-check-inline' style='margin-bottom: 2px;'>
                        <input class='form-check-input' type='radio' name='etiqueta' value='{$etiqueta}' id='etiqueta-{$etiqueta}' style='width: 0.85rem; height: 0.85rem;'>
                        <label class='form-check-label' for='etiqueta-{$etiqueta}' style='font-size: 0.75rem; margin-left: 3px;'>" . ucfirst($etiqueta) . "</label>
                    </div>";
            }

            echo '</div></div>';

        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }

        // Cerrar la conexión
        $conec->close();
    ?>

    </div>
</div>



    <form id="capituloForm">
        <h2 class="h6 mb-2">Asunto</h2> <!-- Añadido clase 'h6' y reducido el margen inferior -->
        <div class="form-group">
        <textarea id="titulo" class="form-control form-control-sm" placeholder="Describir" required style="font-size: 1.2rem;"></textarea>
    </div>
   
        <div class="form-row align-items-center mb-2"> <!-- Reduce el margen inferior -->
            <button type="button" id="grabarBoton" class="btn btn-warning btn-sm">Grabar (F2)</button> <!-- Añadido class 'btn-sm' -->
            <p id="ultimaPagina" class="ml-1 mb-0">Última página: <?= $ultimaPagina + 1 ?></p> <!-- Añadido clase 'mb-0' -->
            <div class="col-auto">
                <input type="number" id="paginaFinal" class="form-control form-control" placeholder="Página de Finalización" value="<?= $ultimaPagina + 1 ?>" required style="font-size: 1.2rem;"> <!-- Añadido class 'form-control-sm' -->
            </div>
            <button type="submit" class="btn btn-primary btn-sm ml-2">Agregar Documento</button> <!-- Añadido class 'btn-sm' -->
        </div>
    </form>
    <br><br><br><br><br> <!-- espacio abajo del la grabación o del bloque de grabación -->
</div>

<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<script>

//Inicia el puntero  en el textarea
document.addEventListener('DOMContentLoaded', function() {
        // Enfocar el textarea
        document.getElementById('titulo').focus();
});

// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
        // Enfocar el textarea al cargar la página
        const tituloTextarea = document.getElementById('titulo');
        tituloTextarea.focus();

        // Manejar el evento de pulsación de teclas en el textarea
        tituloTextarea.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Evitar el comportamiento predeterminado de saltar de línea
                const tituloValue = tituloTextarea.value.trim();
                
                // Solo desplazar si el textarea no está vacío
            if (tituloValue) {
                document.getElementById('paginaFinal').focus(); // Enfocar el siguiente input
            }
        }
    });
});



$(document).ready(function() {
let siguientePagina = <?= empty($capitulos) ? 1 : $paginaSiguiente ?? 1 ?>; // Página inicial
inicializarPaginas();//Inicialisa las Paginas cuando se carla la pargian por primera vez


// Agregar un nuevo capítulo
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
            caja: <?= $caja ?>,
            carpeta: <?= $carpeta ?>,
            titulo: `${etiquetaSeleccionada}: ${titulo}`,
            paginaFinal: paginaFinal
        },
        dataType: 'json',
        success: function(response) {
            console.log("Respuesta del servidor:", response);
            if (response.status === 'success') {
                const nuevoCapitulo = response.capitulo;

                // Eliminar el mensaje de "No hay capítulos registrados" si existe
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

                // Actualizar la siguiente página
                siguientePagina = parseInt(nuevoCapitulo.pagina_final, 10) + 1;
                $("#ultimaPagina").text(`Última página: ${siguientePagina}`);
                $("#paginaFinal").val(siguientePagina);
                $("#ultimaPagina1").val(nuevoCapitulo.pagina_final);
                $("#titulo").val('');
                $("#titulo").focus();

                // Desplazar la pantalla al pie de página
                $('html, body').animate({
                    scrollTop: $(document).height() // Desplaza al final del documento
                }, 500);
            } else {
                alert(response.message || "Error al agregar el capítulo.");
            }
        },
        error: function(xhr, status, error) {
            console.error("Error en la solicitud AJAX:", error);
            console.log("Estado:", status);
            console.log("Respuesta del servidor:", xhr.responseText);
            alert(`Error: ${xhr.status} - ${xhr.statusText}\nDetalles: ${xhr.responseText}`);
        }
    });
});






// Agregar un nuevo capítulo Tecla ENTER
$("#titulo").keypress(function(event) {
    if (event.which === 13) { // 13 es el código de la tecla Enter
        event.preventDefault(); // Evita que se envíe el formulario
        agregarCapitulo(); // Llama a la función para agregar el capítulo
        $("#titulo").val(''); // Limpia el textarea
    }
});





// Función para recalcular páginas e ID después del reordenamiento
function actualizarPaginas() {
    let siguientePagina = 1; // Página inicial para el primer capítulo
    let ultimaPaginaCalculada = 0; // Para calcular la última página global
    let nuevoId2 = 1; // ID inicial para el primer capítulo


    $("#capitulosTable tbody tr").each(function() {
        const $fila = $(this);

        // Obtener el número de páginas desde el atributo data-num-paginas
        const numPaginas = parseInt($fila.attr("data-num-paginas"), 10);
        if (isNaN(numPaginas) || numPaginas < 1) {
            console.error(`Error: Número de páginas inválido en la fila ${$fila.index() + 1}`);
            return; // Saltar esta fila si hay un error
        }

        // Calcular las nuevas páginas de inicio y fin
        const paginaInicio = siguientePagina;
        const paginaFinal = paginaInicio + numPaginas - 1;
        $("#ultimaPagina1").val(`${paginaFinal}`);

        // Actualizar las celdas visibles en la tabla
        $fila.find("td:eq(2)").text(paginaInicio); // Página Inicio
        $fila.find("td:eq(3)").text(paginaFinal); // Página Final

        // Actualizar el atributo data-id con el nuevo ID
        $fila.attr("data-id", nuevoId2);

        // Actualizar la celda del ID visible, si es necesario
        $fila.find("td:eq(0)").text(nuevoId2); // Supongamos que la columna ID está en la posición 0

        // Actualizar siguiente página disponible y el ID
        siguientePagina = paginaFinal + 1;
        nuevoId2++;

        // Mantener un seguimiento de la última página calculada
        ultimaPaginaCalculada = paginaFinal;
    });

    // Actualizar la última página global
    actualizarUltimaPagina(ultimaPaginaCalculada);
}



// Función para actualizar la última página en la interfaz
function actualizarUltimaPagina(ultimaPagina) {
    // Asegurar que la variable global se actualice
    siguientePagina = ultimaPagina + 1;     
    // Actualizar el texto que muestra la última página en la interfaz
    $("#ultimaPagina").text(`Última página: ${siguientePagina}`);    
    // Establecer el valor del input que muestra la página final
    $("#paginaFinal").val(`${siguientePagina}`);    
    // Asegúrate de que `pagina_final` tenga un valor válido y esté definido
    if (typeof pagina_final !== 'undefined') {
        $("#ultimaPagina1").val(`${ultimaPagina}`);
    } else {
        console.warn("La variable 'pagina_final' no está definida.");
    }
}



// Reordenar las filas de la tabla
$("#capitulosTable tbody").sortable({
    items: "tr",
    cursor: "move",
    placeholder: "highlight",
    handle: ".drag-icon",
    update: function() {
        actualizarPaginas();
        
        // Obtener el nuevo orden y actualizar los IDs
        const nuevoOrden = $("#capitulosTable tbody tr").map(function(index) {
            const $fila = $(this);

            // Actualizar el `id2` visualmente en la tabla (columna del índice)
            const nuevoId = index + 1;
            $fila.data("id", nuevoId); // Actualizar el atributo `data-id`
            $fila.find("td:first").html(`<span class="drag-icon">&#x21D5;</span>`); // Restaurar el ícono de arrastre
            
            // Obtener valores de la fila
            const titulo = $fila.find("td:eq(1)").text(); // Columna del título del capítulo
            const inicio = parseInt($fila.find("td:eq(2)").text(), 10); // Página de inicio
            const fin = parseInt($fila.find("td:eq(3)").text(), 10); // Página final
            const paginas = fin - inicio + 1; // Calcular número de páginas
            
            return { id: nuevoId, titulo: titulo, inicio: inicio, fin: fin, paginas: paginas };
        }).get();

        // Convertir los datos a JSON
        const jsonData = JSON.stringify({
            cambios: nuevoOrden,
            caja: <?= $caja ?>,
            carpeta: <?= $carpeta ?>
        });

        // Hacer una llamada AJAX para actualizar la base de datos
        $.ajax({
            url: 'rene/actualizar_orden.php', // URL del archivo PHP que maneja la actualización
            method: 'GET', // Cambia a 'POST' si prefieres
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

    // Asumiendo que ya tienes estas variables definidas en tu script
    const caja = <?= $caja ?>; // Número de caja
    const carpeta = <?= $carpeta ?>; // Número de carpeta

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
                        actualizarPaginas(); // Actualiza las páginas después de eliminar
                        actualizarColumnasMover(); // Reestablece las columnas de movimiento
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
            // Si no hay columna de movimiento, añádela
            $(this).prepend(`
                <td class="drag-column"><span class="drag-icon">&#x21D5;</span></td>
            `);
        } else {
            // Si existe, asegúrate de que muestra el ícono correcto
            $columnaMover.html('<span class="drag-icon">&#x21D5;</span>');
        }
    });
}




// Permitir la edición del título
$(document).on("blur", ".editable", function() {
        const nuevoTitulo = $(this).text().trim();
        if (!nuevoTitulo) {
            alert("El título no puede estar vacío.");
            $(this).text("Título"); // Restaurar texto predeterminado
        }
    });
});

//Actuliza la ultima Pagina
function actualizarUltimaPagina(ultimaPagina) {
    siguientePagina = ultimaPagina + 1; // Asegurar que la variable global se actualice
    $("#ultimaPagina").text(`Última página: ${siguientePagina}`);
    $("#ultimaPagina1").val(`${ultimaPagina}`);
}


//INCIA la pagina con el numero de paginas correctos de cada capitulo
function inicializarPaginas() {
    // Realizar una solicitud AJAX para obtener los datos desde el servidor
    $.ajax({
        url: 'rene/obtener_capitulos.php', // Ruta del script en el servidor
        type: 'GET',
        data: {
            caja: <?= $caja ?>,
            carpeta: <?= $carpeta ?>
        },
        dataType: 'json',
        success: function(response) {
            if (Array.isArray(response)) {
                let siguientePagina = 0; // Inicializa en 0 para asegurar un valor correcto

                response.forEach((capitulo, index) => {
                    const { id, titulo, paginas, paginaInicio, paginaFinal } = capitulo;

                    // Validar los datos recibidos
                    if (isNaN(paginaInicio) || isNaN(paginaFinal) || paginaFinal < paginaInicio) {
                        console.error(`Error en el capítulo ${index + 1}: Datos inválidos. Inicio: ${paginaInicio}, Final: ${paginaFinal}`);
                        return;
                    }

                    // Insertar o actualizar las filas en la tabla
                    const $fila = $(`#capitulosTable tbody tr[data-id="${id}"]`);
                    if ($fila.length) {
                        // Actualizar la fila existente
                        $fila.find("td:eq(2)").text(paginaInicio);
                        $fila.find("td:eq(3)").text(paginaFinal);
                        $fila.data("num-paginas", paginas);
                    } else {
                        // Crear una nueva fila si no existe
                        $("#capitulosTable tbody").append(`
                            <tr data-id="${id}" data-num-paginas="${paginas}">
                                <td class="drag-column"><span class="drag-icon">&#x21D5;</span></td>
                                <td contenteditable="true" class="editable">${titulo}</td>
                                <td>${paginaInicio}</td>
                                <td>${paginaFinal}</td>
                                <td><button class="eliminar">Eliminar</button></td>
                            </tr>
                        `);
                    }

                    // Actualizar el siguiente página con el valor más alto de paginaFinal
                    if (paginaFinal > siguientePagina) {
                        siguientePagina = paginaFinal; // Solo guarda el valor más alto
                    }
                });

                // Llamar a la función para actualizar la última página
                actualizarUltimaPagina(siguientePagina);
                console.log("Inicialización de páginas completada.");
            } else {
                console.error("Error: Formato de datos no válido.");
            }
        },
        error: function(xhr, status, error) {
            console.error("Error al obtener los datos de los capítulos:", error);
            console.log("Respuesta del servidor:", xhr.responseText); // Muestra la respuesta completa
        }
    });
}



// Grabar voz con el micrófono y convertir a texto
let grabando = false; // Estado de grabación
        let recognition; // Reconocimiento de voz

        function iniciarReconocimiento() {
            // Comprobación del soporte para la API de reconocimiento de voz
            if (!('webkitSpeechRecognition' in window)) {
                alert("Lo siento, tu navegador no soporta esta función.");
                return;
            }

            recognition = new webkitSpeechRecognition();
            recognition.lang = 'es-CO'; // Español Colombia
            recognition.continuous = true; // Mantener reconocimiento continuo
            recognition.interimResults = false; // Desactivar resultados intermedios

            recognition.onresult = function(event) {
                let textarea = document.getElementById('titulo');
                let nuevoTexto = "";

                for (let i = event.resultIndex; i < event.results.length; i++) {
                    const resultado = event.results[i];
                    if (resultado.isFinal) {
                        nuevoTexto += (nuevoTexto ? " " : "") + resultado[0].transcript; // Acumular resultados finales
                    }
                }

                // Agregar texto al input
                textarea.value += nuevoTexto.trim() + " "; // Agregar el texto acumulado
                // Enfocar y mover el cursor al final del texto
                textarea.focus();
                textarea.setSelectionRange(textarea.value.length, textarea.value.length);
            };

            recognition.onend = function() {
                // Reinicia el reconocimiento si se sigue grabando
                if (grabando) {
                    recognition.start();
                }
            };

            recognition.start(); // Iniciar reconocimiento
            grabando = true; // Actualiza el estado
            document.getElementById('grabarBoton').classList.add('grabando'); // Cambia color a verde
        }

        // Función para detener el reconocimiento
        function detenerReconocimiento() {
            if (recognition) {
                recognition.stop(); // Detiene reconocimiento
            }
            grabando = false; // Actualiza el estado
            document.getElementById('grabarBoton').classList.remove('grabando'); // Cambia color a rojo

            // Enfocar nuevamente el textarea y mover el cursor al final
            const textarea = document.getElementById('titulo');
            textarea.focus();
            textarea.setSelectionRange(textarea.value.length, textarea.value.length);
        }

        // Manejar el clic en el botón de grabar
        document.getElementById('grabarBoton').addEventListener('click', function() {
            if (!grabando) {
                iniciarReconocimiento(); // Inicia reconocimiento
            } else {
                detenerReconocimiento(); // Detiene reconocimiento
            }
        });

        // Mantener grabando mientras se presiona "F2"
        document.addEventListener('keydown', function(event) {
            if (event.key === 'F2' && !grabando) { // Si se presiona F2 y no está grabando
                iniciarReconocimiento(); // Inicia reconocimiento
            }
        });

        // Detener la grabación al soltar "F2"
        document.addEventListener('keyup', function(event) {
            if (event.key === 'F2' && grabando) { // Si se suelta F2 y se está grabando
                detenerReconocimiento(); // Detiene reconocimiento
            }
        });


</script>

</body>
</html>