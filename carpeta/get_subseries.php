<?php
require "../rene/conexion3.php";

$serieId       = intval($_POST['serie_id'] ?? 0);
$dependenciaId = intval($_POST['dependencia_id'] ?? 0);

if ($serieId <= 0 || $dependenciaId <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT id, Subs AS nombre
    FROM Subs
    WHERE serie_id = ?
      AND dependencia_id = ?
    ORDER BY Subs ASC
";

$stmt = $conec->prepare($sql);
$stmt->bind_param("ii", $serieId, $dependenciaId);
$stmt->execute();

$res = $stmt->get_result();
$data = [];

while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);

$stmt->close();
$conec->close();