<?php
require('fpdf.php');
require('../rene/conexion3.php');

if (isset($_POST['Carpeta']) and isset($_POST['Caja'])) {
    $Carpeta = $_POST['Carpeta'];
    $Caja = $_POST['Caja'];
}

$sql = "SELECT * FROM IndiceDocumental WHERE Caja = '" . $Caja . "' AND Carpeta = '" . $Carpeta . "'";

$pdf = new FPDF('L', 'mm', array(216, 330));
$pdf->AliasNbPages();
$pdf->SetTitle(utf8_decode("{$Carpeta} Carpeta (Caja {$Caja})"));
$pdf->AddPage();

$pageWidth = $pdf->GetPageWidth();
$imageWidth = 240;
$x = ($pageWidth - $imageWidth) / 2;
$y = 7;
$M_Izquierdo = 15;
$AnchoMulticell = 305;

$pdf->SetFont('Arial', '', 10);
$pdf->Image('../img/Indice Cabesera.jpg', $x, $y, $imageWidth, 0);

$pdf->SetXY($M_Izquierdo + 2, 37);
$pdf->MultiCell($AnchoMulticell, 5, utf8_decode("Caja $Caja Carpeta $Carpeta"));
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY($M_Izquierdo, 42);
$pdf->MultiCell($AnchoMulticell, 5, utf8_decode("ÍNDICE DE INFORMACIÓN DOCUMENTAL"), 1, 'C');
$pdf->SetXY($M_Izquierdo, 47);
$pdf->MultiCell($AnchoMulticell, 5, utf8_decode("Dependencia: Facultad de Derecho y Ciencias Políticas"), 1);
$pdf->SetXY($M_Izquierdo, 52);
$pdf->MultiCell($AnchoMulticell, 5, utf8_decode("Oficina productora: Centro de Investigaciones y Estudios Socio-jurídicos CIESJU"), 1);

$pdf->SetXY($M_Izquierdo, 57);
$pdf->MultiCell(265, 28, utf8_decode("Descripción de la unidad documental"), 1, 'C');
$pdf->SetXY(280, 57);

$texto_vertical1 = implode("\n", str_split("Inicio "));
$texto_vertical2 = implode("\n", str_split(" Final "));
$texto_vertical3 = implode("\n", str_split("Digital"));
$texto_vertical4 = implode("\n", str_split(iconv('UTF-8', 'windows-1252',"Físico ")));

$pdf->MultiCell(10, 4, $texto_vertical1, 1, 'C');
$pdf->SetXY(290, 57);
$pdf->MultiCell(10, 4, $texto_vertical2, 1, 'C');
$pdf->SetXY(300, 57);
$pdf->MultiCell(10, 4, $texto_vertical3, 1, 'C');
$pdf->SetXY(310, 57);
$pdf->MultiCell(10, 4, $texto_vertical4, 1, 'C');

$SaltoLinea = 85;
$linea = 5;
$pagina = 1;
$linea2 = 5;

$pdf->SetFont('Arial', '', 9);
$pdf->SetXY(132, 12.5);
$pdf->Cell(0, 10, utf8_decode('Página ') . $pdf->PageNo() . utf8_decode(' de {nb}'), 0, 0, 'C');


if ($result = $conec->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $Descripcion = utf8_decode($row['DescripcionUnidadDocumental']);
        $Inicio = $row['NoFolioInicio'];
        $Fin = $row['NoFolioFin'];


        $size = strlen($Descripcion);
        $linea2 = ($size >= 905) ? 30 
        : (($size >= 724) ? 25 
        : (($size >= 543) ? 20 
        : (($size >= 362) ? 15 
        : (($size > 181)  ? 10 : 5))));

        $pdf->SetXY($M_Izquierdo, $SaltoLinea);
        $pdf->MultiCell(265, $linea, $Descripcion, 1, 'L', false);
        $pdf->SetXY(280, $SaltoLinea);
        $pdf->MultiCell(10, $linea2, $Inicio, 1, 'C');
        $pdf->SetXY(290, $SaltoLinea);
        $pdf->MultiCell(10, $linea2, $Fin, 1, 'C');
        $pdf->SetXY(300, $SaltoLinea);
        $pdf->MultiCell(10, $linea2, "", 1, 'C');
        $pdf->SetXY(310, $SaltoLinea);
        $pdf->MultiCell(10, $linea2, "x", 1, 'C');

        $SaltoLinea += ($size >= 905) ? 30 
             : (($size >= 724) ? 25 
             : (($size >= 543) ? 20 
             : (($size >= 362) ? 15 
             : (($size > 181)  ? 10 : 5))));
        

        if (($SaltoLinea >= 175 && $pagina == 1) || ($SaltoLinea >= 185 && $pagina >= 2)) {
            $pdf->AddPage();
            $pdf->Image('../img/Indice Cabesera.jpg', $x, $y, $imageWidth, 0);
            $SaltoLinea = 35;
            $pagina++;
            $pdf->SetXY(132, 12.5);
            $pdf->Cell(0, 10, utf8_decode('Página ') . $pdf->PageNo() . utf8_decode(' de {nb}'), 0, 0, 'C');
        }
    }
    $result->free();
}

$pdf->Image('../img/Indice Pie de Pagina.jpg', $x, $SaltoLinea + 4, $imageWidth, 0);

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$dia = date('j');
$mes = $meses[(int)date('n')];
$anio = date('Y');
$fecha_actual = utf8_decode("$dia de $mes de $anio");

$pdf->SetXY(50, $SaltoLinea + 13);
$pdf->MultiCell(0, 2, $fecha_actual, 0, 1);

ob_clean();
$pdf->Output('', utf8_decode("Carpeta {$Carpeta} Caja {$Caja}.pdf"));
