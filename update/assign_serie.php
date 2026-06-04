<?php

session_start();

header('Content-Type: application/json');

require_once "../rene/conexion3.php";

if(!isset($_SESSION['user_id'])){

echo json_encode([
'success'=>false,
'message'=>'Sesión expirada'
]);

exit();
}

$id=(int)($_POST['id'] ?? 0);

$serie=trim($_POST['serie'] ?? '');

if(!$id || !$serie){

echo json_encode([
'success'=>false,
'message'=>'Datos incompletos'
]);

exit();
}

$stmtBuscar=$conec->prepare("
SELECT DescripcionUnidadDocumental
FROM indice_documental
WHERE id=?
");

$stmtBuscar->bind_param("i",$id);

$stmtBuscar->execute();

$res=$stmtBuscar->get_result();

$row=$res->fetch_assoc();

if(!$row){

echo json_encode([
'success'=>false,
'message'=>'Registro no encontrado'
]);

exit();
}

$descripcionActual=trim($row['DescripcionUnidadDocumental']);

$descripcionLimpia=preg_replace(
'/^[^:]+:\s*/',
'',
$descripcionActual
);

$nuevaDescripcion=$serie.': '.$descripcionLimpia;

$stmt=$conec->prepare("
UPDATE indice_documental
SET serie=?,
DescripcionUnidadDocumental=?
WHERE id=?
");

if(!$stmt){

echo json_encode([
'success'=>false,
'message'=>$conec->error
]);

exit();
}

$stmt->bind_param(
"ssi",
$serie,
$nuevaDescripcion,
$id
);

if($stmt->execute()){

echo json_encode([
'success'=>true
]);

}else{

echo json_encode([
'success'=>false,
'message'=>$stmt->error
]);

}

$stmtBuscar->close();
$stmt->close();

$conec->close();