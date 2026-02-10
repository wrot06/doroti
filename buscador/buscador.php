<?php
require "../rene/conexion3.php";
ini_set('display_errors',1);
error_reporting(E_ALL);

session_start();
if(!isset($_SESSION['authenticated'])||$_SESSION['authenticated']!==true){
    header('Location: ../login/login.php');
    exit();
}

// Manejar cierre de sesión
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['cerrar_seccion'])){
    session_destroy();
    header('Location: ../login/login.php');
    exit();
}


$oficina  = $_SESSION['oficina']  ?? null;
$dependencia_id  = $_SESSION['dependencia_id']  ?? null;

$search='';
$resultados=[];

if(isset($_GET['search'])&&trim($_GET['search'])!==''){
    $search=trim($_GET['search']);
    $likeSearch="%$search%";
    $stmt=$conec->prepare("
        SELECT Caja,Carpeta,DescripcionUnidadDocumental,NoFolioInicio,NoFolioFin
        FROM IndiceDocumental
        WHERE DescripcionUnidadDocumental LIKE ?
        ORDER BY Caja DESC,Carpeta ASC
    ");
    $stmt->bind_param("s",$likeSearch);
    $stmt->execute();
    $resultados=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}


/* ================== SESIÓN ================== */
$usuario=$_SESSION['username']??'Usuario';
$oficina=$_SESSION['oficina']??'';

/* ================== AVATAR ================== */
$userAvatar='../uploads/avatars/default.png';
$user_id=(int)($_SESSION['user_id']??0);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscador de Índices</title>    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="buscador.css">
    <style>
    body{padding-top:62px}
    .hidden{display:none!important}
    </style>
</head>
<body>

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

<!-- BUSCADOR -->
<form method="GET" action="buscador.php" class="d-flex gap-2 me-3" id="form-busqueda">
<input class="form-control form-control-sm" type="text" name="search" placeholder="Buscar..."value="<?= htmlspecialchars($search) ?>">
<button class="btn btn-outline-primary btn-sm" type="submit">
<i class="bi bi-search me-2"></i>Buscar
</button>
<button class="btn btn-outline-primary btn-sm" type="button" id="btn-limpiar">
<i class="bi bi-eraser me-2"></i>limpiar
</button>
</form>

<!-- SALIR -->
<form method="POST">
<button type="submit" name="cerrar_seccion" class="btn btn-danger btn-sm">
<i class="bi bi-box-arrow-right me-1"></i>Salir
</button>
</form>

</div>
</div>
</nav>



<div class="container my-4" id="contenedor">

<?php if(empty($resultados)&&$search===''): ?>
<div class="alert alert-info" id="mensaje-inicio">
Para empezar la búsqueda, escribe una palabra y haz clic en "<b>Buscar</b>"
</div>

<?php elseif(!empty($resultados)): ?>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle">
<thead class="table-dark">
<tr>
<th>Descripción</th>
<th>Caja</th>
<th>Carpeta</th>
<th>Inicio</th>
<th>Fin</th>
</tr>
</thead>
<tbody>
<?php foreach($resultados as $fila): ?>
<tr>
<td><?=htmlspecialchars($fila['DescripcionUnidadDocumental'])?></td>
<td><?=htmlspecialchars($fila['Caja'])?></td>
<td><?=htmlspecialchars($fila['Carpeta'])?></td>
<td><?=htmlspecialchars($fila['NoFolioInicio'])?></td>
<td><?=htmlspecialchars($fila['NoFolioFin'])?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<?php else: ?>
<div class="alert alert-warning">
No se encontraron resultados para "<b><?=htmlspecialchars($search)?></b>".
</div>
<?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('btn-limpiar').addEventListener('click',()=>{
document.querySelector('input[name="search"]').value='';
document.getElementById('contenedor').innerHTML=
`<div class="alert alert-info" id="mensaje-inicio">
Para empezar la búsqueda, escribe una palabra y haz clic en <b>Buscar</b>.
</div>`;
});
</script>

</body>
</html>
