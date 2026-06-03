<?php
require_once __DIR__ . '/../rene/conexion3.php';
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../error.log');
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
        SELECT c.Caja, c.Carpeta, i.DescripcionUnidadDocumental, i.NoFolioInicio, i.NoFolioFin
        FROM indice_documental i
        INNER JOIN carpetas c ON c.id = i.carpeta_id
        WHERE i.DescripcionUnidadDocumental LIKE ?
        ORDER BY c.Caja DESC, c.Carpeta ASC
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
    body{padding-top:70px}
    .hidden{display:none!important}
    </style>
</head>
<body>

<?php
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}
$basePath = '../';
$activePage = 'buscador';
require_once "../components/navbar.php";
?>

<div class="container my-4" id="contenedor-padre">
    <!-- Formulario de búsqueda principal en el cuerpo -->
    <div class="card shadow-sm border border-light mb-4">
        <div class="card-body p-4 bg-white rounded">
            <h4 class="mb-3 fw-bold text-dark"><i class="bi bi-search text-primary me-2"></i>Buscador de Índices Documentales</h4>
            <form method="GET" action="buscador.php" class="m-0" id="form-busqueda">
                <div class="input-group">
                    <input class="form-control form-control-lg" type="text" name="search" placeholder="Escribe términos de búsqueda (ej. factura, acta, etc.)..." value="<?= htmlspecialchars($search) ?>" aria-label="Search" required>
                    <button class="btn btn-primary btn-lg d-flex align-items-center px-4" type="submit">
                        <i class="bi bi-search me-2"></i>Buscar
                    </button>
                    <button class="btn btn-outline-secondary btn-lg d-flex align-items-center" type="button" id="btn-limpiar">
                        <i class="bi bi-eraser me-2"></i>Limpiar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="contenedor">

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
