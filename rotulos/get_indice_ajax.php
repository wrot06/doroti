<?php
declare(strict_types=1);
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
AuthMiddleware::initSession();
require_once __DIR__ . '/../rene/conexion3.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['authenticated'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$carpeta_id = isset($_GET['carpeta_id']) ? (int)$_GET['carpeta_id'] : 0;
if ($carpeta_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de carpeta inválido']);
    exit;
}

$dependencia_id = $_SESSION['dependencia_id'] ?? null;
$tableName = getIndiceTableName($conec, (int)$dependencia_id);

$stmtDocs = $conec->prepare("SELECT id, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, Soporte FROM `$tableName` WHERE carpeta_id = ?");
if (!$stmtDocs) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al consultar la base de datos']);
    exit;
}

$stmtDocs->bind_param("i", $carpeta_id);
$stmtDocs->execute();
$resDocs = $stmtDocs->get_result();

$documentos = [];
while ($doc = $resDocs->fetch_assoc()) {
    $pdfPath = __DIR__ . '/../uploads/' . $doc['id'] . '.pdf';
    $hasPdf = is_file($pdfPath) && $doc['Soporte'] === 'FD';
    
    $documentos[] = [
        'id' => (int)$doc['id'],
        'descripcion' => $doc['DescripcionUnidadDocumental'],
        'folio_inicio' => $doc['NoFolioInicio'],
        'folio_fin' => $doc['NoFolioFin'],
        'has_pdf' => $hasPdf
    ];
}
$stmtDocs->close();

echo json_encode(['documentos' => $documentos]);
