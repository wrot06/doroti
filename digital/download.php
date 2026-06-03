<?php
declare(strict_types=1);
session_start();
require_once "../rene/conexion3.php";

if(empty($_SESSION['authenticated'])){http_response_code(401);exit;}

$doc_id=(int)($_GET['id']??0);
$force=(int)($_GET['download']??0);
$user_id=(int)($_SESSION['user_id']??0);

$sql="
SELECT dv.archivo_pdf, dv.archivo_nombre, dv.documento_id
FROM documento_versiones dv
JOIN documentos d ON d.id=dv.documento_id
WHERE dv.documento_id=? AND dv.activa=1
";
$stmt=$conec->prepare($sql);
$stmt->bind_param("i",$doc_id);
$stmt->execute();
$r=$stmt->get_result()->fetch_assoc();

if(!$r){http_response_code(404);exit;}

// Validar existencia del archivo físico
$filepath = __DIR__ . '/../' . $r['archivo_pdf'];
if (empty($r['archivo_pdf']) || !file_exists($filepath)) {
    http_response_code(404);
    exit("El archivo no existe en el almacenamiento físico del servidor.");
}

// Registrar acción en la tabla de auditoría (historial_acciones)
$accion = "Descargar/Visualizar documento";
$tabla = "documentos";
$detalles = "Se visualizó/descargó el documento: '" . $r['archivo_nombre'] . "' (" . ($force ? 'Descarga' : 'Visualización en línea') . ")";
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

$audit_stmt = $conec->prepare("
    INSERT INTO historial_acciones (user_id, accion, tabla, registro_id, detalles, ip)
    VALUES (?, ?, ?, ?, ?, ?)
");
$audit_stmt->bind_param("ississ",
    $user_id,
    $accion,
    $tabla,
    $r['documento_id'],
    $detalles,
    $ip
);
$audit_stmt->execute();
$audit_stmt->close();

// Servir el archivo físico
header("Content-Type: application/pdf");
header(
"Content-Disposition: ".
($force?'attachment':'inline').
"; filename=\"".$r['archivo_nombre']."\""
);
header("Content-Length: ".filesize($filepath));
readfile($filepath);
exit;
