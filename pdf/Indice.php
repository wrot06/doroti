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

$pdf->Image('../img/Indice Cabesera pag 1.png', 15, 42, 305, 0);

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetXY($M_Izquierdo, 42);

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

        // Calcula la altura de la descripción según el número de líneas
        $lineas = explode("\n", wordwrap($Descripcion, 100)); // Ajusta 100 según el ancho/cantidad de caracteres
        $altura_filas = $linea * count($lineas);

        // Salto de página si se excede límite
        $limite = ($pagina == 1) ? 175 : 185;
        if ($SaltoLinea + $altura_filas >= $limite) {
            $pdf->AddPage();
            $pdf->Image('../img/Indice Cabesera.jpg', $x, $y, $imageWidth, 0);
            $SaltoLinea = 35;
            $pagina++;
            $pdf->SetXY(132, 12.5);
            $pdf->Cell(0, 10, utf8_decode('Página ') . $pdf->PageNo() . utf8_decode(' de {nb}'), 0, 0, 'C');
        }

        // Celda de descripción
            $maxCaracteresLinea = 175;
            $numeroLineas = ceil(strlen($Descripcion) / $maxCaracteresLinea);
            $altura_filas = $linea * $numeroLineas;

            // Celda de descripción
            $pdf->SetXY($M_Izquierdo, $SaltoLinea);
            $pdf->MultiCell(265, $linea, $Descripcion, 1, 'L', false);

            // Columnas pequeñas con la misma altura
            $colValues = [$Inicio, $Fin, "", "x"];
            $colX = 280;
            foreach ($colValues as $val) {
                $pdf->SetXY($colX, $SaltoLinea);
                $pdf->Cell(10, $altura_filas, $val, 1, 0, 'C');
                $colX += 10;
            }

            $SaltoLinea += $altura_filas;

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