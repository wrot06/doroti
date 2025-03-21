<?php
require('fpdf.php');
require('../rene/conexion4.php');

if (isset($_POST['consulta'])) {
    $idpost = $_POST['consulta'];
}

class PDF extends FPDF {
    function Header() {

        //$this->SetXY(47.3, 84);// Logo
        $this->Image('../img/Caja AYC-GDO-FR-18 top consu.jpg', 0, 0, 175);
    }

}

$sql = "SELECT * FROM Carpetas WHERE Caja = " . intval($idpost);
$FFinal = "";
$fecha_actual = date('Y-m-d'); // Formato correcto de fecha
$FInicial = $fecha_actual;
$serieA = [];
$titulo_array = []; // Array para almacenar todos los valores de Titulo

if ($result = $conec->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $Caja = $row['Caja'];
        $Car2 = $row['Carpeta'];
        $Serie = $row['Serie'];
        $Subs = $row['Subs'];
        $Titulo = $row['Titulo'];
        
        if ($FInicial > $row['FInicial']) {
            $FInicial = $row['FInicial']; 
        }
        if ($FFinal < $row['FFinal']) {
            $FFinal = $row['FFinal']; 
        }
        
        // Añadir el Titulo al array
        $serieA[] = $Serie;
        $titulo_array[] = $Titulo;
    }
    $result->free();
}

$frequencies = [];

// Contar la frecuencia de cada serie
foreach ($serieA as $serieA) {
    if (isset($frequencies[$serieA])) {
        $frequencies[$serieA]++;
    } else {
        $frequencies[$serieA] = 1;
    }
}

// Encontrar la serie que más se repite
$mostFrequentSerie = null;
$maxCount = 0;

foreach ($frequencies as $serieA => $count) {
    if ($count > $maxCount) {
        $maxCount = $count;
        $mostFrequentSerie = $serieA;
    }
}

// Creación del objeto de la clase heredada
$pdf = new PDF('P', 'mm', array(216, 330));
$pdf->SetTitle(utf8_decode($Caja . " Caja"));
$pdf->AddPage();
$pdf->AliasNbPages();

$pdf->SetFont('Arial', '', 9);
$pdf->SetXY(47.3, 84);
$pdf->MultiCell(87, 6.1, utf8_decode($mostFrequentSerie), 0); // Serie

$pdf->SetXY(47.3, 95);
$pdf->MultiCell(87, 6.8, utf8_decode($Subs), 0); // Sub-serie

$salto = 117; // Valor inicial de la posición Y
$salto2 = 0;
$contador = 1;
$Saltoimagen=0;
$salto3 = 0;

foreach ($titulo_array as $titulo) {
    $titulo_lines = explode("\n", wordwrap($titulo, 62, "\n"));
    foreach ($titulo_lines as $line) {
        $pdf->SetXY(45.5, $salto);
        $pdf->MultiCell(103.4, 5, utf8_decode($line), 1, 1);
        $pdf->SetXY(149, $salto);
        $pdf->MultiCell(17.8, 5, $contador++, 1, 'C');
        $salto += 5; // Incrementa la posición Y
        $salto2 += 5;
    }
}

$pdf->SetXY(10, 117);
$pdf->MultiCell(30.8, $salto2, "CONTENIDO", 1, 'C');

if ($contador==3) {
    $Saltoimagen= $Saltoimagen-20;
    //$salto=$salto+2.5;
}

if ($contador==4) {
    $Saltoimagen= $Saltoimagen-15;
    //$salto=$salto+2.5;
}

if ($contador<=7) {
    $Saltoimagen= $Saltoimagen-5;
    //$salto=$salto+2.5;
}

if ($contador<=8) {
    $Saltoimagen= $Saltoimagen-5;
    $salto=$salto-2.5;
}

if ($contador>8) {   
    $salto=$salto-2.5;
}

if ($contador>9) {
    $Saltoimagen= $Saltoimagen+5;
    //$salto=$salto-2.5;
}

if ($contador>10) {
    $Saltoimagen= $Saltoimagen+5;
    //$salto=$salto-2.5;
}

if ($contador>11) {
    $Saltoimagen= $Saltoimagen+5;
    //$salto=$salto+2.5;
}

if ($contador>12) {
    $Saltoimagen= $Saltoimagen+5;
    //$salto=$salto+2.5;
}

// Dimensiones de la página
$pageHeight = $pdf->GetPageHeight();
$margin = 10; // Margen de seguridad para evitar que la imagen se salga


list($imgWidth, $imgHeight) = getimagesize('../img/Caja AYC-GDO-FR-18 below.png');
$imageWidth = 175; // Ancho deseado en mm


$salto += 10;
$pdf->Image('../img/Caja AYC-GDO-FR-18 below.png', 0, $Saltoimagen, $imageWidth);


$pdf->SetFont('Arial', '', 9);

$fecha = $FInicial;
$fechaComoEntero = strtotime($fecha);
$Iano = date("Y", $fechaComoEntero);
$Imes = date("m", $fechaComoEntero);
$Idia = date("d", $fechaComoEntero);

// Fecha Inicial
$pdf->SetXY(62.2, $salto);
$pdf->MultiCell(14.4, 4.5, $Iano, 0, 'C'); // Año
$pdf->SetXY(74.7, $salto);
$pdf->MultiCell(14.4, 4.5, $Imes, 0, 'C'); // Mes
$pdf->SetXY(88.2, $salto);
$pdf->MultiCell(14.4, 4.5, $Idia, 0, 'C'); // Día

$fecha2 = $FFinal;
$fechaComoEntero2 = strtotime($fecha2);
$Fano = date("Y", $fechaComoEntero2);
$Fmes = date("m", $fechaComoEntero2);
$Fdia = date("d", $fechaComoEntero2);

// Fecha Final
$pdf->SetXY(125, $salto);
$pdf->MultiCell(12.8, 4.5, $Fano, 0, 'C'); // Año
$pdf->SetXY(138.9, $salto);
$pdf->MultiCell(12.8, 4.5, $Fmes, 0, 'C'); // Mes
$pdf->SetXY(151.7, $salto);
$pdf->MultiCell(12.8, 4.5, $Fdia, 0, 'C'); // Día

$salto3=$salto+8;

$salto += 5; // Ajustar la posición Y para el número de carpeta
$pdf->SetFont('Arial', '', 14);
$pdf->SetXY(57.3, $salto3);
$pdf->MultiCell(24.1, 6.6, utf8_decode($Caja), 0, 'C'); // Caja

$salto3+=10;

$salto += 9; // Ajustar la posición Y para el número de carpeta
$pdf->SetXY(42.3, $salto3);
$pdf->MultiCell(24.1, 10, utf8_decode($Car2), 0, 'C'); // Numero carpeta



$pdf->Output('', "Caja " . $Caja . ".pdf");


