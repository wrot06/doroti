<?php
session_start();
require "../rene/conexion3.php";

/* =========================
   VALIDACIÓN DE ENTRADA
========================= */
$caja           = intval($_POST['caja'] ?? 0);
$carpeta        = intval($_POST['carpeta'] ?? 0);
$id_carpeta     = intval($_POST['id_carpeta'] ?? 0);
$dependencia_id = intval($_POST['dependencia_id'] ?? 0);

if ($caja <= 0 || $carpeta <= 0 || $id_carpeta <= 0 || $dependencia_id <= 0) {
    die("Error: Datos de carpeta u oficina no válidos.");
}

// Redirigir si no está autenticado
if (empty($_SESSION['authenticated'])) {
    header('Location: login/login.php');
    exit();
}

/* =========================
   VALIDAR QUE LA CARPETA EXISTA
========================= */
$stmt = $conec->prepare("
    SELECT id
    FROM Carpetas
    WHERE id = ? AND Caja = ? AND Carpeta = ? AND dependencia_id = ?
    LIMIT 1
");
$stmt->bind_param("iiii", $id_carpeta, $caja, $carpeta, $dependencia_id);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    die("Error: La carpeta no existe o no pertenece a esta dependencia.");
}
$stmt->close();

/* =========================
   OBTENER ÚLTIMO FOLIO
========================= */
$ultimaPagina = 0;
$stmt = $conec->prepare("
    SELECT MAX(NoFolioFin) AS ultima_pagina
    FROM IndiceTemp
    WHERE Caja = ? AND Carpeta = ? AND dependencia_id = ?
");
$stmt->bind_param("iii", $caja, $carpeta, $dependencia_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $ultimaPagina = (int)($row['ultima_pagina'] ?? 0);
}
$stmt->close();

/* =========================
   OBTENER NOMBRE OFICINA
========================= */
$oficinaNombre = '';
$stmt = $conec->prepare("
    SELECT nombre
    FROM dependencias
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $dependencia_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $oficinaNombre = $row['nombre'];
}
$stmt->close();

/* =========================
   OBTENER SERIES
========================= */
$series = [];
$stmt = $conec->prepare("
    SELECT s.id, s.nombre
    FROM Serie s
    INNER JOIN OficinaSerie os ON os.serie_id = s.id
    WHERE os.dependencia_id = ?
    ORDER BY s.nombre ASC
");
$stmt->bind_param("i", $dependencia_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $series[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Finalizar Carpeta</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="icon" href="../img/hueso.png">

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="css/estiloindice.css">
<link rel="stylesheet" href="css/botongrabar.css">
</head>

<body>

<div class="container mt-4">

<div class="card">

<div class="card-header d-flex justify-content-between align-items-center">
    <div>
        <strong>Caja <?= $caja ?></strong> |
        <strong>Carpeta <?= $carpeta ?></strong><br>
        <small class="text-muted"><?= htmlspecialchars($oficinaNombre) ?></small>
    </div>

    <div class="d-flex align-items-center gap-2">
        <span class="badge badge-dark mr-2">ID <?= $id_carpeta ?></span>

        <form action="indice.php" method="post" class="m-0">
            <input type="hidden" name="consulta" value="<?= $caja ?>">
            <input type="hidden" name="carpeta" value="<?= $carpeta ?>">
            <button class="btn btn-outline-primary btn-sm">Regresar</button>
        </form>
    </div>
</div>

<div class="card-body">

<form action="finalizarcarpeta.php" method="post">

<input type="hidden" name="caja" value="<?= $caja ?>">
<input type="hidden" name="carpeta" value="<?= $carpeta ?>">
<input type="hidden" name="id_carpeta" value="<?= $id_carpeta ?>">
<input type="hidden" name="dependencia_id" id="dependencia_id" value="<?= $dependencia_id ?>">
<input type="hidden" name="folios" value="<?= $ultimaPagina ?>">

<div class="form-group">
    <label>Serie</label>
    <select id="serie" name="serie" class="form-control" required>
        <option value="" disabled selected>Seleccione una Serie</option>
        <?php foreach ($series as $serie): ?>
            <option value="<?= $serie['id'] ?>">
                <?= htmlspecialchars(mb_strtoupper($serie['nombre'], 'UTF-8')) ?> (<?= htmlspecialchars(mb_strtoupper($serie['id'], 'UTF-8')) ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label>Subserie</label>
    <select id="subserie" name="subserie" class="form-control">
        <option value="">Seleccione una Subserie</option>
    </select>
</div>

<div class="form-group">
    <label>Título de Carpeta</label>
<textarea id="tituloCarpeta" name="tituloCarpeta" class="form-control" maxlength="56" rows="3" required></textarea>

<button type="button" id="grabarBoton" class="btn btn-warning mt-1 d-block">Grabar (F2–F9)</button>
       
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Fecha Inicial</label>
            <input type="date" id="fechaInicial" name="fechaInicial" class="form-control" required>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label>Fecha Final</label>
            <input type="date" id="fechaFinal" name="fechaFinal" class="form-control" required>
        </div>
    </div>
</div>


<div class="alert alert-info">
    Folios actuales: <strong><?= $ultimaPagina ?></strong>
</div>

<?php if ($ultimaPagina < 1): ?>
    <div class="alert alert-warning">No se puede finalizar la carpeta sin capítulos.</div>
    <button class="btn btn-secondary btn-block" disabled>Finalizar Carpeta</button>
<?php else: ?>
    <button class="btn btn-success btn-block btn-lg">Finalizar Carpeta</button>
<?php endif; ?>

</form>

</div>
</div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/voz.js"></script>
<script src="js/subseries.js"></script>
<script src="js/validacionesFecha.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const dependenciaId = document.getElementById('dependencia_id').value;
    configurarSubseries('serie', 'subserie', dependenciaId);

    // Voz
    iniciarVoz('tituloCarpeta', 'grabarBoton');

    // ✅ Validación de fechas
    configurarFechas('fechaInicial', 'fechaFinal');
});
</script>



</body>
</html>
