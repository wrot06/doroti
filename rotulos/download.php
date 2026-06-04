<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../rene/conexion3.php';

if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
 http_response_code(403);
 exit("Acceso denegado");
}

$id=filter_input(INPUT_GET,'id2',FILTER_VALIDATE_INT);
if(!$id){
 http_response_code(400);
 exit("ID inválido");
}

$tableName = getIndiceTableNameByDocumentId($conec, $id);
$stmt=$conec->prepare("
  SELECT DescripcionUnidadDocumental
  FROM `$tableName`
  WHERE id=?
 ");
$stmt->bind_param("i",$id);
$stmt->execute();
$doc=$stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$doc){
 http_response_code(404);
 exit("Documento no encontrado");
}

$ruta=__DIR__.'/../uploads/'.$id.'.pdf';
if(!is_file($ruta)){
 http_response_code(404);
 exit("Documento sin PDF o archivo no encontrado");
}

$filename=basename($ruta);

header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"$filename\"");
header("Content-Length: ".filesize($ruta));
header("Cache-Control: private, max-age=0, must-revalidate");
header("Pragma: public");

readfile($ruta);
exit;
