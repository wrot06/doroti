<?php
declare(strict_types=1);
session_start();
require_once "../rene/conexion3.php";

/* ================== AUTH ================== */
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(401);
    exit('No autorizado');
}
$user_id=(int)$_SESSION['user_id'];

/* ================== ID ================== */
$documento_id=(int)($_GET['id']??0);
if($documento_id<=0){
    http_response_code(400);
    exit('ID inválido');
}

/* ================== VALIDAR PROPIEDAD ================== */
$stmt=$conec->prepare("
SELECT titulo_documento
FROM documentos
WHERE id=? AND user_id=? AND estado='activo'
LIMIT 1
");
$stmt->bind_param("ii",$documento_id,$user_id);
$stmt->execute();
$res=$stmt->get_result();

if($res->num_rows===0){
    http_response_code(404);
    exit('Documento no disponible');
}

$doc=$res->fetch_assoc();

/* ================== VERSIONES ================== */
$stmt=$conec->prepare("
SELECT id,archivo_nombre,hash_sha256,tamano_bytes,fecha_subida,activa
FROM documento_versiones
WHERE documento_id=?
ORDER BY fecha_subida DESC
");
$stmt->bind_param("i",$documento_id);
$stmt->execute();
$versions=$stmt->get_result();

function h(string $v): string {
    return htmlspecialchars($v,ENT_QUOTES,'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Versiones del Documento</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Historial de versiones</h4>
        <div class="text-muted small"><?=h($doc['titulo_documento'])?></div>
    </div>
    <a href="documents.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="card shadow-sm">
<div class="card-body p-0">

<table class="table table-hover mb-0 align-middle">
<thead class="table-light">
<tr>
    <th>#</th>
    <th>Archivo</th>
    <th>Fecha</th>
    <th>Tamaño</th>
    <th>Hash SHA-256</th>
    <th>Estado</th>
    <th class="text-end">Acciones</th>
</tr>
</thead>
<tbody>

<?php if($versions->num_rows>0): ?>
<?php while($v=$versions->fetch_assoc()): ?>
<tr>
    <td><?=intval($v['id'])?></td>
    <td><?=h($v['archivo_nombre'])?></td>
    <td><?=h($v['fecha_subida'])?></td>
    <td><?=number_format($v['tamano_bytes']/1024,2)?> KB</td>
    <td class="text-truncate" style="max-width:180px">
        <code><?=h($v['hash_sha256'])?></code>
    </td>
    <td>
        <?php if($v['activa']): ?>
            <span class="badge bg-success">Activa</span>
        <?php else: ?>
            <span class="badge bg-secondary">Histórica</span>
        <?php endif; ?>
    </td>
    <td class="text-end">
        <a class="btn btn-primary btn-sm"
           target="_blank"
           href="download.php?id=<?=$documento_id?>&version=<?=$v['id']?>">
            <i class="bi bi-eye"></i>
        </a>
        <a class="btn btn-outline-secondary btn-sm"
           href="download.php?id=<?=$documento_id?>&version=<?=$v['id']?>&mode=download">
            <i class="bi bi-download"></i>
        </a>
    </td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="7" class="text-center text-muted py-4">
    No hay versiones registradas
</td>
</tr>
<?php endif; ?>

</tbody>
</table>

</div>
</div>

</div>
</body>
</html>
