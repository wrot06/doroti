<?php
session_start();
require('fpdf.php');
require('../rene/conexion3.php');

if (!isset($_POST['Carpeta'], $_POST['Caja'])) {
    exit('Parámetros inválidos.');
}

$Carpeta = $conec->real_escape_string($_POST['Carpeta']);
$Caja    = $conec->real_escape_string($_POST['Caja']);

$oficina  = $_SESSION['oficina']  ?? null;
$dependencia_id  = $_SESSION['dependencia_id']  ?? null;

/* =====================================================
   1) TRAER OFICINA Y CENTRO (CONSULTA LIVIANA)
   ===================================================== */
$sqlInfo = "
SELECT 
    d1.nombre AS oficina,
    d2.nombre AS centro
FROM Carpetas c
LEFT JOIN dependencias d1 ON d1.id = c.dependencia_id
LEFT JOIN dependencias d2 ON d2.id = d1.parent_id
WHERE c.Caja = '$Caja'
  AND c.Carpeta = '$Carpeta'
  AND c.dependencia_id = $dependencia_id
LIMIT 1
";

$Oficina = '';
$Centro  = '';

$resInfo = $conec->query($sqlInfo);
if ($resInfo && $resInfo->num_rows > 0) {
    $info = $resInfo->fetch_assoc();
    $Oficina = strtoupper(utf8_decode($info['oficina'] ?? ''));
    $Centro  = strtoupper(utf8_decode($info['centro'] ?? ''));
}

/* =====================================================
   2) CONSULTA DEL ÍNDICE (SIN JOIN)
   ===================================================== */
$sql = "
SELECT *
FROM IndiceDocumental
WHERE Caja = '$Caja'
  AND Carpeta = '$Carpeta'
  AND dependencia_id = $dependencia_id
";

$result = $conec->query($sql);
if (!$result || $result->num_rows === 0) {
    exit('No hay datos para este índice.');
}

/* =====================================================
   3) CREAR PDF
   ===================================================== */
$pdf = new FPDF('L', 'mm', array(216, 330));
$pdf->AliasNbPages();
$pdf->SetTitle(utf8_decode("{$Carpeta} Carpeta (Caja {$Caja})"));
$pdf->AddPage();

$pageWidth = $pdf->GetPageWidth();
$imageWidth = 240;
$x = ($pageWidth - $imageWidth) / 2;
$y = 7;

$M_Izquierdo    = 15;
$AnchoMulticell = 305;

$pdf->SetFont('Arial', '', 10);
$pdf->Image('../img/Indice Cabesera.jpg', $x, $y, $imageWidth, 0);

/* Encabezado */
$pdf->SetXY($M_Izquierdo + 2, 37);
$pdf->MultiCell($AnchoMulticell, 5, utf8_decode("Caja $Caja  |  Carpeta $Carpeta"));

$pdf->Image('../img/Indice Cabesera pag 1.jpg', 15, 42, 305, 0);

/* AHORA sí, texto encima */
$pdf->SetFont('Arial', 'B', 9);

$pdf->SetXY($M_Izquierdo + 24, 48);
$pdf->MultiCell($AnchoMulticell, 4, $Centro);

$pdf->SetXY($M_Izquierdo + 34, 52.7);
$pdf->MultiCell($AnchoMulticell, 4, $Oficina);


/* =====================================================
   4) TABLA DE CONTENIDO
   ===================================================== */
$SaltoLinea = 85;
$linea      = 5;
$pagina     = 1;

$pdf->SetFont('Arial', '', 9);
$pdf->SetXY(132, 12.5);
$pdf->Cell(0, 10, utf8_decode('Página ') . $pdf->PageNo() . utf8_decode(' de {nb}'), 0, 0, 'C');

while ($row = $result->fetch_assoc()) {

    $Descripcion = utf8_decode($row['DescripcionUnidadDocumental']);
    $Inicio      = $row['NoFolioInicio'];
    $Fin         = $row['NoFolioFin'];

    $maxCaracteresLinea = 175;
    $numeroLineas = max(1, ceil(strlen($Descripcion) / $maxCaracteresLinea));
    $altura_filas = $linea * $numeroLineas;

    $limite = ($pagina == 1) ? 175 : 185;
    if ($SaltoLinea + $altura_filas >= $limite) {
        $pdf->AddPage();
        $pdf->Image('../img/Indice Cabesera.jpg', $x, $y, $imageWidth, 0);
        $SaltoLinea = 35;
        $pagina++;

        $pdf->SetXY(132, 12.5);
        $pdf->Cell(0, 10, utf8_decode('Página ') . $pdf->PageNo() . utf8_decode(' de {nb}'), 0, 0, 'C');
    }

    /* Descripción */
    $pdf->SetXY($M_Izquierdo, $SaltoLinea);
    $pdf->MultiCell(265, $linea, $Descripcion, 1, 'L', false);

    /* Columnas */
    $colValues = [$Inicio, $Fin, '', 'x'];
    $colX = 280;

    foreach ($colValues as $val) {
        $pdf->SetXY($colX, $SaltoLinea);
        $pdf->Cell(10, $altura_filas, $val, 1, 0, 'C');
        $colX += 10;
    }

    $SaltoLinea += $altura_filas;
}

/* =====================================================
   5) PIE DE PÁGINA
   ===================================================== */
$pdf->Image('../img/Indice Pie de Pagina.jpg', $x, $SaltoLinea + 4, $imageWidth, 0);

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$dia  = date('j');
$mes  = $meses[(int)date('n')];
$anio = date('Y');

$pdf->SetXY(50, $SaltoLinea + 13);
$pdf->MultiCell(0, 2, utf8_decode("$dia de $mes de $anio"), 0, 1);

/* =====================================================
   6) OUTPUT
   ===================================================== */
ob_clean();
$pdf->Output('', utf8_decode("Carpeta {$Carpeta} Caja {$Caja}.pdf"));
