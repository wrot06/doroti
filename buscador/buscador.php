<?php
ob_start();
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
AuthMiddleware::initSession();
require_once __DIR__ . '/../rene/conexion3.php';
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../error.log');
error_reporting(E_ALL);

if(!isset($_SESSION['authenticated'])||$_SESSION['authenticated']!==true){
    header('Location: ../login/login.php');
    exit();
}

// Manejar cierre de sesión
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['cerrar_seccion'])){
    if (isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        session_destroy();
        header('Location: ../login/login.php');
        exit();
    }
}


$oficina  = $_SESSION['oficina']  ?? null;
$dependencia_id  = $_SESSION['dependencia_id']  ?? null;

$search='';
$tipo_documental='';
$resultados=[];

// Obtener todos los tipos documentales para el filtro
$tipos_documentales=[];
$res_tipos=$conec->query("SELECT DISTINCT nombre FROM tipo_documental ORDER BY nombre ASC");
if($res_tipos){
    while($row_t=$res_tipos->fetch_assoc()){
        $tipos_documentales[]=$row_t['nombre'];
    }
}

if((isset($_GET['search'])&&trim($_GET['search'])!=='')||(isset($_GET['tipo_documental'])&&trim($_GET['tipo_documental'])!=='')){
    $search=isset($_GET['search'])?trim($_GET['search']):'';
    $tipo_documental=isset($_GET['tipo_documental'])?trim($_GET['tipo_documental']):'';
    
    $unionQuery = getIndiceUnionQuery($conec, ["carpeta_id", "serie", "DescripcionUnidadDocumental", "NoFolioInicio", "NoFolioFin"]);
    
    $conditions = [];
    $params = [];
    $types = "";
    
    if($search!==''){
        $conditions[]="i.DescripcionUnidadDocumental LIKE ?";
        $params[]="%$search%";
        $types.="s";
    }
    
    if($tipo_documental!==''){
        $conditions[]="i.serie = ?";
        $params[]=$tipo_documental;
        $types.="s";
    }
    
    $whereClause=implode(" AND ",$conditions);
    
    $stmt=$conec->prepare("
        SELECT c.Caja, c.Carpeta, i.DescripcionUnidadDocumental, i.NoFolioInicio, i.NoFolioFin, i.serie
        FROM $unionQuery i
        INNER JOIN carpetas c ON c.id = i.carpeta_id
        WHERE $whereClause
        ORDER BY c.Caja DESC, c.Carpeta ASC
        LIMIT 250
    ");
    if($stmt){
        if(!empty($params)){
            $stmt->bind_param($types,...$params);
        }
        $stmt->execute();
        $resultados=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}


/* ================== SESIÓN ================== */
require_once "../services/UserService.php";
$userService = new UserService($conec);
$user_id=(int)($_SESSION['user_id']??0);
$userInfo = $userService->getUserInfo($user_id);
$usuario = $userInfo['username'];
$oficina = $userInfo['oficina'];
$userAvatar = '../' . $userService->getUserAvatar($user_id);

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
                <div class="row g-3">
                    <div class="col-md-7">
                        <label for="search" class="form-label fw-semibold text-secondary">Término de búsqueda</label>
                        <input class="form-control form-control-lg" type="text" id="search" name="search" placeholder="Escribe términos (ej. factura, acta, etc.)..." value="<?= htmlspecialchars($search) ?>" aria-label="Search">
                    </div>
                    <div class="col-md-5">
                        <label for="tipo_documental" class="form-label fw-semibold text-secondary">Tipo Documental</label>
                        <select class="form-select form-select-lg" id="tipo_documental" name="tipo_documental" aria-label="Tipo Documental">
                            <option value="">Todos los tipos documentales</option>
                            <?php foreach ($tipos_documentales as $tipo): ?>
                                <option value="<?= htmlspecialchars($tipo) ?>" <?= $tipo_documental === $tipo ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tipo) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                        <button class="btn btn-outline-secondary btn-lg d-flex align-items-center" type="button" id="btn-limpiar">
                            <i class="bi bi-eraser me-2"></i>Limpiar
                        </button>
                        <button class="btn btn-primary btn-lg d-flex align-items-center px-4" type="submit">
                            <i class="bi bi-search me-2"></i>Buscar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div id="contenedor">

<?php if(empty($resultados)&&$search===''&&$tipo_documental===''): ?>
<div class="alert alert-info" id="mensaje-inicio">
Para empezar la búsqueda, escribe una palabra o selecciona un tipo documental y haz clic en "<b>Buscar</b>"
</div>

<?php elseif(!empty($resultados)): ?>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle">
<thead class="table-dark">
<tr>
<th>Descripción</th>
<th>Tipo Documental</th>
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
<td>
    <?php if(!empty($fila['serie'])): ?>
        <span class="badge bg-secondary"><?=htmlspecialchars($fila['serie'])?></span>
    <?php else: ?>
        <span class="text-muted">—</span>
    <?php endif; ?>
</td>
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
No se encontraron resultados para 
<?php 
$criteria = [];
if ($search !== '') $criteria[] = "el término '<b>" . htmlspecialchars($search) . "</b>'";
if ($tipo_documental !== '') $criteria[] = "el tipo documental '<b>" . htmlspecialchars($tipo_documental) . "</b>'";
echo implode(" y ", $criteria);
?>.
</div>
<?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('btn-limpiar').addEventListener('click',()=>{
    document.getElementById('search').value='';
    const selectTipo = document.getElementById('tipo_documental');
    if (selectTipo) {
        selectTipo.selectedIndex = 0;
    }
    document.getElementById('contenedor').innerHTML=
    `<div class="alert alert-info" id="mensaje-inicio">
    Para empezar la búsqueda, escribe una palabra o selecciona un tipo documental y haz clic en "<b>Buscar</b>"
    </div>`;
});

document.getElementById('form-busqueda').addEventListener('submit',(e)=>{
    const searchVal = document.getElementById('search').value.trim();
    const selectTipo = document.getElementById('tipo_documental');
    const tipoVal = selectTipo ? selectTipo.value : '';
    
    if (searchVal === '' && tipoVal === '') {
        e.preventDefault();
        alert('Por favor, ingresa un término de búsqueda o selecciona un tipo documental.');
    }
});
</script>

</body>
</html>
<?php ob_end_flush(); ?>
