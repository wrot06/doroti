<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require "rene/conexion3.php"; // Archivo de conexión a la base de datos

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
    $_SESSION['caja'] = filter_input(INPUT_POST, 'consulta', FILTER_SANITIZE_NUMBER_INT);
    $_SESSION['car2'] = filter_input(INPUT_POST, 'carpeta', FILTER_SANITIZE_NUMBER_INT);
}

$caja = $_SESSION['caja'] ?? null;
$carpeta = $_SESSION['car2'] ?? null;

if (!ctype_digit((string)$caja) || !ctype_digit((string)$carpeta)) {
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
    <link rel="icon" type="image/png" href="hueso.png">
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

<div style="display: flex; align-items: center; gap: 1rem; white-space: nowrap;">
    <h5 style="margin: 0;">Caja <?= htmlspecialchars($caja) ?> | Carpeta <?= htmlspecialchars($carpeta) ?></h5>
    <form action="tcarpeta.php" method="POST" style="margin: 0;">
        <input type="hidden" name="caja" value="<?= htmlspecialchars($caja) ?>">
        <input type="hidden" name="carpeta" value="<?= htmlspecialchars($carpeta) ?>">
        <input type="hidden" name="folios" value="<?= $ultimaPagina ?>">    
        <button type="submit" class="btn btn-primary btn-sm" style="padding: .25rem .5rem;">Terminar Carpeta</button>
    </form>
    <form action="index.php" method="POST" style="margin: 0;">
        <button type="submit" class="btn btn-primary btn-sm" style="padding: .25rem .5rem;">Inicio</button>
    </form>
</div>

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
                    <td colspan="5" class="text-center">No hay folios registrados. folio inicial: 1</td>
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
                        <td contenteditable="true" class="editable" ><?= htmlspecialchars($capitulo['DescripcionUnidadDocumental'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($capitulo['NoFolioInicio'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($capitulo['NoFolioFin'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><button class="btn-dark btn-sm editar">Editar</button></td> <!-- Añadido class 'btn-sm' -->
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
<div class="form-group">
  <div class="input-group mb-2">
    <input type="text" id="textoInput" class="form-control" placeholder="" aria-label="Texto para insertar" />
    <div class="input-group-append">
      <button class="btn btn-outline-primary" type="button" onclick="agregarAlTextarea()">+</button>
    </div>
  </div>
  <textarea id="titulo" class="form-control form-control-sm" placeholder="Asunto" required style="font-size: 1.2rem; height: 150px;"></textarea>
</div>
   
        <div class="form-row align-items-center mb-2"> <!-- Reduce el margen inferior -->
            <button type="button" id="grabarBoton" class="btn btn-warning btn-sm">Grabar (F2-F9)</button> <!-- Añadido class 'btn-sm' -->
            <p id="ultimaPagina" class="ml-1 mb-0">Último folio: <?= $ultimaPagina + 1 ?></p> <!-- Añadido clase 'mb-0' -->
            <div class="col-auto">
                <input type="number" id="paginaFinal" class="form-control form-control" placeholder="Página de Finalización" value="<?= $ultimaPagina + 1 ?>" required style="font-size: 1.2rem;"> <!-- Añadido class 'form-control-sm' -->
            </div>
            <button type="submit" class="btn btn-primary btn-sm ml-2">Agregar (ENTER)</button> <!-- Añadido class 'btn-sm' -->
        </div>
    </form>
    <br><br><br><br><br> <!-- espacio abajo del la grabación o del bloque de grabación -->
</div>


<!-- Modal para editar capítulo -->
<div class="modal fade" id="editarCapituloModal" tabindex="-1" role="dialog" aria-labelledby="modalEditarLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document"> <!-- Tamaño XL -->
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEditarLabel">Editar Detalle</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="editId">

        <div class="form-group">
          <label for="editSerie">Serie</label>
          <select class="form-control" id="editSerie">
            <!-- Se llenará dinámicamente -->
          </select>
        </div>

        <div class="form-group">
          <label for="editTitulo">Título</label>
          <textarea class="form-control" id="editTitulo" rows="6" style="font-size: 1.2rem; resize: vertical;"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Descartar</button>
        <button type="button" class="btn btn-primary" id="guardarCambios">Guardar (ENTER)</button>
      </div>
    </div>
  </div>
</div>


<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<script>
    window.caja = <?= $caja ?>;
    window.carpeta = <?= $carpeta ?>;
</script>
<script src="js/inicializar.js"></script>
<script src="js/capituloForm.js"></script>
<script src="js/tablaCapitulos.js"></script>
<script src="js/textotareainsert.js"></script>
<script src="js/voice.js"></script>
<script src="js/navegacionInputs.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        activarNavegacionConEnter("titulo", "paginaFinal");
    });
</script>
<script src="js/editcapitulo.js"></script>
<script src="js/centrarpantalla.js"></script>

</body>
</html>