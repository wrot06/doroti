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

    // Calcular la última página como la suma de NoFolioFin
    $ultimaPagina += (int) $row['NoFolioFin']; // Aquí suma el valor de NoFolioFin
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
                <tr data-id="<?= htmlspecialchars($capitulo['id2'], ENT_QUOTES, 'UTF-8'); ?>">
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
        <p id="ultimaPagina">Última página: <?= $ultimaPagina ?></p>      
        <input type="number" id="paginaFinal" placeholder="Página de Finalización" required>
        <button type="submit">Agregar Folios</button>
    </div>
</form>

<script>
$(document).ready(function() {
let siguientePagina = <?= empty($capitulos) ? 1 : $paginaSiguiente ?? 1 ?>; // Página inicial


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

    $.ajax({
        url: 'rene/agregar_capitulo.php',
        type: 'POST',
        data: {
            caja: <?= $caja ?>,
            carpeta: <?= $carpeta ?>,
            titulo: `${etiquetaSeleccionada}: ${titulo}`,
            paginaInicio: paginaInicio,
            paginaFinal: paginaFinal,
            paginas2: numPaginas // Asegúrate de enviar el número de páginas al servidor
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
                            <td>${paginaInicio}</td>
                            <td>${paginaFinal}</td>
                            <td><button class="eliminar">Eliminar</button></td>
                        </tr>
                    `);

                    $("#titulo").val('');
                    $("#paginaFinal").val('');
                    siguientePagina = paginaFinal + 1;
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


// Función para reordenar capítulos y actualizar páginas
function actualizarPaginas() {
    let siguientePagina = 1;

    $("#capitulosTable tbody tr").each(function() {
        const $fila = $(this);

        // Obtener numPaginas desde data
        const numPaginas = parseInt($fila.data("num-paginas"), 10) || 1;

        // Calcular paginaInicio y paginaFinal
        const paginaInicio = siguientePagina; // La página de inicio es el siguiente número disponible
        const paginaFinal = paginaInicio + numPaginas - 1; // La página final se calcula con numPaginas

        // Validar que la página final sea mayor o igual que la página de inicio
        if (isNaN(paginaFinal) || paginaFinal < paginaInicio) {
            console.error(`Error en la fila ${$fila.index() + 1}: Página final (${paginaFinal}) no puede ser menor que la página de inicio (${paginaInicio}).`);
            return; // Salir de esta iteración si hay un error
        }

        // Actualizar el valor de paginas2 y logear la información
        const paginas2 = numPaginas; // Aquí se calcula el valor de paginas2
        console.log(`Fila: ${$fila.index() + 1}, Num Páginas: ${numPaginas}, Inicio: ${paginaInicio}, Final: ${paginaFinal}, Paginas2: ${paginas2}`);

        $fila.find("td:eq(2)").text(paginaInicio); // Actualizar columna de página de inicio
        $fila.find("td:eq(3)").text(paginaFinal); // Actualizar columna de página final
        $fila.data("num-paginas", numPaginas); // Actualizar el dato de num-paginas en el elemento

        // Actualizar siguientePágina para la próxima fila
        siguientePagina = paginaFinal + 1; 
    });

    actualizarUltimaPagina();
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

</script>

</body>
</html>