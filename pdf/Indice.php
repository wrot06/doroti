<?php
session_start();
require('fpdf.php');
require('../rene/conexion3.php');

if (!isset($_POST['Carpeta'], $_POST['Caja'])) exit('Parámetros inválidos.');

$Carpeta = $conec->real_escape_string($_POST['Carpeta']);
$Caja = $conec->real_escape_string($_POST['Caja']);
$dependencia_id = $_SESSION['dependencia_id'] ?? null;

/* =====================================================
   CLASE PDF EXTENDIDA
   ===================================================== */
class PDF extends FPDF
{

    function NbLines($w, $txt)
    {
        $cw = $this->CurrentFont['cw'];
        if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n") $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') $sep = $i;
            $l += isset($cw[$c]) ? $cw[$c] : 0;
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) $i++;
                } else $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else $i++;
        }
        return $nl;
    }
}

/* =====================================================
   CONSULTA OFICINA Y CENTRO
   ===================================================== */
$sqlInfo = "SELECT d1.nombre AS oficina,d2.nombre AS centro
FROM Carpetas c
LEFT JOIN dependencias d1 ON d1.id=c.dependencia_id
LEFT JOIN dependencias d2 ON d2.id=d1.parent_id
WHERE c.Caja='$Caja' AND c.Carpeta='$Carpeta'
AND c.dependencia_id=$dependencia_id LIMIT 1";

$Oficina = '';
$Centro = '';
$resInfo = $conec->query($sqlInfo);
if ($resInfo && $resInfo->num_rows > 0) {
    $info = $resInfo->fetch_assoc();
    $Oficina = strtoupper(mb_convert_encoding($info['oficina'] ?? '', 'ISO-8859-1', 'UTF-8'));
    $Centro = strtoupper(mb_convert_encoding($info['centro'] ?? '', 'ISO-8859-1', 'UTF-8'));
}

/* =====================================================
   CONSULTA INDICE
   ===================================================== */
$sql = "SELECT * FROM IndiceDocumental
WHERE Caja='$Caja' AND Carpeta='$Carpeta'
AND dependencia_id=$dependencia_id";

$result = $conec->query($sql);
if (!$result || $result->num_rows === 0) exit('No hay datos para este índice.');

/* =====================================================
   CREAR PDF
   ===================================================== */
$pdf = new PDF('L', 'mm', [216, 330]);
$pdf->AliasNbPages();
$pdf->SetTitle("Carpeta {$Carpeta} Caja {$Caja}");
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
$pdf->MultiCell(
    $AnchoMulticell,
    5,
    mb_convert_encoding("Caja $Caja  |  Carpeta $Carpeta", 'ISO-8859-1', 'UTF-8')
);

$pdf->Image('../img/Indice Cabesera pag 1.jpg', 15, 42, 305, 0);

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetXY($M_Izquierdo + 24, 48);
$pdf->MultiCell($AnchoMulticell, 4, $Centro);

$pdf->SetXY($M_Izquierdo + 34, 52.7);
$pdf->MultiCell($AnchoMulticell, 4, $Oficina);

/* =====================================================
   TABLA
   ===================================================== */
$SaltoLinea = 85;
$linea = 5;
$pagina = 1;

$pdf->SetFont('Arial', '', 9);
$pdf->SetXY(132, 12.5);
$pdf->Cell(
    0,
    10,
    mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8') .
        $pdf->PageNo() .
        mb_convert_encoding(' de {nb}', 'ISO-8859-1', 'UTF-8'),
    0,
    0,
    'C'
);

while ($row = $result->fetch_assoc()) {

    $Descripcion = mb_convert_encoding(
        $row['DescripcionUnidadDocumental'],
        'ISO-8859-1',
        'UTF-8'
    );

    $Inicio = $row['NoFolioInicio'];
    $Fin = $row['NoFolioFin'];

    $numeroLineas = $pdf->NbLines(265, $Descripcion);
    $altura_filas = $numeroLineas * $linea;

    $limite = ($pagina == 1) ? 175 : 185;

    if ($SaltoLinea + $altura_filas >= $limite) {
        $pdf->AddPage();
        $pdf->Image('../img/Indice Cabesera.jpg', $x, $y, $imageWidth, 0);
        $SaltoLinea = 35;
        $pagina++;

        $pdf->SetXY(132, 12.5);
        $pdf->Cell(
            0,
            10,
            mb_convert_encoding('Página ', 'ISO-8859-1', 'UTF-8') .
                $pdf->PageNo() .
                mb_convert_encoding(' de {nb}', 'ISO-8859-1', 'UTF-8'),
            0,
            0,
            'C'
        );
    }

    $yActual = $SaltoLinea;

    $pdf->SetXY($M_Izquierdo, $yActual);
    $pdf->MultiCell(265, $linea, $Descripcion, 1, 'L');

    $colX = 280;
    foreach ([$Inicio, $Fin, '', 'x'] as $val) {
        $pdf->SetXY($colX, $yActual);
        $pdf->Cell(10, $altura_filas, $val, 1, 0, 'C');
        $colX += 10;
    }

    $SaltoLinea += $altura_filas;
}

/* =====================================================
   PIE DE PAGINA
   ===================================================== */
$pdf->Image('../img/Indice Pie de Pagina.jpg', $x, $SaltoLinea + 4, $imageWidth, 0);

$meses = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre'
];

$dia = date('j');
$mes = $meses[(int)date('n')];
$anio = date('Y');

$pdf->SetXY(50, $SaltoLinea + 13);
$pdf->MultiCell(
    0,
    2,
    mb_convert_encoding("$dia de $mes de $anio", 'ISO-8859-1', 'UTF-8'),
    0,
    1
);

/* =====================================================
   OUTPUT
   ===================================================== */
ob_clean();
$pdf->Output('', "Carpeta {$Carpeta} Caja {$Caja}.pdf");
