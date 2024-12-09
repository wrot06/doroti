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
    <title>Reordenar Capítulos</title>
    <link rel="stylesheet" href="css/estiloIndice.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
</head>
<body>

<div class="etiquetas">
    <h3>Caja <?= htmlspecialchars($caja, ENT_QUOTES, 'UTF-8'); ?> Carpeta <?= htmlspecialchars($carpeta, ENT_QUOTES, 'UTF-8'); ?></h3>
    <?php
    $etiquetas = ['correspondencia', 'acuerdos', 'resoluciones', 'actas', 'constancias', 'certificaciones', 'listados', 'proposiciones'];
    foreach ($etiquetas as $etiqueta) {
        echo "<label><input type='radio' name='etiqueta' value='{$etiqueta}'> " . ucfirst($etiqueta) . "</label>";
    }
    ?>
</div>

<table id="capitulosTable">
    <thead>
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
                <td colspan="5">No hay capítulos registrados. Página inicial: 1</td>
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
                    <td><button class="eliminar">Eliminar</button></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>


<form id="capituloForm">
    <h2>Agregar Folios</h2>    
    <input type="text" id="titulo" placeholder="Describir" required style="width: 80%; height: 40px; font-size: 16px;"> 
    <div class="form-row">
        <p id="ultimaPagina">Última página: <?= $ultimaPagina + 1 ?></p>      
        <input type="number" id="paginaFinal" placeholder="Página de Finalización" required>
        <button type="submit">Agregar Folios</button>
    </div>
</form>

<script>
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

    // Recalcular siguientePagina basado en la tabla
    let ultimaPaginaTabla = 0;
    $("#capitulosTable tbody tr").each(function() {
        const filaPaginaFinal = parseInt($(this).find("td:eq(3)").text(), 10);
        if (!isNaN(filaPaginaFinal)) {
            ultimaPaginaTabla = Math.max(ultimaPaginaTabla, filaPaginaFinal);
        }
    });

    const siguientePagina = ultimaPaginaTabla + 1; // La siguiente página disponible

    if (isNaN(paginaFinal) || paginaFinal < siguientePagina) {
        alert("La página de finalización debe ser un número válido mayor o igual a la página de inicio.");
        return;
    }

    const numPaginas = paginaFinal - siguientePagina + 1;

    $.ajax({
        url: 'rene/agregar_capitulo.php',
        type: 'POST',
        data: {
            caja: <?= $caja ?>,
            carpeta: <?= $carpeta ?>,
            titulo: `${etiquetaSeleccionada}: ${titulo}`,
            paginaInicio: siguientePagina,
            paginaFinal: paginaFinal,
            paginas2: numPaginas // Enviar el número de páginas
        },
        success: function(response) {
            try {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    $("#capitulosTable tbody").find("tr:first-child:contains('No hay capítulos')").remove();

                    $("#capitulosTable tbody").append(`
                        <tr data-id="${data.id}" data-num-paginas="${numPaginas}">
                            <td class="drag-column"><span class="drag-icon">&#x21D5;</span></td>
                            <td contenteditable="true" class="editable">${etiquetaSeleccionada}: ${titulo}</td>
                            <td>${siguientePagina}</td>
                            <td>${paginaFinal}</td>
                            <td><button class="eliminar">Eliminar</button></td>
                        </tr>
                    `);

                    $("#titulo").val('');
                    $("#paginaFinal").val('');
                    actualizarUltimaPagina();
                } else {
                    alert(data.message || "Error al agregar el capítulo.");
                }
            } catch (e) {
                console.error("Error en la respuesta del servidor:", e);
                alert("Error al procesar la solicitud.");
            }
        },
        error: function() {
            alert("Error en la solicitud. Por favor, intenta nuevamente.");
        }
    });
});




// Función para actualizar la última página
function actualizarUltimaPagina() {
        $("#ultimaPagina").text(`Última página: ${siguientePagina}`);
    }


// Función para recalcular páginas después del reordenamiento
   function actualizarPaginas() {
    let siguientePagina = 1; // Página inicial para el primer capítulo

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

        // Actualizar las celdas visibles en la tabla
        $fila.find("td:eq(2)").text(paginaInicio); // Página Inicio
        $fila.find("td:eq(3)").text(paginaFinal); // Página Final

        // Actualizar siguiente página disponible
        siguientePagina = paginaFinal + 1;
    });

    actualizarUltimaPagina(); // Actualizar un indicador global si es necesario
}






// Reordenar las filas de la tabla
$("#capitulosTable tbody").sortable({
    items: "tr",
    cursor: "move",
    placeholder: "highlight",
    handle: ".drag-icon",
    update: function() {
        actualizarPaginas();
        const nuevoOrden = $("#capitulosTable tbody tr").map(function(index) {
            return { id: $(this).data("id"), orden: index + 1 };
        }).get();

        $.ajax({
            url: 'rene/actualizar_orden.php',
            type: 'POST',
            data: { orden: nuevoOrden },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.status !== 'success') {
                    alert(data.message || "Error al actualizar el orden.");
                }
            },
            error: function() {
                alert("Error al actualizar el orden. Por favor, intenta nuevamente.");
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
                data: { id: idCapitulo },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            $fila.remove();
                            actualizarPaginas();
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


// Permitir la edición del título
$(document).on("blur", ".editable", function() {
        const nuevoTitulo = $(this).text().trim();
        if (!nuevoTitulo) {
            alert("El título no puede estar vacío.");
            $(this).text("Título"); // Restaurar texto predeterminado
        }
    });
});


//INCIA la apgina con eel numero de pagians correctos de cada capitulo
function inicializarPaginas() {
    // Realizar una solicitud AJAX para obtener los datos desde el servidor
    $.ajax({
        url: 'rene/obtener_capitulos.php', // Ruta del script en el servidor
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (Array.isArray(response)) {
                let siguientePagina = 1;

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

                    siguientePagina = paginaFinal + 1;
                });

                actualizarUltimaPagina();
                console.log("Inicialización de páginas completada.");
            } else {
                console.error("Error: Formato de datos no válido.");
            }
        },
        error: function(xhr, status, error) {
            console.error("Error al obtener los datos de los capítulos:", error);
        }
    });
}



</script>

</body>
</html>