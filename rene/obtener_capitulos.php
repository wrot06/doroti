<?php
include 'conexion3.php'; // Archivo que contiene la conexión a la base de datos

$caja = $_GET['caja'];
$carpeta = $_GET['carpeta'];

$sql = "SELECT id2 AS id, DescripcionUnidadDocumental AS titulo, paginas, NoFolioInicio AS paginaInicio, NoFolioFin AS paginaFinal 
        FROM IndiceTemp 
        WHERE Caja = ? AND Carpeta = ?";
$stmt = $conec->prepare($sql);
$stmt->bind_param("ii", $caja, $carpeta);
$stmt->execute();
$result = $stmt->get_result();

$capitulos = [];
while ($row = $result->fetch_assoc()) {
    $capitulos[] = $row;
}

$stmt->close();
$conec->close();

header('Content-Type: application/json');
echo json_encode($capitulos);
