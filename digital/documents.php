<?php
declare(strict_types=1);
session_start();
require_once "../rene/conexion3.php";

/* ================== SEGURIDAD ================== */
ini_set('display_errors','0');
error_reporting(E_ALL);
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

/* ================== AUTH ================== */
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(401);exit('No autorizado');
}
$user_id=(int)($_SESSION['user_id']??0);
if($user_id<=0){http_response_code(401);exit('Sesión inválida');}

/* ================== LOGOUT ================== */
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['cerrar_seccion'])){
    session_destroy();
    header("Location: ../login/login.php");exit;
}

/* ================== CSRF ================== */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ================== PAGINACIÓN ================== */
$limit=20;
$page=max(1,(int)($_GET['page']??1));
$offset=($page-1)*$limit;

/* ================== CONSULTA ================== */
$sql="
SELECT d.id, d.tipo, td.nombre AS tipo_nombre, d.titulo_documento, d.fecha_creacion, d.fecha_subida
FROM documentos d
LEFT JOIN tipo_documental td ON d.tipo = td.id
WHERE d.user_id = ? AND d.estado = 'activo'
ORDER BY d.fecha_subida DESC
LIMIT ? OFFSET ?
";
$stmt=$conec->prepare($sql);
$stmt->bind_param("iii",$user_id,$limit,$offset);
$stmt->execute();
$result=$stmt->get_result();

/* ================== SESIÓN ================== */
$usuario=$_SESSION['username']??'Usuario';
$oficina=$_SESSION['oficina']??'';

/* ================== AVATAR ================== */
$userAvatar='../uploads/avatars/default.png';
if($user_id>0){
    $stmt_av=$conec->prepare("SELECT avatar FROM users WHERE id=?");
    $stmt_av->bind_param("i",$user_id);
    $stmt_av->execute();
    $res_av=$stmt_av->get_result();
    if($row_av=$res_av->fetch_assoc()){
        if($row_av['avatar'] && file_exists('../uploads/avatars/'.basename($row_av['avatar']))){
            $userAvatar='../uploads/avatars/'.basename($row_av['avatar']);
        }
    }
    $stmt_av->close();
}

function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/png" href="../img/hueso.png">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mis Documentos</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body{padding-top:80px}
.hidden{display:none!important}
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

<!-- CONTENIDO -->
<main class="container py-4">
    <!-- Barra de acciones local (Buscador y Subir) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 bg-white p-3 rounded shadow-sm border border-light">
        <div class="d-flex align-items-center gap-2 w-100 w-md-auto" style="max-width:300px;">
            <i class="bi bi-search text-muted"></i>
            <input type="search" id="buscarInput" class="form-control form-control-sm" placeholder="Buscar por serie o título..." onkeyup="filtrarDocumentos()">
        </div>
        <a href="digital.php" class="btn btn-success btn-sm"><i class="bi bi-upload me-2"></i>Subir Documento</a>
    </div>

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

<?php if($result->num_rows>0): ?>
<div class="table-responsive">
<table class="table table-sm table-bordered table-hover align-middle shadow-sm" style="font-size: 0.85rem;" id="listaDocumentos">
    <thead class="table-light">
        <tr>
            <th style="width: 15%;">Tipo Documental</th>
            <th style="width: 55%;">Título del Documento</th>
            <th style="width: 15%;">Fechas</th>
            <th style="width: 15%;" class="text-center">Acciones</th>
        </tr>
    </thead>
    <tbody>
<?php while($doc=$result->fetch_assoc()): ?>
<tr class="list-item" data-serie="<?=h(strtolower($doc['tipo_nombre'] ?? $doc['tipo']))?>" data-titulo="<?=h(strtolower($doc['titulo_documento']))?>">
    <td class="fw-bold text-primary"><?=h($doc['tipo_nombre'] ?? $doc['tipo'])?></td>
    <td class="text-dark fw-medium"><?=h($doc['titulo_documento'])?></td>
    <td>
        <ul class="list-unstyled mb-0" style="font-size: 0.75rem;">
            <li><i class="bi bi-calendar-event me-1"></i><span class="text-muted">C:</span> <?=h($doc['fecha_creacion'])?></li>
            <li><i class="bi bi-cloud-upload me-1"></i><span class="text-muted">S:</span> <?= date('Y-m-d', strtotime($doc['fecha_subida'])) ?></li>
        </ul>
    </td>
    <td>
        <div class="d-flex align-items-center justify-content-center flex-wrap gap-1">
            <a href="viewer.php?id=<?= (int)$doc['id'] ?>" class="btn btn-outline-primary btn-sm py-0 px-1" title="Ver en Libro Animado">
                <i class="bi bi-book-half"></i>
            </a>
            <a href="versions.php?id=<?= (int)$doc['id'] ?>" class="btn btn-outline-info btn-sm text-dark py-0 px-1" title="Historial de Versiones">
                <i class="bi bi-clock-history"></i>
            </a>
            <a href="download.php?id=<?= (int)$doc['id'] ?>&download=1" class="btn btn-outline-success btn-sm py-0 px-1" title="Descargar PDF">
                <i class="bi bi-download"></i>
            </a>
            <form method="POST" action="delete_document.php" class="m-0" onsubmit="return confirm('¿Está seguro de que desea eliminar este documento?');">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="documento_id" value="<?= (int)$doc['id'] ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-1" title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </td>
</tr>
<?php endwhile; ?>
    </tbody>
</table>
</div>
<?php else: ?>
<div class="alert alert-info">No has subido ningún documento.</div>
<?php endif; ?>

</main>

<script>
function filtrarDocumentos(){
const f=document.getElementById('buscarInput').value.toLowerCase();
document.querySelectorAll('.list-item').forEach(i=>{
    if (!i.dataset.serie.includes(f) && !i.dataset.titulo.includes(f)) {
        i.style.display = 'none';
    } else {
        i.style.display = '';
    }
});
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
