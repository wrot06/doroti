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
<nav class="navbar fixed-top" style="background-color: #e3f2fd;" data-bs-theme="light">
<div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="../index.php">
            <img src="../img/Doroti Logo Horizontal.png" alt="Logo Doroti" height="30">
        </a>

        <!-- Usuario + Oficina -->
        <div class="ms-3 d-flex align-items-center bg-light px-3 py-1 rounded-pill shadow-sm">
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

<div class="d-flex align-items-center gap-3 ms-auto">


<input type="search" id="buscarInput" class="form-control form-control-sm" placeholder="Buscar por serie o título" onkeyup="filtrarDocumentos()" style="width:200px">
<a href="digital.php" class="btn btn-success btn-sm"><i class="bi bi-upload me-1"></i>Subir</a>

    <form method="POST">
        <button type="submit" name="cerrar_seccion" class="btn btn-danger btn-sm"><i class="bi bi-box-arrow-right me-2"></i>Salir</button>
    </form>

</div>
</div>
</nav>



<!-- CONTENIDO -->
<main class="container py-4">

<?php if($result->num_rows>0): ?>
<ul class="list-group" id="listaDocumentos">
<?php while($doc=$result->fetch_assoc()): ?>
<li class="list-group-item d-flex justify-content-between align-items-start list-item"
data-serie="<?=h(strtolower($doc['tipo']))?>"
data-titulo="<?=h(strtolower($doc['titulo_documento']))?>">

<div class="me-auto">
<div>
<span class="fw-bold"><?=h($doc['tipo'])?>:</span>
<span class="text-secondary"><?=h($doc['titulo_documento'])?></span>
</div>
<small class="text-muted">
<strong>Creación:</strong> <?=h($doc['fecha_creacion'])?> |
<strong>Subido:</strong> <?=h($doc['fecha_subida'])?>
</small>
</div>

<a href="viewer.php?id=<?= (int)$doc['id'] ?>" target="_blank"
class="btn btn-primary btn-sm">
<i class="bi bi-file-earmark-pdf-fill me-1" ></i>Nueva Versión
</a>

<a href="download.php?id=<?= (int)$doc['id'] ?>" target="_blank"
class="btn btn-primary btn-sm" style="margin-left: 5px;">
<i class="bi bi-file-earmark-pdf-fill me-1"></i>Ver PDF
</a>

</li>
<?php endwhile; ?>
</ul>
<?php else: ?>
<div class="alert alert-info">No has subido ningún documento.</div>
<?php endif; ?>

</main>

<script>
function filtrarDocumentos(){
const f=document.getElementById('buscarInput').value.toLowerCase();
document.querySelectorAll('.list-item').forEach(i=>{
i.classList.toggle('hidden',
!i.dataset.serie.includes(f)&&!i.dataset.titulo.includes(f));
});
}
</script>

</body>
</html>
