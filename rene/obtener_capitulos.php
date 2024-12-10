<?php
//obtener_capitulos.php
include 'conexion3.php'; // Archivo que contiene la conexión a la base de datos

// Habilitar el reporte de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Obtener y validar parámetros
$caja = isset($_GET['caja']) ? intval($_GET['caja']) : null;
$carpeta = isset($_GET['carpeta']) ? intval($_GET['carpeta']) : null;

if (is_null($caja) || is_null($carpeta)) {
    echo json_encode(['status' => 'error', 'message' => 'Parámetros inválidos.']);
    exit();
}

$sql = "SELECT id2 AS id, DescripcionUnidadDocumental AS titulo, paginas, NoFolioInicio AS paginaInicio, NoFolioFin AS paginaFinal 
        FROM IndiceTemp 
        WHERE Caja = ? AND Carpeta = ?";
$stmt = $conec->prepare($sql);

if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => 'Error en la preparación de la consulta.']);
    exit();
}

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
?>
