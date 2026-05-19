<?php
ob_start();
session_start();

require('fpdf.php');
require('../rene/conexion3.php');

if (!isset($_POST['consulta'])) {
    die('Parámetro faltante');
}
$idpost = (int)$_POST['consulta'];

class PDF extends FPDF {
    function Header() {
        $this->Image('../img/Caja AYC-GDO-FR-18 top.png',0,0,175);
    }
}


if (!isset($_SESSION['dependencia_id'])) {
    die('Dependencia no definida');
}

$dependencia_id = (int)$_SESSION['dependencia_id'];



$sql = "
SELECT c.*,
       d1.nombre AS oficina,
       d2.nombre AS centro
FROM Carpetas c
LEFT JOIN dependencias d1 ON d1.id = c.dependencia_id
LEFT JOIN dependencias d2 ON d2.id = d1.parent_id
WHERE c.Caja = $idpost
  AND c.dependencia_id = $dependencia_id AND c.estado = 'C'
";



$FFinal = '0000-00-00';
$FInicial = date('Y-m-d');
$serieA = [];
$titulo_array = [];
$subs_array = [];
$Subs = '';
$Caja = '';
$Car2 = '';
$OficinaNombre = '';
$CentroNombre = '';

$result = $conec->query($sql);
if (!$result || $result->num_rows === 0) {
    die('No hay datos para la caja');
}

while ($row = $result->fetch_assoc()) {
    $Caja = $row['Caja'];
    $Car2 = $row['Carpeta'];
    $Serie = $row['Serie'];
    $SubsTmp = $row['Subs'];
    $Titulo = $row['Titulo'];
    $OficinaNombre = $row['oficina'];
    $CentroNombre = $row['centro'];

    if ($FInicial > $row['FInicial']) $FInicial = $row['FInicial'];
    if ($FFinal < $row['FFinal']) $FFinal = $row['FFinal'];

    $serieA[] = (string)$Serie;
    $titulo_array[] = (string)$Titulo;

    if (is_string($SubsTmp) || is_int($SubsTmp)) {
        $subs_array[] = $SubsTmp;
    }
}
$result->free();

/* SUBSERIE */
$subs_array = array_filter($subs_array, fn($v)=>$v!=='' && $v!==null);
$subs_frequencies = array_count_values($subs_array);
foreach ($subs_frequencies as $valor=>$cantidad) {
    if ($cantidad>=2) {
        $Subs = iconv('UTF-8','windows-1252',(string)$valor);
        break;
    }
}

/* SERIE MÁS FRECUENTE */
$frequencies = [];
foreach ($serieA as $serie) {
    $frequencies[$serie] = ($frequencies[$serie] ?? 0) + 1;
}

$mostFrequentSerie = '';
$maxCount = 0;
foreach ($frequencies as $serie=>$count) {
    if ($count>$maxCount) {
        $maxCount = $count;
        $mostFrequentSerie = $serie;
    }
}
$mostFrequentSerie = iconv('UTF-8','windows-1252',$mostFrequentSerie);

/* PDF */
$pdf = new PDF('P','mm',[216,330]);
$pdf->AddPage();

$pdf->SetFont('Arial','',10);
$pdf->SetXY(47,58);
$pdf->MultiCell(80,4,iconv('UTF-8','windows-1252',strtoupper((string)$CentroNombre)));

$pdf->SetXY(47.5,70);
$pdf->MultiCell(80,4,iconv('UTF-8','windows-1252',strtoupper((string)$OficinaNombre)));

$pdf->SetTitle("Caja $Caja");
$pdf->SetFont('Arial','',9);
$pdf->SetXY(47.3,84);
$pdf->MultiCell(87,6.1,$mostFrequentSerie);

$pdf->SetXY(47.3,95);
$pdf->MultiCell(87,6.8,$Subs);

$salto = 117;
$salto2 = 0;
$contador = 1;
$Saltoimagen = 0;

foreach ($titulo_array as $titulo) {
    $titulo = iconv('UTF-8','windows-1252',(string)$titulo);
    $titulo_lines = explode("\n",wordwrap($titulo,62,"\n"));
    foreach ($titulo_lines as $line) {
        $pdf->SetXY(45.5,$salto);
        $pdf->MultiCell(103.4,5,$line,1);
        $pdf->SetXY(149,$salto);
        $pdf->MultiCell(17,5,$contador++,1,'C');
        $salto += 5;
        $salto2 += 5;
    }
}

$pdf->SetXY(10,117);
$pdf->MultiCell(30.8,$salto2,"CONTENIDO",1,'C');

$ajustes = [4=>-25,5=>-20,6=>-15,7=>-10,8=>-5,9=>0,10=>5,11=>10,12=>15,13=>20,14=>25,15=>30,16=>35];
$Saltoimagen += $ajustes[$contador] ?? 0;

$salto += 7.5;
$pdf->Image('../img/Caja AYC-GDO-FR-18 below.png',0,$Saltoimagen,175);

/* FECHAS */
$f1 = strtotime($FInicial);
$f2 = strtotime($FFinal);

$pdf->SetXY(62.2,$salto);
$pdf->MultiCell(14.4,4.5,date('Y',$f1),0,'C');
$pdf->SetXY(74.7,$salto);
$pdf->MultiCell(14.4,4.5,date('m',$f1),0,'C');
$pdf->SetXY(88.2,$salto);
$pdf->MultiCell(14.4,4.5,date('d',$f1),0,'C');

$pdf->SetXY(125,$salto);
$pdf->MultiCell(12.8,4.5,date('Y',$f2),0,'C');
$pdf->SetXY(138.9,$salto);
$pdf->MultiCell(12.8,4.5,date('m',$f2),0,'C');
$pdf->SetXY(151.7,$salto);
$pdf->MultiCell(12.8,4.5,date('d',$f2),0,'C');

$salto3 = $salto + 8;
$pdf->SetFont('Arial','',14);
$pdf->SetXY(57.3,$salto3);
$pdf->MultiCell(24.1,6.6,$Caja,0,'C');

$salto3 += 10;
$pdf->SetXY(42.3,$salto3);
$pdf->MultiCell(24.1,10,$Car2,0,'C');

ob_end_clean();
$pdf->Output('I',"Caja $Caja.pdf");
