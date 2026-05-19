<?php
ob_start();
session_start();
require('fpdf.php');
require('../rene/conexion3.php');

$dependencia_id = (int)($_SESSION['dependencia_id'] ?? 0);
if ($dependencia_id <= 0) {
    die('Dependencia no válida');
}

// ================== CONSULTA CORREGIDA ==================
$dependencia_id = (int)$_SESSION['dependencia_id'];

$sql = "
    SELECT *
    FROM Carpetas
    WHERE dependencia_id = ?
    ORDER BY Caja ASC, Carpeta ASC
";
$stmt = $conec->prepare($sql);
$stmt->bind_param('i', $dependencia_id);
$stmt->execute();
$result = $stmt->get_result();


$pdf = new FPDF('L', 'mm', array(216, 330));
$pdf->AliasNbPages();
$pdf->SetTitle("Inventario Carpetas");
$pdf->AddPage();

$pageWidth = $pdf->GetPageWidth();
$imageWidth = 320;
$x = ($pageWidth - $imageWidth) / 2;
$y = 7;
$M_Izquierdo = 15;
$AnchoMulticell = 305;

// Fuente
$pdf->SetFont('Arial', '', 10);

// Imagen superior
$pdf->Image('../img/inventario top.png', $x, $y, $imageWidth, 0);

// Posicionamos el cursor debajo de la imagen superior
$pdf->Ln(48.5);

// Fuente normal
$pdf->SetFont('Arial', '', 7);

$contador=1;

// Filas
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pdf->SetX(5.5);
        $pdf->Cell(13.6, 8, $contador++, 1, 0, 'C');
        $pdf->Cell(13.8, 8, "", 1, 0, 'C');
        $pdf->Cell(22.4, 8, substr(utf8_decode($row['Serie']) ?? '', 0, 11), 1, 0, 'L');
        $pdf->Cell(25.7, 8, substr(utf8_decode($row['Titulo']) ?? '', 0, 18), 1, 0, 'L');

        $pdf->SetFont('Arial', '', 5); // más pequeño
        $pdf->Cell(10.1, 8, $row['FInicial'], 1, 0, 'C');
        $pdf->Cell(10.2, 8, $row['FFinal'], 1, 0, 'C');
        $pdf->SetFont('Arial', '', 7); // más pequeño

        $pdf->Cell(12.5, 8, "X", 1, 0, 'C');
        $pdf->Cell(12.6, 8, "", 1, 0, 'C');
        $pdf->Cell(10.8, 8, $row['Caja'], 1, 0, 'C');
        $pdf->Cell(15.4, 8, $row['Carpeta'], 1, 0, 'C');
        $pdf->Cell(16.8, 8, "", 1, 0, 'C');
        $pdf->Cell(16.5, 8, $row['Folios'], 1, 0, 'C');
        $pdf->Cell(17.2, 8, "", 1, 0, 'C');
        $pdf->Cell(14.8, 8, "", 1, 0, 'C');
        $pdf->Cell(15.6, 8, "", 1, 0, 'C');
        $pdf->Cell(22.6, 8, "", 1, 0, 'C');
        $pdf->Cell(17.1, 8, "", 1, 0, 'C');
        $pdf->Cell(51.1, 8, "", 1, 0, 'C');
        $pdf->Ln();
    }
} else {
    $pdf->Cell(0, 10, "No hay registros en la tabla Carpetas.", 1, 1, 'C');
}

// Imagen inferior
$SaltoLinea = 180; // si quieres calcularlo exacto -> $pdf->GetPageHeight() - 30
$pdf->Image('../img/inventario below.png', $x, $SaltoLinea + 4, $imageWidth, 0);

// Limpiar buffer y enviar PDF
ob_end_clean();
$pdf->Output("I", "Inventario Carpetas.pdf");


