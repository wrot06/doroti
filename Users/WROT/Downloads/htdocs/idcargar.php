<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();
require_once __DIR__ . "/rene/conexion3.php";

ini_set('display_errors', '1');
error_reporting(E_ALL);


function flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['message' => $msg, 'type' => $type];
}
function redirect(string $url): void {
    header("Location: {$url}");
    exit();
}


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_GET,  'id', FILTER_VALIDATE_INT);
if (!$id) {
    flash("ID inválido.", 'error');
    redirect('subido.php');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_pdf'])) {
    // Validar CSRF
    $tokenPost = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $tokenPost)) {
        flash("Token de seguridad inválido.", 'error');
        redirect("idcargar.php?id={$id}");
    }

    // Verificar existencia del registro
    $stmt = $conec->prepare("SELECT 1 FROM IndiceDocumental WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        flash("No existe documento con ID {$id}.", 'error');
        redirect('subido.php');
    }
    $stmt->close();

    // Validar serie
    $serie = filter_input(INPUT_POST, 'etiqueta', FILTER_SANITIZE_STRING);
    if (!$serie) {
        flash("Debes seleccionar una serie.", 'error');
        redirect("idcargar.php?id={$id}");
    }

    // ... sigue la lógica del archivo PDF y la base de datos



    // 4.4) Validar archivo PDF
    if (empty($_FILES['archivo_pdf']) || $_FILES['archivo_pdf']['error'] !== UPLOAD_ERR_OK) {
        flash("Error al cargar el archivo.", 'error');
        redirect("idcargar.php?id={$id}");
    }
    // MIME
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['archivo_pdf']['tmp_name']);
    if ($mime !== 'application/pdf') {
        flash("El archivo debe ser un PDF.", 'error');
        redirect("idcargar.php?id={$id}");
    }
    // Tamaño máximo 5MB
    if ($_FILES['archivo_pdf']['size'] > 5 * 1024 * 1024) {
        flash("El PDF supera 5 MB.", 'error');
        redirect("idcargar.php?id={$id}");
    }

    // 4.5) Leer contenido y comprobar
    $pdfData = file_get_contents($_FILES['archivo_pdf']['tmp_name']);
    if ($pdfData === false) {
        flash("No se pudo leer el archivo PDF.", 'error');
        redirect("idcargar.php?id={$id}");
    }
    // Log de debug (opcional)
    file_put_contents(__DIR__ . "/debug_upload.log", date('[Y-m-d H:i:s] ') . 
        "ID {$id}: PDF leído " . strlen($pdfData) . " bytes\n", FILE_APPEND);

    // 4.6) Guardar en BLOB dentro de transacción
    $fecha = date('Y-m-d H:i:s');
    $conec->begin_transaction();
    $upd = $conec->prepare("
        UPDATE IndiceDocumental
        SET archivo_pdf = ?, serie = ?, cargaFecha = ?
        WHERE id = ?
    ");
    // Para BLOB usamos send_long_data
    $null = null;
    $upd->bind_param("bssi", $null, $serie, $fecha, $id);
    $upd->send_long_data(0, $pdfData);

    if ($upd->execute()) {
        $conec->commit();
        flash("PDF subido y serie actualizada correctamente.", 'success');
    } else {
        $conec->rollback();
        // Log del error SQL
        file_put_contents(__DIR__ . "/debug_upload.log", date('[Y-m-d H:i:s] ') . 
            "ID {$id}: Error SQL: " . $upd->error . "\n", FILE_APPEND);
        flash("Error al guardar: " . $upd->error, 'error');
    }
    $upd->close();
    redirect("subido.php?id={$id}");
}


$stmt = $conec->prepare("SELECT * FROM IndiceDocumental WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result   = $stmt->get_result();
$registro = $result->fetch_assoc();
$stmt->close();
if (!$registro) {
    flash("Registro con ID {$id} no encontrado.", 'error');
    redirect('subido.php');
}

// Cargar series
$series = [];
$res = $conec->query("SELECT nombre FROM Serie ORDER BY nombre ASC");
while ($row = $res->fetch_assoc()) {
    $series[] = $row['nombre'];
}
$res->free();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir PDF (ID <?=htmlspecialchars($id)?>)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">

    <?php if (!empty($_SESSION['flash'])):
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
    ?>
        <div class="alert alert-<?= $f['type'] === 'success' ? 'success' : 'danger' ?>">
            <?= htmlspecialchars($f['message']) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="card-title mb-4">Subir PDF para registro ID <?=htmlspecialchars($id)?></h4>

        <table class="table table-bordered mb-4">
            <tr><th>ID</th><td><?=htmlspecialchars($registro['id'])?></td></tr>
            <tr><th>Descripción</th><td><?=htmlspecialchars($registro['DescripcionUnidadDocumental'])?></td></tr>
            <tr><th>Folio Inicio</th><td><?=htmlspecialchars($registro['NoFolioInicio'])?></td></tr>
            <tr><th>Folio Fin</th><td><?=htmlspecialchars($registro['NoFolioFin'])?></td></tr>
            <tr><th>Soporte</th><td><?=htmlspecialchars($registro['Soporte'])?></td></tr>
        </table>

        <form action="idcargar.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id"         value="<?=htmlspecialchars($id)?>">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>">

            <div class="mb-3">
                <label for="serie" class="form-label">Serie:</label>
                <select id="serie" name="etiqueta" class="form-select" required>
                    <option value="" disabled selected>Seleccione una Serie</option>
                    <?php foreach ($series as $s): ?>
                        <option value="<?=htmlspecialchars($s)?>"><?=htmlspecialchars(ucfirst(strtolower($s)))?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="archivo_pdf" class="form-label">Selecciona PDF (máx. 5 MB):</label>
                <input type="file" name="archivo_pdf" id="archivo_pdf"
                       class="form-control" accept="application/pdf" required>
            </div>

            <button type="submit" class="btn btn-primary">📤 Subir PDF</button>
            <a href="documents.php" class="btn btn-secondary ms-2">← Volver</a>
        </form>
      </div>
    </div>

</body>
</html>
