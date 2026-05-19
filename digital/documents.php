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
SELECT id,tipo,titulo_documento,fecha_creacion,fecha_subida
FROM documentos
WHERE user_id=? AND estado='activo'
ORDER BY fecha_subida DESC
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
<nav class="navbar navbar-expand-lg fixed-top shadow-sm" style="background-color: #e3f2fd;" data-bs-theme="light">
<div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="../index.php">
            <img src="../img/Doroti Logo Horizontal.png" alt="Logo Doroti" height="30">
        </a>

        <!-- Botón Hamburguesa -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarCollapse">
            <!-- Usuario + Oficina -->
            <div class="d-flex align-items-center bg-light px-3 py-1 rounded-pill shadow-sm me-auto mt-2 mt-lg-0 mb-2 mb-lg-0" style="width: fit-content;">
                <img src="<?= h($userAvatar) ?>" 
                     class="rounded-circle me-2" 
                     width="32" 
                     height="32" 
                     style="object-fit: cover; border: 2px solid #0d6efd;"
                     alt="Avatar de <?= h($usuario) ?>">
                <div class="d-flex flex-column lh-sm">
                    <span class="fw-semibold"><?= h($usuario) ?></span>
                    <small class="text-muted"><?= h($oficina) ?></small>
                </div>
            </div>

            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 gap-lg-3 ms-auto">
                <input type="search" id="buscarInput" class="form-control form-control-sm w-100" placeholder="Buscar por serie o título" onkeyup="filtrarDocumentos()" style="max-width:200px">
                <a href="digital.php" class="btn btn-success btn-sm w-100 text-start text-lg-center"><i class="bi bi-upload me-1"></i>Subir</a>

                <form method="POST" class="m-0 w-100">
                    <button type="submit" name="cerrar_seccion" class="btn btn-danger btn-sm w-100 text-start text-lg-center"><i class="bi bi-box-arrow-right me-2"></i>Salir</button>
                </form>
            </div>
        </div>
</div>
</nav>



<!-- CONTENIDO -->
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
<tr class="list-item" data-serie="<?=h(strtolower($doc['tipo']))?>" data-titulo="<?=h(strtolower($doc['titulo_documento']))?>">
    <td class="fw-bold text-primary"><?=h($doc['tipo'])?></td>
    <td class="text-dark fw-medium"><?=h($doc['titulo_documento'])?></td>
    <td>
        <ul class="list-unstyled mb-0" style="font-size: 0.75rem;">
            <li><i class="bi bi-calendar-event me-1"></i><span class="text-muted">C:</span> <?=h($doc['fecha_creacion'])?></li>
            <li><i class="bi bi-cloud-upload me-1"></i><span class="text-muted">S:</span> <?= date('Y-m-d', strtotime($doc['fecha_subida'])) ?></li>
        </ul>
    </td>
    <td>
        <div class="d-flex align-items-center justify-content-center flex-wrap gap-1">
            <a href="viewer.php?id=<?= (int)$doc['id'] ?>" target="_blank" class="btn btn-outline-primary btn-sm py-0 px-1" title="Nueva Versión">
                <i class="bi bi-file-earmark-plus"></i>
            </a>
            <a href="download.php?id=<?= (int)$doc['id'] ?>" target="_blank" class="btn btn-outline-info btn-sm text-dark py-0 px-1" title="Ver PDF">
                <i class="bi bi-eye"></i>
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
