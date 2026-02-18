<?php

declare(strict_types=1);

/* ===============================
   Sesión segura
================================ */
$params = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $params['lifetime'],
    'path'     => $params['path'],
    'domain'   => $params['domain'],
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

/* ===============================
   Helpers
================================ */
function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function redirect(string $to, string $msg = ''): void
{
    if ($msg !== '') {
        $_SESSION['mensaje'] = $msg;
    }
    header("Location: {$to}");
    exit();
}

/* ===============================
   Autenticación
================================ */
if (empty($_SESSION['authenticated'])) {
    redirect('../login/login.php');
}

// Manejar cierre de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_seccion'])) {
    session_destroy();
    redirect('../login/login.php');
}

/* ===============================
   PRG: recibir datos desde index.php
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Caja'], $_POST['carpeta'], $_POST['oficina'])) {
    $_SESSION['caja']     = (int)$_POST['Caja'];
    $_SESSION['carpeta']  = (int)$_POST['carpeta'];
    $_SESSION['oficina']  = trim($_POST['oficina']); // nombre de la oficina
    $_SESSION['dependencia_id']  = (int)$_POST['dependencia_id'];
    header("Location: indice.php");
    exit();
}

/* ===============================
   Cargar desde sesión
================================ */
$caja     = $_SESSION['caja']     ?? null;
$carpeta  = $_SESSION['carpeta']  ?? null;
$oficina  = $_SESSION['oficina']  ?? null;
$dependencia_id  = $_SESSION['dependencia_id']  ?? null;

/* ===============================
   Validaciones fuertes
================================ */
if (!is_int($caja) || $caja < 1 || !is_int($carpeta) || $carpeta < 1 || empty($oficina)) {
    redirect('../index.php', 'Datos de carpeta inválidos.');
}




/* ===============================
   Mensaje flash
================================ */
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

/* ===============================
   DB
================================ */
require "../rene/conexion3.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conec->set_charset('utf8mb4');


/* ===============================
   Obtener id de la carpeta
================================ */
$id_carpeta = null;

if ($caja && $carpeta && $dependencia_id) {
    $stmtCarp = $conec->prepare("
        SELECT id 
        FROM Carpetas 
        WHERE Caja = ? AND Carpeta = ? AND dependencia_id = ? 
        LIMIT 1
    ");
    $stmtCarp->bind_param("iii", $caja, $carpeta, $dependencia_id);
    $stmtCarp->execute();
    $resCarp = $stmtCarp->get_result();
    $filaCarp = $resCarp->fetch_assoc();
    $id_carpeta = $filaCarp['id'] ?? null;
    $stmtCarp->close();
}

$_SESSION['id_carpeta'] = $id_carpeta;

/* ===============================
   1) Capítulos SOLO de esa Caja/Carpeta
================================ */
$sqlCap = "
    SELECT id2, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas
    FROM IndiceTemp
    WHERE Caja = ? AND Carpeta = ?
    ORDER BY NoFolioInicio ASC, id2 ASC
";
$stmtCap = $conec->prepare($sqlCap);
$stmtCap->bind_param('ii', $caja, $carpeta);
$stmtCap->execute();
$resCap = $stmtCap->get_result();

$capitulos = [];
$ultimaPagina = 0;

while ($row = $resCap->fetch_assoc()) {
    $row['id2']           = (int)$row['id2'];
    $row['NoFolioInicio'] = (int)$row['NoFolioInicio'];
    $row['NoFolioFin']    = (int)$row['NoFolioFin'];
    $row['paginas']       = (int)($row['paginas'] ?? 0);
    $capitulos[] = $row;
    $ultimaPagina = max($ultimaPagina, $row['NoFolioFin']);
}
$stmtCap->close();

/* ===============================
   2) Tipos documentales SOLO por dependencia
================================ */
$sqlTipo = "
    SELECT nombre
    FROM tipo_documental
    WHERE dependencia_id = ?
      AND estado = 1
    ORDER BY nombre
";
$stmtTipo = $conec->prepare($sqlTipo);
$stmtTipo->bind_param('i', $dependencia_id); // $dependencia_id = 6 por ejemplo
$stmtTipo->execute();
$resTipo = $stmtTipo->get_result();

$etiquetas = [];
while ($r = $resTipo->fetch_assoc()) {
    $etiquetas[] = $r['nombre'];
}
$stmtTipo->close();


$proximaPagina = $ultimaPagina + 1;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indice Documental</title>

    <!-- CSS -->
    <link rel="stylesheet" href="css/estiloindice.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/botongrabar.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">


</head>

<body>

    <div class="container mt-4">
        <?php if ($mensaje): ?>
            <div class="alert alert-info py-2 my-2"><?= e($mensaje) ?></div>
        <?php endif; ?>

        <div style="display:flex; align-items:center; gap:1rem; white-space:nowrap;">
            <h5 style="margin:0;">
                Caja <?= e((string)$caja) ?> | Carpeta <?= e((string)$carpeta) ?>
                <?php if ($oficina): ?>
                    <small class="text-muted">| <?= htmlspecialchars($oficina, ENT_QUOTES, 'UTF-8') . " (" . $dependencia_id . ")" ?></small>

                <?php endif; ?>
            </h5>

            <form action="" method="POST" style="margin:0 0 0 auto;">
                <button type="submit" name="cerrar_seccion" class="btn btn-outline-danger btn-sm" style="padding:.25rem .5rem;">Cerrar sesión</button>
            </form>

            <form action="tcarpeta.php" method="POST" style="margin:0;">
                <input type="hidden" name="caja" value="<?= e((string)$caja) ?>">
                <input type="hidden" name="carpeta" value="<?= e((string)$carpeta) ?>">
                <input type="hidden" name="folios" value="<?= e((string)$ultimaPagina) ?>">
                <input type="hidden" name="oficina_id" value="<?= e((string)$oficina) ?>">
                <input type="hidden" name="dependencia_id" value="<?= e((string)$dependencia_id) ?>">
                <input type="hidden" name="id_carpeta" value="<?= e((string)$id_carpeta) ?>">
                <button type="submit" class="btn btn-primary btn-sm" style="padding:.25rem .5rem;">Terminar Carpeta</button>
            </form>

            <a href="../index.php" class="btn btn-primary btn-sm" style="padding:.25rem .5rem;">Inicio</a>

        </div>

        <table class="table table-bordered table-sm mt-2 mb-0" id="capitulosTable">
            <thead class="thead-light">
                <tr>
                    <th style="width:36px;"></th>
                    <th>Descripción</th>
                    <th style="width:100px;">Inicio</th>
                    <th style="width:100px;">Final</th>
                    <th style="width:120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($capitulos)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No hay folios registrados. Folio inicial: 1</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($capitulos as $c): ?>
                        <?php $numPaginas = $c['NoFolioFin'] - $c['NoFolioInicio'] + 1; ?>
                        <tr data-id="<?= e((string)$c['id2']) ?>"
                            data-num-paginas="<?= e((string)$numPaginas) ?>">
                            <td class="drag-column text-center"><span class="drag-icon">&#x21D5;</span></td>
                            <td contenteditable="true" class="editable"><?= e($c['DescripcionUnidadDocumental']) ?></td>
                            <td><?= e((string)$c['NoFolioInicio']) ?></td>
                            <td><?= e((string)$c['NoFolioFin']) ?></td>
                            <td><button class="btn btn-dark btn-sm editar" type="button">Editar</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Etiquetas (Series) -->
        <div class="etiquetas mb-1" style="font-size:.85rem;">
            <div class="etiquetas-container d-flex flex-wrap align-items-center" style="gap:6px;">
                <?php if (!empty($etiquetas)): ?>
                    <?php foreach ($etiquetas as $et): ?>
                        <?php $id = 'etiqueta-' . preg_replace('/[^a-z0-9_-]/i', '_', $et); ?>
                        <div class="form-check form-check-inline" style="margin-bottom:1px;">
                            <input class="form-check-input" type="radio" name="etiqueta" value="<?= e($et) ?>" id="<?= e($id) ?>" style="width:.95rem; height:.95rem;">
                            <label class="form-check-label" for="<?= e($id) ?>" style="margin-left:3px;"><?= e(ucfirst($et)) ?></label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="text-muted">No hay series configuradas.</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulario agregar capítulo -->
        <form id="capituloForm" autocomplete="off">
            <div class="form-group">
                <div class="input-group mb-2">
                    <input type="checkbox" id="checkInsertar" class="mr-1">
                    <input type="text" id="textoInput" class="form-control" placeholder="" aria-label="Texto para insertar">
                    <div class="input-group-append ">
                        <button class="btn btn-primary " type="button" onclick="agregarAlTextarea()">+</button>
                    </div>
                </div>
                <textarea id="titulo" class="form-control form-control-sm" placeholder="Asunto" required style="font-size:1.1rem; height:150px;"></textarea>
                <div class="text-right"><small id="charCounter" class="text-muted">0 / 170</small></div>
            </div>

            <div class="form-row align-items-center mb-2">
                <button type="button" id="grabarBoton" class="btn btn-warning btn-sm">Grabar (F2-F9)</button>
                <p id="ultimaPagina" class="ml-2 mb-0">Último folio: <?= e((string)$proximaPagina) ?></p>
                <div class="col-auto">
                    <input type="number" id="paginaFinal" class="form-control" placeholder="Página de Finalización" value="<?= e((string)$proximaPagina) ?>" min="<?= e((string)$proximaPagina) ?>" required style="font-size:1.1rem;">

                </div>
                <button type="submit" class="btn btn-primary btn-sm ml-2">Agregar (ENTER)</button>
            </div>
        </form>

        <div style="height:100px;"></div>
    </div>

    <!-- Modal editar -->
    <div class="modal fade" id="editarCapituloModal" tabindex="-1" role="dialog" aria-labelledby="modalEditarLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarLabel">Editar Detalle</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="editId">
                    <div class="form-group">
                        <label for="editSerie">Serie</label>
                        <select class="form-control" id="editSerie"></select>
                    </div>
                    <div class="form-group">
                        <label for="editTitulo">Título</label>
                        <textarea class="form-control" id="editTitulo" rows="6" style="font-size:1.1rem; resize:vertical;"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Descartar</button>
                    <button type="button" class="btn btn-primary" id="guardarCambios">Guardar (ENTER)</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Librerías -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script>
        // Variables globales seguras
        window.caja = <?= (int)$caja ?>;
        window.carpeta = <?= (int)$carpeta ?>;
        window.series = <?= json_encode($etiquetas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>

    <!-- Tus scripts -->
    <script src="js/inicializar.js" defer></script>
    <script src="js/validarCapitulo.js" defer></script>
    <script src="js/capituloForm.js"></script>
    <script src="js/reemplazos.js"></script>
    <script src="js/tablaCapitulos.js"></script>
    <script src="js/textotareainsert.js"></script>
    <script src="js/interceptarPlusTecla.js"></script>
    <script src="js/voice.js"></script>
    <script src="js/navegacionInputs.js"></script>
    <script src="js/editcapitulo.js"></script>
    <script src="js/centrarpantalla.js"></script>

    <!-- Nuevo: radios con label en negrita -->
    <script src="js/ratiolabel.js?v=<?= time() ?>"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof activarNavegacionConEnter === 'function') {
                activarNavegacionConEnter('titulo', 'paginaFinal');
            }
        });

        window.caja = <?= (int)$caja ?>;
        window.carpeta = <?= (int)$carpeta ?>;
        window.dependencia_id = <?= (int)$dependencia_id ?>;
        window.id_carpeta = <?= (int)$_SESSION['id_carpeta'] ?>; // <-- nueva variable
    </script>

</body>

</html>