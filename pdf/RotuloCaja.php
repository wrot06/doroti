<?php
require('fpdf.php');
require('../rene/conexion3.php');

if (isset($_POST['consulta'])) {
    $idpost = $_POST['consulta'];
}

class PDF extends FPDF {
    function Header() {
        $this->Image('../img/Caja AYC-GDO-FR-18 top.png', 0, 0, 175);
    }
}

$sql = "SELECT * FROM Carpetas WHERE Caja = " . intval($idpost);
$FFinal = "";
$fecha_actual = date('Y-m-d');
$FInicial = $fecha_actual;
$serieA = [];
$titulo_array = [];
$subs_array = [];
$Subs = '';

if ($result = $conec->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $Caja = $row['Caja'];
        $Car2 = $row['Car2'];
        $Serie = $row['Serie'];
        $Subs = $row['Subs'];
        $Titulo = $row['Titulo'];

        if ($FInicial > $row['FInicial']) {
            $FInicial = $row['FInicial']; 
        }
        if ($FFinal < $row['FFinal']) {
            $FFinal = $row['FFinal']; 
        }

        $serieA[] = $Serie;
        $titulo_array[] = $Titulo;

        if (is_string($Subs) || is_int($Subs)) {
            $subs_array[] = $Subs;
        }
    }
    $result->free();
}

$subs_array = array_filter($subs_array, fn($v) => is_string($v) || is_int($v));
$subs_frequencies = array_count_values($subs_array);
$Subs = '';
foreach ($subs_frequencies as $valor => $cantidad) {
    if ($cantidad >= 2) {
        $Subs = iconv('UTF-8', 'windows-1252', $valor);
        break;
    }
}

$frequencies = [];
foreach ($serieA as $serieA) {
    if (isset($frequencies[$serieA])) {
        $frequencies[$serieA]++;
    } else {
        $frequencies[$serieA] = 1;
    }
}
$mostFrequentSerie = null;
$maxCount = 0;
foreach ($frequencies as $serieA => $count) {
    if ($count > $maxCount) {
        $maxCount = $count;
        $mostFrequentSerie = $serieA;
    }
}

$mostFrequentSerie = iconv('UTF-8', 'windows-1252', $mostFrequentSerie);

$pdf = new PDF('P', 'mm', array(216, 330));
$pdf->AddPage();
$pdf->SetTitle("Caja $Caja");
$pdf->SetFont('Arial', '', 9);
$pdf->SetXY(47.3, 84);
$pdf->MultiCell(87, 6.1, $mostFrequentSerie, 0);

$pdf->SetXY(47.3, 95);
$pdf->MultiCell(87, 6.8, $Subs, 0);

$salto = 117;
$salto2 = 0;
$contador = 1;
$Saltoimagen = 0;
$salto3 = 0;

foreach ($titulo_array as $titulo) {
    $titulo = iconv('UTF-8', 'windows-1252', $titulo);
    $titulo_lines = explode("\n", wordwrap($titulo, 62, "\n"));
    foreach ($titulo_lines as $line) {
        $pdf->SetXY(45.5, $salto);
        $pdf->MultiCell(103.4, 5, $line, 1, 1);
        $pdf->SetXY(149, $salto);
        $pdf->MultiCell(17, 5, $contador++, 1, 'C');
        $salto += 5;
        $salto2 += 5;
    }
}

$pdf->SetXY(10, 117);
$pdf->MultiCell(30.8, $salto2, "CONTENIDO", 1, 'C');

if ($contador == 4) $Saltoimagen -= 25;
if ($contador == 5) $Saltoimagen -= 20;
if ($contador == 6) $Saltoimagen -= 15;
if ($contador == 7) $Saltoimagen -= 10;
if ($contador == 8) $Saltoimagen -= 5;
if ($contador == 9) $Saltoimagen += 0;
if ($contador == 10) $Saltoimagen += 5;
if ($contador == 11) $Saltoimagen += 10;
if ($contador == 12) $Saltoimagen += 15;
if ($contador == 13) $Saltoimagen += 20;
if ($contador == 14) $Saltoimagen += 25;
if ($contador == 15) $Saltoimagen += 30;
if ($contador == 16) $Saltoimagen += 35;

$salto += 7.5;
$pdf->Image('../img/Caja AYC-GDO-FR-18 below.png', 0, $Saltoimagen, 175);

$fecha = $FInicial;
$fechaComoEntero = strtotime($fecha);
$Iano = date("Y", $fechaComoEntero);
$Imes = date("m", $fechaComoEntero);
$Idia = date("d", $fechaComoEntero);

$pdf->SetXY(62.2, $salto);
$pdf->MultiCell(14.4, 4.5, $Iano, 0, 'C');
$pdf->SetXY(74.7, $salto);
$pdf->MultiCell(14.4, 4.5, $Imes, 0, 'C');
$pdf->SetXY(88.2, $salto);
$pdf->MultiCell(14.4, 4.5, $Idia, 0, 'C');

$fecha2 = $FFinal;
$fechaComoEntero2 = strtotime($fecha2);
$Fano = date("Y", $fechaComoEntero2);
$Fmes = date("m", $fechaComoEntero2);
$Fdia = date("d", $fechaComoEntero2);

$pdf->SetXY(125, $salto);
$pdf->MultiCell(12.8, 4.5, $Fano, 0, 'C');
$pdf->SetXY(138.9, $salto);
$pdf->MultiCell(12.8, 4.5, $Fmes, 0, 'C');
$pdf->SetXY(151.7, $salto);
$pdf->MultiCell(12.8, 4.5, $Fdia, 0, 'C');

$salto3 = $salto + 8;
$pdf->SetFont('Arial', '', 14);
$pdf->SetXY(57.3, $salto3);
$pdf->MultiCell(24.1, 6.6, $Caja, 0, 'C');

$salto3 += 10;
$pdf->SetXY(42.3, $salto3);
$pdf->MultiCell(24.1, 10, $Car2, 0, 'C');

$pdf->Output('', "Caja $Caja.pdf");
