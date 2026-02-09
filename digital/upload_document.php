<?php
declare(strict_types=1);
session_start();
require_once "../rene/conexion3.php";

if(empty($_SESSION['authenticated'])){http_response_code(401);exit;}
$user_id=(int)$_SESSION['user_id'];
$dependencia_id=(int)$_SESSION['dependencia_id'];

if(empty($_FILES['pdf']['tmp_name'])) exit('PDF requerido');
if($_FILES['pdf']['type']!=='application/pdf') exit('Archivo inválido');
if($_FILES['pdf']['size']>10*1024*1024) exit('Máx 10MB');

$pdf_bin=file_get_contents($_FILES['pdf']['tmp_name']);
$hash=hash('sha256',$pdf_bin);

$conec->begin_transaction();

$stmt=$conec->prepare("
INSERT INTO documentos
(tipo,titulo_documento,fecha_creacion,user_id,dependencia_id)
VALUES (?,?,?,?,?)
");
$stmt->bind_param("sssii",
$_POST['serie'],
$_POST['titulo_documento'],
$_POST['fecha_creacion'],
$user_id,
$dependencia_id
);
$stmt->execute();
$documento_id=$stmt->insert_id;

$radicado=sprintf(
"%s-%s-%06d-V1",
$dependencia_id,
date('Y'),
$documento_id
);

$stmt=$conec->prepare("
INSERT INTO documento_versiones
(documento_id,archivo_nombre,archivo_pdf,hash_sha256,tamano_bytes,activa)
VALUES (?,?,?,?,?,1)
");
$nombre="doc_{$documento_id}.pdf";
$tamano=strlen($pdf_bin);
$pdf_blob = ""; // Empty string for NOT NULL blob column to avoid max_allowed_packet
$stmt->bind_param("isssi",
$documento_id,
$nombre,
$pdf_blob,
$hash,
$tamano
);
// Guardar copia en carpeta tmp
$tmp_dir = __DIR__ . '/tmp';
if (!is_dir($tmp_dir)) {
    mkdir($tmp_dir, 0755, true);
}
if (file_put_contents($tmp_dir . '/' . $nombre, $pdf_bin) === false) {
    $conec->rollback();
    exit('Error al guardar archivo en tmp');
}

$stmt->execute();

$conec->commit();
header("Location: documents.php");
