<?php
require "../rene/conexion3.php";
ini_set('display_errors',1);
error_reporting(E_ALL);
session_start();


$dependencia_id  = $_SESSION['dependencia_id']  ?? null;
/* ================== AUTENTICACIÓN ================== */
if (empty($_SESSION['authenticated'])) {
    header('Location: ../login/login.php');
    exit;
}

if (isset($_POST['cerrar_seccion'])) {
    session_destroy();
    header("Location: ../login/login.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$oficina  = $_SESSION['oficina']  ?? null;
$dependencia_id  = $_SESSION['dependencia_id']  ?? null;

/* ================== FILTROS ================== */
$anio_inicio = filter_input(INPUT_GET,'anio_inicio',FILTER_VALIDATE_INT) ?: null;
$anio_final  = filter_input(INPUT_GET,'anio_final',FILTER_VALIDATE_INT) ?: null;

/* ================== CONSULTA CARPETAS ================== */
$sql = "SELECT * FROM Carpetas WHERE Estado='C' AND dependencia_id =".$dependencia_id."";
$params = [];
$types = "";

if ($anio_inicio) {
    $sql .= " AND YEAR(FInicial) >= ?";
    $params[] = $anio_inicio;
    $types .= "i";
}
if ($anio_final) {
    $sql .= " AND YEAR(FFinal) <= ?";
    $params[] = $anio_final;
    $types .= "i";
}

$sql .= " ORDER BY Caja DESC, Carpeta ASC";

$stmt = $conec->prepare($sql);
if ($params) $stmt->bind_param($types,...$params);
$stmt->execute();
$resultado = $stmt->get_result();

/* ================== ÍNDICES DOCUMENTALES ================== */
$indices = [];
$res = $conec->query("SELECT * FROM IndiceDocumental WHERE dependencia_id =".$dependencia_id."");
while ($r = $res->fetch_assoc()) {
    $indices[$r['Caja']][$r['Carpeta']][] = $r;
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
<title>Rotulos</title>
    <link rel="stylesheet" href="estilos.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
<style>
body{padding-top:50px}
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

<form method="GET" class="d-flex gap-2">

<div class="input-group input-group-sm mb-3">
  <span class="input-group-text" id="inputGroup-sizing-sm">Año Inicial</span>
  <input type="number" name="anio_inicio" class="form-control" aria-label="Sizing example input" aria-describedby="inputGroup-sizing-sm" value="<?= htmlspecialchars($anio_inicio ?? '') ?>">
</div>

<div class="input-group input-group-sm mb-3">
  <span class="input-group-text" id="inputGroup-sizing-sm">Año Final</span>
  <input type="number" name="anio_final" class="form-control" aria-label="Sizing example input" aria-describedby="inputGroup-sizing-sm" value="<?= htmlspecialchars($anio_final ?? '') ?>">
</div>

<div class="input-group input-group-sm mb-3">
<button class="btn btn-outline-primary btn-sm"><i class="bi bi-funnel-fill me-1"></i>Filtrar</button>
<a href="buscador.php" class="btn btn-secondary btn-sm"><i class="bi bi-eraser me-1"></i>Limpiar</a>
</form>
</div>

<form method="POST">
<button class="btn btn-danger btn-sm" name="cerrar_seccion"><i class="bi bi-box-arrow-right me-1"></i>Salir</button>
</form>

</div>
</div>
</nav>



<div id="contenedor">
<table class="mi-tabla">
<thead>
<tr>
<th></th><th>Caja</th><th>Carpeta</th><th>Serie</th><th>Título</th>
<th>Fecha Inicial</th><th>Fecha Final</th><th>Folios</th><th>Rótulos</th><th>Rótulo</th>
</tr>
</thead>
<tbody>

<?php while ($f = $resultado->fetch_assoc()):
$bg = ($f['Caja'] % 2 == 0) ? "#e7f4ff" : "#fff";
$caja = (int)$f['Caja'];
$carpeta = (int)$f['Carpeta'];
?>
<tr style="background:<?= $bg ?>">
<td><button class="accordion">v</button></td>
<td><?= htmlspecialchars($caja) ?></td>
<td><?= htmlspecialchars($carpeta) ?></td>
<td>
<b><?= htmlspecialchars($f['Serie']) ?></b>
<?php if ($f['Subs']): ?><br><small><?= htmlspecialchars($f['Subs']) ?></small><?php endif; ?>
</td>
<td><?= htmlspecialchars($f['Titulo'] ?? '') ?></td>
<td><?= htmlspecialchars($f['FInicial']) ?></td>
<td><?= htmlspecialchars($f['FFinal']) ?></td>
<td><b><?= htmlspecialchars($f['Folios']) ?></b></td>

<td>
<form action="../pdf/RotuloCarpeta.php" method="post" target="_blank">
<button name="consulta" value="<?= $f['id'] ?>" class="btn btn-primary btn-sm">
Carpeta <?= $carpeta ?>
</button>
</form>
</td>

<td>
<?php if ($carpeta === 1): ?>
<form action="../pdf/RotuloCaja.php" method="post" target="_blank">
<button name="consulta" value="<?= $caja ?>" class="btn btn-primary btn-sm">
Caja <?= $caja ?>
</button>
</form>
<?php endif; ?>
</td>
</tr>

<tr class="panel" style="display:none">
<td colspan="11">
<form action="../pdf/Indice.php" method="post" target="_blank">
<input type="hidden" name="Carpeta" value="<?= $carpeta ?>">
<div class="d-grid gap-2 col-6 mx-auto">
<button name="Caja" value="<?= $caja ?>" class="btn btn-info btn-sm">
Índice Carpeta <?= $carpeta ?>
</button>
</div>
</form>

<table class="mi-tabla2">
<?php foreach ($indices[$caja][$carpeta] ?? [] as $doc): ?>
<tr>
<td><i><?= htmlspecialchars($doc['DescripcionUnidadDocumental']) ?></i></td>
<td><?= htmlspecialchars($doc['NoFolioInicio']) ?></td>
<td><?= htmlspecialchars($doc['NoFolioFin']) ?></td>
<td>
<?php if (!empty($doc['ruta_pdf']) && $doc['Soporte'] === 'FD'): ?>
<form action="download.php" method="get" target="_blank">
 <button name="id2" value="<?= $doc['id'] ?>" class="btn btn-success btn-sm">
  Ver PDF
 </button>
</form>
<?php else: ?>
<form action="idcargar.php" method="post" target="_blank">
 <input type="hidden" name="id" value="<?= $doc['id'] ?>">
 <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
 <button class="btn btn-primary btn-sm">
  Subir
 </button>
</form>
<?php endif; ?>

</td>
</tr>
<?php endforeach; ?>
</table>
</td>
</tr>
<?php endwhile; ?>

</tbody>
</table>
</div>


<script src="rotulo.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
