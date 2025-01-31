<?php
require('fpdf.php');

require('../rene/conexion3.php');

if(isset($_POST['Carpeta']) and isset($_POST['Caja'])){
	$Carpeta=$_POST['Carpeta']; 
    $Caja=$_POST['Caja'];
} 


$sql = "SELECT * FROM IndiceDocumental WHERE Caja = '" . $Caja. "' AND Carpeta = '". $Carpeta."'";    

// Creación del objeto de la clase heredada
$pdf=new fpdf('L', 'mm', array(216,330)); 
$pdf->AliasNbPages();
$pdf->SetTitle(utf8_decode($Carpeta." Carpeta (Caja ".$Caja).")");

$pdf->AddPage();

$pageWidth = $pdf->GetPageWidth();
$pageHeight = $pdf->GetPageHeight();

// Dimensiones de la imagen
$imageWidth = 240; // Ancho de la imagen que deseas colocar
$imageHeight = 0; // Altura automática (0 mantiene la proporción)

// Calcula la posición X para centrar la imagen horizontalmente
$x = ($pageWidth - $imageWidth) / 2;
$y = 7; // Mantener la posición en la parte superior de la página

$M_Izquierdo=15;
$AnchoMulticell=305;

// Coloca la imagen centrada
$pdf->Image('../img/Indice Cabesera.jpg', $x, $y, $imageWidth, $imageHeight);

$pdf->SetFont('Arial','',10);
$pdf->SetXY($M_Izquierdo+2, 37);
$pdf->MultiCell($AnchoMulticell, 5,utf8_decode("Caja ".$Caja." Carpeta ".$Carpeta ));//Descripción del Documento
$pdf->SetFont('Arial','B',10);
$pdf->SetXY($M_Izquierdo, 42);
$pdf->MultiCell($AnchoMulticell, 5,utf8_decode("ÍNDICE DE INFORMACIÓN DOCUMENTAL"), 1, 'C');//Descripción del Documento
$pdf->SetXY($M_Izquierdo, 47);
$pdf->MultiCell($AnchoMulticell, 5,utf8_decode("Dependencia: Facultad de Derecho y Ciencias Políticas"), 1);
$pdf->SetXY($M_Izquierdo, 52);
$pdf->MultiCell($AnchoMulticell, 5,utf8_decode("Oficina productora: Centro de Investigaciones y Estudios Socio-jurídicos CIESJU"), 1);


$pdf->SetXY($M_Izquierdo, 57);
$pdf->MultiCell(265,28,utf8_decode("Descripción de la unidad documental"), 1, 'C');//Descripción del Documento
$pdf->SetXY(280, 57);

$texto_vertical1 = implode("\n", str_split("Inicio "));
$texto_vertical2 = implode("\n", str_split(" Final "));
$texto_vertical3 = implode("\n", str_split("Digital"));
$texto_vertical4 = implode("\n", str_split(utf8_decode("Físico ")));

$pdf->MultiCell(10, 4,utf8_decode($texto_vertical1), 1, 'C');//Folio de Inicio del Documento
$pdf->SetXY(290, 57);
$pdf->MultiCell(10, 4,utf8_decode($texto_vertical2), 1, 'C');//Folio de Fin del Documento
$pdf->SetXY(300, 57);
$pdf->MultiCell(10, 4,utf8_decode($texto_vertical3), 1, 'C');//Soporte del Documento
$pdf->SetXY(310, 57);
$pdf->MultiCell(10, 4,$texto_vertical4, 1, 'C');//Soporte del Documento

$SaltoLinea = 85;
$linea=5;
$contador=1;
$espacio="\n";
$pagina=1;
$linea2=5;

$pdf->SetFont('Arial', '', 9);         
$pdf->SetXY(132, 12.5);
$pdf->Cell(0, 10, utf8_decode('Página ' . $pdf->PageNo() . ' de {nb}'), 0, 0, 'C');

if ($result = $conec->query($sql) ) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $Descripcion = $row['DescripcionUnidadDocumental'];
        $Car2 = $row['Carpeta'];
        $Caja = $row['Caja'];
        $Inicio = $row['NoFolioInicio'];
        $Fin = $row['NoFolioFin'];
        $Soporte = $row['Soporte'];

        $size = strlen($Descripcion);     

        if ($size >= 553) {
            $linea2 = 15;
        } 
        
        if ($size >= 362 and $size< 553) {
            $linea2 = 15;
        } 
        
        if ($size > 181 and $size< 362) {
            $linea2 = 10;
        } 
        
        if ($size <= 180) {
            $linea2 = 5;
        }  

        $pdf->SetXY($M_Izquierdo, $SaltoLinea);
        $pdf->MultiCell(265, $linea, utf8_decode($Descripcion), 1, 'L', false); // Descripción del Documento
        $pdf->SetXY(280, $SaltoLinea);
        $pdf->MultiCell(10, $linea2, utf8_decode($Inicio), 1, 'C'); // Folio de Inicio del Documento
        $pdf->SetXY(290, $SaltoLinea);
        $pdf->MultiCell(10, $linea2, utf8_decode($Fin), 1, 'C'); // Folio de Fin del Documento
        $pdf->SetXY(300, $SaltoLinea);
        $pdf->MultiCell(10, $linea2, utf8_decode(""), 1, 'C'); // Soporte del Documento
        $pdf->SetXY(310, $SaltoLinea);
        $pdf->MultiCell(10, $linea2, utf8_decode("x"), 1, 'C'); // Soporte del Documento

        if ($size >= 181 and $size <= 366) {            
            $SaltoLinea +=5;                                   
        } 

        if ($size >= 366) {            
            $SaltoLinea +=10;                                   
        } 
                  
        $SaltoLinea +=5;      
        $contador +=1;

        if ($SaltoLinea >= 175 and $pagina==1) {
            $pdf->AddPage();
            $pdf->Image('../img/Indice Cabesera.jpg', $x, $y, $imageWidth, $imageHeight);
            $SaltoLinea=35;
            $pagina+=1;
            $pdf->SetXY(132, 12.5);
            $pdf->Cell(0, 10, utf8_decode('Página ' . $pdf->PageNo() . ' de {nb}'), 0, 0, 'C');
        }

        if ($SaltoLinea >= 190 and $pagina>=2) {
            $pdf->AddPage();
            $pdf->Image('../img/Indice Cabesera.jpg', $x, $y, $imageWidth, $imageHeight);
            $SaltoLinea=35;
            $pagina+=1;       
            $pdf->SetXY(132, 12.5);
            $pdf->Cell(0, 10, utf8_decode('Página ' . $pdf->PageNo() . ' de {nb}'), 0, 0, 'C');        
        }
    }
    $result->free();
}


$pdf->Image('../img/Indice Pie de Pagina.jpg', $x, $SaltoLinea+3, $imageWidth, $imageHeight);

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
    7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$dia = date('j');
$mes = $meses[(int)date('n')];
$anio = date('Y');
$fecha_actual = "$dia de $mes de $anio";

if ($SaltoLinea >= 180) {
    $SaltoLinea+=10.9;
}else{
    $SaltoLinea+=13;
}

$pdf->SetXY(50, $SaltoLinea);
$pdf->MultiCell(0, 0, utf8_decode("$fecha_actual"), 0, 1);


$pdf->Output('', utf8_decode("Carpeta ".$Carpeta." Caja ".$Caja.".pdf"));