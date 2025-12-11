<?php
require('fpdf.php');
require('../rene/conexion3.php');

if (ob_get_level()) { ob_end_clean(); }
ob_start();

function conv($txt) {
    return ($txt !== null && $txt !== '') ? iconv('UTF-8', 'ISO-8859-1', $txt) : '';
}

if (!isset($_POST['consulta'])) { exit('Parámetro inválido.'); }
$idpost = intval($_POST['consulta']);

// ------------------------------
// Clase PDF con SetDash
// ------------------------------
class PDF extends FPDF {
    function SetDash($black=null, $white=null) {
        if($black!==null)
            $s = sprintf('[%.3F %.3F] 0 d', $black*$this->k, $white*$this->k);
        else
            $s = '[] 0 d';
        $this->_out($s);
    }
}

// ------------------------------
// Consulta
// ------------------------------
$sql = "SELECT * FROM Carpetas WHERE id = $idpost";
$result = $conec->query($sql);
if (!$result || $result->num_rows === 0) exit('No existe el registro.');

$row = $result->fetch_assoc();
$result->free();

// ------------------------------
// Variables
// ------------------------------
$Caja     = $row['Caja'];
$Carpeta  = $row['Carpeta'];
$CaCa     = $row['CaCa'];
$Car2     = $row['Car2'];
$Serie    = $row['Serie'];
$Subs     = $row['Subs'];
$Titulo   = $row['Titulo'];
$FInicial = $row['FInicial'];
$FFinal   = $row['FFinal'];
$Folios   = $row['Folios'];

// ------------------------------
// Crear PDF
// ------------------------------
$pdf = new PDF('L','mm', array(216,330));
$pdf->AddPage();
$pdf->SetFont('Arial','',9);
$pdf->SetTitle(conv("$Car2 Carpeta (Caja $Caja)"));
$pdf->AliasNbPages();

// Imagen base
$pdf->Image('../img/Carpeta AYC-GDO-FR-19.jpg',0,0,335);

// Campos
$pdf->SetXY(42.3,91.5); $pdf->MultiCell(87,6.1,conv($Serie),0);
$pdf->SetXY(42.3,99.1); $pdf->MultiCell(87,6.8,conv($Subs),0);
$pdf->SetXY(42.3,107.3); $pdf->MultiCell(87,6.2,conv($Titulo),0);
$pdf->SetXY(134.1,109.9); $pdf->MultiCell(24.1,10,conv($Car2),0,'C');
$pdf->SetXY(42.3,121.4); $pdf->MultiCell(87,8.5,'',0);
$pdf->SetXY(134.1,124.1); $pdf->MultiCell(24.1,5.7,conv($Folios),0,'C');
$pdf->SetXY(134.1,134.9); $pdf->MultiCell(24.1,6.6,conv($Caja),0,'C');
$pdf->SetXY(89,142.9); $pdf->MultiCell(30.6,6.8,conv($Folios),0,'C');

// Fechas
$FI = strtotime($FInicial); $FF = strtotime($FFinal);
$Iano = $FI ? date("Y",$FI):''; $Imes = $FI ? date("m",$FI):''; $Idia = $FI ? date("d",$FI):'';
$Fano = $FF ? date("Y",$FF):''; $Fmes = $FF ? date("m",$FF):''; $Fdia = $FF ? date("d",$FF):'';

$pdf->SetXY(55.2,154.5); $pdf->MultiCell(14.4,4.5,$Iano,0,'C');
$pdf->SetXY(69.7,154.5); $pdf->MultiCell(14.4,4.5,$Imes,0,'C');
$pdf->SetXY(84.2,154.5); $pdf->MultiCell(14.4,4.5,$Idia,0,'C');
$pdf->SetXY(118,154.5); $pdf->MultiCell(12.8,4.5,$Fano,0,'C');
$pdf->SetXY(130.9,154.5); $pdf->MultiCell(12.8,4.5,$Fmes,0,'C');
$pdf->SetXY(143.7,154.5); $pdf->MultiCell(12.8,4.5,$Fdia,0,'C');

// ------------------------------
// Limpiar buffer
// ------------------------------
while(ob_get_level()) { ob_end_clean(); }

// ------------------------------
// Líneas de corte
// ------------------------------
$pdf->SetDrawColor(160,160,160);
$pdf->SetDash(1,1);

// Líneas horizontales
$pdf->Line(5,5,163,5);     // superior (7→5, 165→163)
$pdf->Line(5,211,163,211); // inferior

// Líneas verticales
$pdf->Line(5,5,5,211);     // izquierda
$pdf->Line(163,5,163,211); // derecha

$pdf->SetDash();




// ------------------------------
// Output
// ------------------------------
$pdf->Output('I', conv("Carpeta $Car2 Caja $Caja.pdf"));
