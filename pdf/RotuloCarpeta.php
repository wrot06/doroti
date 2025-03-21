<?php
require('fpdf.php');

require('../rene/conexion3.php');

if(isset($_POST['consulta'])){
	$idpost=$_POST['consulta'];           
} 
class PDF extends fpdf {
function Header()
{
    // Logo
    $this->Image('../img/flayer seminario mujeres.jpeg',0,0,-50);
}
}

$sql = "SELECT * FROM Carpetas WHERE id = ".intval($idpost);            

if($result = $conec->query($sql)) {         


while($row = $result->fetch_assoc()) { 
    $id=$row['id'];
    $Caja=$row['Caja'];
    $Carpeta=$row['Carpeta'];
    $CaCa =$row['CaCa'];
    $Car2=$row['Car2'];
    $Serie=$row['Serie'];
    $Subs=$row['Subs'];
    $Titulo=$row['Titulo'];
    $FInicial=$row['FInicial'];
    $FFinal=$row['FFinal'];
    $Folios=$row['Folios'];    
}    
    $result->free();
}

    
  

// Creación del objeto de la clase heredada
$pdf=new fpdf('L', 'mm', array(216,330)); 
$pdf->SetTitle(utf8_decode($Car2." Carpeta (Caja ".$Caja).")");

$pdf->AddPage();
$pdf->AliasNbPages();
$pdf->Image('../img/Carpeta AYC-GDO-FR-19.jpg',0,0,335);




$pdf->SetFont('Arial','',9);
$pdf->SetXY(42.3, 91.5);
$pdf->MultiCell(87,6.1,utf8_decode($Serie), 0);//Serie

$pdf->SetXY(42.3, 99.1);
$pdf->MultiCell(87,6.8,utf8_decode($Subs), 0);//Sub-serie

$pdf->SetXY(42.3, 107.3);
$pdf->MultiCell(87,6.2,utf8_decode($Titulo), 0);//Titulo Carpeta

$pdf->SetXY(134.1, 109.9);
$pdf->MultiCell(24.1,10,utf8_decode($Car2), 0, 'C');//Numero carpeta

$pdf->SetXY(42.3, 121.4);
$pdf->MultiCell(87,8.5,utf8_decode(''), 0);//Titulo Expediente

$pdf->SetXY(134.1, 124.1);
$pdf->MultiCell(24.1,5.7,utf8_decode($Folios), 0, 'C');//total Folios

$pdf->SetXY(134.1, 134.9);
$pdf->MultiCell(24.1,6.6,utf8_decode($Caja), 0, 'C');//Caja

$pdf->SetXY(89, 142.9);
$pdf->MultiCell(30.6,6.8,utf8_decode($Folios), 0, 'C');//Folios 2



$fecha =  $FInicial;
$fechaComoEntero = strtotime($fecha);
$Iano = date("Y", $fechaComoEntero);
$Imes = date("m", $fechaComoEntero);
$Idia = date("d", $fechaComoEntero);

//fecha Inicial
$pdf->SetXY(55.2, 154.5);
$pdf->MultiCell(14.4,4.5,utf8_decode($Iano), 0, 'C');//año

$pdf->SetXY(69.7, 154.5);
$pdf->MultiCell(14.4,4.5,utf8_decode($Imes ), 0, 'C');//mes

$pdf->SetXY(84.2, 154.5);
$pdf->MultiCell(14.4,4.5,utf8_decode($Idia), 0, 'C');//dia


$fecha2 = $FFinal;
$fechaComoEntero2 = strtotime($fecha2);
$Fano = date("Y", $fechaComoEntero2);
$Fmes = date("m", $fechaComoEntero2);
$Fdia = date("d", $fechaComoEntero2);
//fecha Final
$pdf->SetXY(118, 154.5);
$pdf->MultiCell(12.8,4.5,utf8_decode($Fano), 0, 'C');//año

$pdf->SetXY(130.9, 154.5);
$pdf->MultiCell(12.8,4.5,utf8_decode($Fmes), 0, 'C');//mes

$pdf->SetXY(143.7, 154.5);
$pdf->MultiCell(12.8,4.5,utf8_decode($Fdia), 0, 'C');//dia


$pdf->Output('', utf8_decode("Carpeta ".$Car2." Caja ".$Caja.".pdf"));