<?php
session_start();
require "../rene/conexion3.php";

ini_set('display_errors','1');
ini_set('display_startup_errors','1');
error_reporting(E_ALL);

function flash(string $msg,string $type='success'):void{
 $_SESSION['flash']=['message'=>$msg,'type'=>$type];
}
function redirect(string $url):void{
 header("Location: {$url}");
 exit;
}

if(empty($_SESSION['csrf_token'])){
 $_SESSION['csrf_token']=bin2hex(random_bytes(32));
}

$id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT)
 ?:filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if(!$id){
 flash("ID inválido",'error');
 redirect('subido.php');
}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_FILES['archivo_pdf'])){

 $tokenPost=$_POST['csrf_token']??'';
 if(!hash_equals($_SESSION['csrf_token'],$tokenPost)){
  flash("Token CSRF inválido",'error');
  redirect("idcargar.php?id={$id}");
 }

 $stmt=$conec->prepare("SELECT Soporte FROM IndiceDocumental WHERE id=?");
 $stmt->bind_param("i",$id);
 $stmt->execute();
 $stmt->bind_result($soporteActual);
 if(!$stmt->fetch()){
  $stmt->close();
  flash("Registro no existe",'error');
  redirect('subido.php');
 }
 $stmt->close();

 $nuevoSoporte=($soporteActual==='F')?'FD':$soporteActual;

 $serie=trim(filter_input(INPUT_POST,'etiqueta',FILTER_UNSAFE_RAW));
 if(!$serie){
  flash("Debes seleccionar una serie",'error');
  redirect("idcargar.php?id={$id}");
 }

 if($_FILES['archivo_pdf']['error']!==UPLOAD_ERR_OK){
  flash("Error al subir archivo",'error');
  redirect("idcargar.php?id={$id}");
 }

 $finfo=new finfo(FILEINFO_MIME_TYPE);
 $mime=$finfo->file($_FILES['archivo_pdf']['tmp_name']);
 if($mime!=='application/pdf'){
  flash("El archivo no es PDF",'error');
  redirect("idcargar.php?id={$id}");
 }

 if($_FILES['archivo_pdf']['size']>5*1024*1024){
  flash("El PDF supera 5 MB",'error');
  redirect("idcargar.php?id={$id}");
 }

 $dir=__DIR__."../uploads";
 if(!is_dir($dir)) mkdir($dir,0755,true);

 $pathRel="uploads/{$id}.pdf";
 $pathAbs=$dir."/{$id}.pdf";

 if(!move_uploaded_file($_FILES['archivo_pdf']['tmp_name'],$pathAbs)){
  flash("No se pudo guardar el PDF",'error');
  redirect("idcargar.php?id={$id}");
 }

 $fecha=date('Y-m-d H:i:s');
 $conec->begin_transaction();

 $upd=$conec->prepare("
  UPDATE IndiceDocumental
  SET ruta_pdf=?, serie=?, cargaFecha=?, Soporte=?
  WHERE id=?
 ");
 $upd->bind_param("ssssi",$pathRel,$serie,$fecha,$nuevoSoporte,$id);

 if($upd->execute()){
  $conec->commit();
  flash("PDF subido correctamente",'success');
 }else{
  $conec->rollback();
  flash("Error al guardar",'error');
 }

 $upd->close();
 redirect("subido.php?id={$id}");
}

$stmt=$conec->prepare("SELECT * FROM IndiceDocumental WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$registro=$stmt->get_result()->fetch_assoc();
$stmt->close();
if(!$registro){
 flash("Registro no encontrado",'error');
 redirect('subido.php');
}

$series=[];
$res=$conec->query("SELECT nombre FROM Serie ORDER BY nombre ASC");
while($r=$res->fetch_assoc()) $series[]=$r['nombre'];
$res->free();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Subir PDF</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">

<?php if(!empty($_SESSION['flash'])):
$f=$_SESSION['flash']; unset($_SESSION['flash']); ?>
<div class="alert alert-<?=$f['type']==='success'?'success':'danger'?>">
<?=htmlspecialchars($f['message'])?>
</div>
<?php endif; ?>

<div class="card">
<div class="card-body">
<h4>Subir PDF ID <?=htmlspecialchars($id)?></h4>

<table class="table table-bordered">
<tr><th>ID</th><td><?=$registro['id']?></td></tr>
<tr><th>Descripción</th><td><?=htmlspecialchars($registro['DescripcionUnidadDocumental'])?></td></tr>
<tr><th>Soporte</th><td><?=htmlspecialchars($registro['Soporte'])?></td></tr>
</table>

<form method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?=$id?>">
<input type="hidden" name="csrf_token" value="<?=$_SESSION['csrf_token']?>">

<div class="mb-3">
<label>Serie</label>
<select name="etiqueta" class="form-select" required>
<option value="">Seleccione</option>
<?php foreach($series as $s): ?>
<option value="<?=htmlspecialchars($s)?>"><?=htmlspecialchars($s)?></option>
<?php endforeach; ?>
</select>
</div>

<div class="mb-3">
<label>PDF (máx 5MB)</label>
<input type="file" name="archivo_pdf" class="form-control" accept="application/pdf" required>
</div>

<button class="btn btn-primary">Subir PDF</button>
<a href="rotulo.php" class="btn btn-secondary">Volver</a>
</form>
</div>
</div>

</body>
</html>
