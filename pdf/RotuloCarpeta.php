<?php
require('fpdf.php');
require('../rene/conexion3.php');

ob_clean();
ob_start();

if (!isset($_POST['consulta'])) {
    exit;
}
$idpost = intval($_POST['consulta']);

class PDF extends FPDF {
    function Header() {
        $this->Image('../img/flayer seminario mujeres.jpeg', 0, 0, -50);
    }
}

$sql = "SELECT * FROM Carpetas WHERE id = $idpost";
$result = $conec->query($sql);
if (!$result || $result->num_rows === 0) {
    exit;
}

$row = $result->fetch_assoc();
$result->free();

$id = $row['id'];
$Caja = $row['Caja'];
$Carpeta = $row['Carpeta'];
$CaCa = $row['CaCa'];
$Car2 = $row['Car2'];
$Serie = $row['Serie'];
$Subs = $row['Subs'];
$Titulo = $row['Titulo'];
$FInicial = $row['FInicial'];
$FFinal = $row['FFinal'];
$Folios = $row['Folios'];

$pdf = new FPDF('L', 'mm', array(216, 330));
$pdf->AddPage();
$pdf->SetFont('Arial', '', 9);
$pdf->SetTitle(iconv('UTF-8', 'ISO-8859-1', "$Car2 Carpeta (Caja $Caja)"));
$pdf->AliasNbPages();
$pdf->Image('../img/Carpeta AYC-GDO-FR-19.jpg', 0, 0, 335);

$pdf->SetXY(42.3, 91.5);
$pdf->MultiCell(87, 6.1, iconv('UTF-8', 'ISO-8859-1', $Serie), 0);

$pdf->SetXY(42.3, 99.1);
$pdf->MultiCell(87, 6.8, iconv('UTF-8', 'ISO-8859-1', $Subs), 0);

$pdf->SetXY(42.3, 107.3);
$pdf->MultiCell(87, 6.2, iconv('UTF-8', 'ISO-8859-1', $Titulo), 0);

$pdf->SetXY(134.1, 109.9);
$pdf->MultiCell(24.1, 10, iconv('UTF-8', 'ISO-8859-1', $Car2), 0, 'C');

$pdf->SetXY(42.3, 121.4);
$pdf->MultiCell(87, 8.5, '', 0);

$pdf->SetXY(134.1, 124.1);
$pdf->MultiCell(24.1, 5.7, iconv('UTF-8', 'ISO-8859-1', $Folios), 0, 'C');

$pdf->SetXY(134.1, 134.9);
$pdf->MultiCell(24.1, 6.6, iconv('UTF-8', 'ISO-8859-1', $Caja), 0, 'C');

$pdf->SetXY(89, 142.9);
$pdf->MultiCell(30.6, 6.8, iconv('UTF-8', 'ISO-8859-1', $Folios), 0, 'C');

$fechaComoEntero = strtotime($FInicial);
$Iano = date("Y", $fechaComoEntero);
$Imes = date("m", $fechaComoEntero);
$Idia = date("d", $fechaComoEntero);

$pdf->SetXY(55.2, 154.5);
$pdf->MultiCell(14.4, 4.5, $Iano, 0, 'C');

$pdf->SetXY(69.7, 154.5);
$pdf->MultiCell(14.4, 4.5, $Imes, 0, 'C');

$pdf->SetXY(84.2, 154.5);
$pdf->MultiCell(14.4, 4.5, $Idia, 0, 'C');

$fechaComoEntero2 = strtotime($FFinal);
$Fano = date("Y", $fechaComoEntero2);
$Fmes = date("m", $fechaComoEntero2);
$Fdia = date("d", $fechaComoEntero2);

$pdf->SetXY(118, 154.5);
$pdf->MultiCell(12.8, 4.5, $Fano, 0, 'C');

$pdf->SetXY(130.9, 154.5);
$pdf->MultiCell(12.8, 4.5, $Fmes, 0, 'C');

$pdf->SetXY(143.7, 154.5);
$pdf->MultiCell(12.8, 4.5, $Fdia, 0, 'C');

$pdf->Output('I', iconv('UTF-8', 'ISO-8859-1', "Carpeta $Car2 Caja $Caja.pdf"));
