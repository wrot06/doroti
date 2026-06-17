<?php
declare(strict_types=1);
ob_start();
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
AuthMiddleware::initSession();
AuthMiddleware::checkCsrf();

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

$tableName = getIndiceTableNameByDocumentId($conec, $id);
$stmtBuscar=$conec->prepare("
SELECT DescripcionUnidadDocumental
FROM `$tableName`
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

$descripcionFront=trim($_POST['descripcion'] ?? '');

if ($descripcionFront !== '') {
    $descripcionLimpia=preg_replace(
        '/^[^:]+:\s*/',
        '',
        $descripcionFront
    );
} else {
    $descripcionActual=trim($row['DescripcionUnidadDocumental']);
    $descripcionLimpia=preg_replace(
        '/^[^:]+:\s*/',
        '',
        $descripcionActual
    );
}

$nuevaDescripcion=$serie.': '.$descripcionLimpia;

$stmt=$conec->prepare("
UPDATE `$tableName`
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
ob_end_flush();