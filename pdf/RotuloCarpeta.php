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
// Consulta con oficina y centro
// ------------------------------
$sql = "
SELECT c.*, 
       d1.nombre AS oficina, 
       d2.nombre AS centro
FROM Carpetas c
LEFT JOIN dependencias d1 ON d1.id = c.dependencia_id   -- oficina directa
LEFT JOIN dependencias d2 ON d2.id = d1.parent_id       -- centro (padre de oficina)
WHERE c.id = $idpost
";
$result = $conec->query($sql);
if (!$result || $result->num_rows === 0) exit('No existe el registro.');

$row = $result->fetch_assoc();
$result->free();

// ------------------------------
// Variables
// ------------------------------
$Caja        = $row['Caja'];
$Car2        = $row['Carpeta'];
$Serie       = $row['Serie'];
$Subs        = $row['Subs'];
$Titulo      = $row['Titulo'];
$FInicial    = $row['FInicial'];
$FFinal      = $row['FFinal'];
$Folios      = $row['Folios'];
$Oficina     = $row['oficina'] ?? '';
$Centro      = $row['centro'] ?? '';


// ------------------------------
// Crear PDF
// ------------------------------
$pdf = new PDF('L','mm', array(216,330));
$pdf->AddPage();
$pdf->SetFont('Arial','',9);




$pdf->SetTitle(conv("C$Caja-$Car2"));
$pdf->AliasNbPages();

// Imagen base
$pdf->Image('../img/Carpeta AYC-GDO-FR-19.jpg',0,0,335);

// Oficina y Centro en mayúsculas
$pdf->SetXY(42.3,57.5); // ajusta la posición según tu diseño
$pdf->MultiCell(87,4, conv(strtoupper($Centro)));

$pdf->SetXY(42.3,69); // ajusta la posición según tu diseño
$pdf->MultiCell(87,4, conv(strtoupper($Oficina)));

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
