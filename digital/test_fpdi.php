<?php
// 1. Oeste archivo se peude BORRAR sol oes apra probar las libererias dentor de la carpeta libs
require_once "libs/tcpdf/tcpdf.php";
require_once "libs/fpdi/src/autoload.php";

use setasign\Fpdi\Tcpdf\Fpdi;

$pdf=new FPDI();
$pdf->AddPage();
$pdf->SetFont('helvetica','',12);
$pdf->Cell(0,10,'FPDI OK',0,1);
$pdf->Output();
