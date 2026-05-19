<?php
declare(strict_types=1);
session_start();
require_once "../rene/conexion3.php";

if(empty($_SESSION['authenticated'])){http_response_code(401);exit;}

$doc_id=(int)($_GET['id']??0);
$force=(int)($_GET['download']??0);

$sql="
SELECT dv.archivo_pdf,dv.archivo_nombre
FROM documento_versiones dv
JOIN documentos d ON d.id=dv.documento_id
WHERE dv.documento_id=? AND dv.activa=1
";
$stmt=$conec->prepare($sql);
$stmt->bind_param("i",$doc_id);
$stmt->execute();
$r=$stmt->get_result()->fetch_assoc();

if(!$r){http_response_code(404);exit;}

header("Content-Type: application/pdf");
header(
"Content-Disposition: ".
($force?'attachment':'inline').
"; filename=\"".$r['archivo_nombre']."\""
);
header("Content-Length: ".strlen($r['archivo_pdf']));
echo $r['archivo_pdf'];
exit;
