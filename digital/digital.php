<?php
declare(strict_types=1);
ob_start();
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
AuthMiddleware::initSession();
require_once "../rene/conexion3.php";

/* ================== AUTH ================== */
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: ../login/login.php"); exit;
}


if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['cerrar_seccion'])){
    if (isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        session_destroy();
        header('Location: ../login/login.php');
        exit();
    }
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    session_destroy(); header("Location: ../login/login.php"); exit;
}

/* ================== HELPERS ================== */
function h(?string $v): string {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

/* ================== DOCUMENTOS EXISTENTES ================== */
$stmt = $conec->prepare("
    SELECT id, tipo, titulo_documento, version_actual
    FROM documentos
    WHERE user_id = ? AND estado = 'activo'
    ORDER BY fecha_subida DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$docs = $stmt->get_result();

/* ================== SESIÓN ================== */
require_once "../services/UserService.php";
$userService = new UserService($conec);
$userInfo = $userService->getUserInfo($user_id);
$usuario = $userInfo['username'];
$oficina = $userInfo['oficina'];
$userAvatar = '../' . $userService->getUserAvatar($user_id);
$dependencia_id = (int)($_SESSION['dependencia_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/png" href="../img/hueso.png">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Subir Documento</title>
<link rel="stylesheet" href="css/botongrabar.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{padding-top:80px}
</style>
</head>
<body>

<!-- NAVBAR -->
<?php
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}
$basePath = '../';
$activePage = 'digital';
require_once "../components/navbar.php";
?>

<main class="container py-4">

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= h($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= h($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<form action="upload_document.php" method="POST" enctype="multipart/form-data" class="card shadow-sm">
<input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
<div class="card-body">

<h5 class="mb-3">Subir un Documento al Sistema</h5>

<!-- NUEVO DOCUMENTO -->
<!-- NUEVO DOCUMENTO -->
<div id="nuevoBox">

<div class="mb-3">
<label class="form-label">Tipo Documental</label>
<select name="tipo_documental_id" class="form-select" required>
    <option value="">Seleccione un Tipo Documental</option>
<?php
$td_stmt = $conec->prepare("SELECT id, nombre FROM tipo_documental WHERE dependencia_id = ".$dependencia_id." AND estado = 1 ORDER BY nombre ASC");
$td_stmt->execute();
$tipos = $td_stmt->get_result();
while($t = $tipos->fetch_assoc()):
?>
<option value="<?= $t['id'] ?>"><?= h($t['nombre']) ?></option>
<?php endwhile; ?>
</select>
</div>

<div class="mb-3">
<label class="form-label">Título del documento</label>
<textarea id="titulo" name="titulo_documento" class="form-control form-control-sm" placeholder="Asunto" required style="font-size:1.1rem; height:70px; margin-bottom: 3px"></textarea>
<button type="button" id="grabarBoton" class="btn btn-warning btn-sm">Grabar (F2-F9)</button>
</div>

<div class="mb-3">
<label class="form-label">Fecha del documento</label>
<input type="date" name="fecha_creacion" class="form-control" required>
</div>

</div>


<!-- NUEVA VERSION -->
<div id="versionBox" class="d-none">

<div class="mb-3">
<label class="form-label">Documento</label>
    <select name="documento_id" class="form-select">   

    <?php while($d=$docs->fetch_assoc()): ?>
    <option value="<?=$d['id']?>">
    <?=h($d['serie'])?> - <?=h($d['titulo_documento'])?> (v<?=$d['version_actual']?>)
    </option>
    <?php endwhile ?>
    </select>
</div>

</div>

<hr>

<div class="mb-3">
<label class="form-label">Archivo PDF (máx. 10 MB)</label>
<input type="file" name="pdf" class="form-control" accept="application/pdf" required>
</div>

<button class="btn btn-primary">
<i class="bi bi-upload me-1"></i>Subir documento
</button>

<a href="documents.php" class="btn btn-secondary ms-2">Cancelar</a>

</div>
</form>

</main>
<!-- Librerías -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<script src="../carpeta/js/reemplazos.js"></script>
<script src="../carpeta/js/voice.js"></script>

<script>
function toggleTipo(){
document.getElementById('nuevoBox').classList.toggle('d-none',document.getElementById('version').checked);
document.getElementById('versionBox').classList.toggle('d-none',document.getElementById('nuevo').checked);
}
</script>

</body>
</html>
<?php ob_end_flush(); ?>
