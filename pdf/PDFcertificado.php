<?php
require('fpdf.php');
session_start();
require('../rene/conexion.php');

if ($_SESSION["idencargado"] == "X") {
   header("refresh:0;../certificar.php");
}

if ($_SESSION["idencargado"] == "B") {
  

class PDF extends FPDF
{
// Cabecera de página
function Header()
{   
           
    $titulo="";    
    $this->Image('../img/diplomaFondo.jpg',0,0,279.2,'C');
    $this->Image('../phpqrcode/temp/test.png',231,32,34);    
}

// Pie de página

}

// Creación del objeto de la clase heredada
$fpdf = new PDF('L','mm','letter');
$fpdf->SetTitle(utf8_decode("CERTIFICACIÓN(").$_SESSION['id'].") X Congreso Internacional de Derecho Constitucional 2023 - UDENAR");
$fpdf->AliasNbPages();
$fpdf->AddPage();

            $fpdf->AddFont("Concept Medium",'',"Concept Medium.php");
            $fpdf->SetFont('Concept Medium','',22);
            $fpdf->Cell(80);   

            $DoctipoLargo="NN";

            $sql = "SELECT id, tipo, nombres, DocTipo, correo FROM participantes WHERE id =".$_SESSION['id'];            

            if($result = $conec->query($sql)) {         


            while($row = $result->fetch_assoc()) { 
                $nombre=$row['nombres'];
                $cedula=$row['id'];
                $tipo=$row['DocTipo'];
                $largo = strlen($nombre);
                $calidad=$row['tipo'];
                $correo=$row['correo'];
                
            }    
                $result->free();
            }

            if ($tipo=='CC') {
                $DoctipoLargo="Cedula de Ciudadanía número ";
            }

            if ($tipo=='TI') {
                $DoctipoLargo="Tarjeta de Identidad número ";
            }

            if ($tipo=='NN') {
                $DoctipoLargo="Pasaporte ".$correo;
            }

            if ($calidad=='DOCENTEU') {
                $calidad="CONFERENCISTA".utf8_decode(" en representación de la Universidad de Nariño,");
            }
            
    // Título
    
    $fpdf->Cell(95,141, utf8_decode($nombre),0,1,'C');
    $fpdf->AddFont("Montserrat",'',"Montserrat-Regular.php");
    $fpdf->SetFont('Montserrat','',11);
    $fpdf->Cell(255,-128, "Identificado(a) con ".utf8_decode($DoctipoLargo)."".$cedula,0,1,'C');
    $fpdf->Cell(255,138, utf8_decode("Participó en calidad de ").utf8_decode($calidad).utf8_decode(" en el "),0,1,'C');
    // Salto de línea
    $fpdf->Ln(20);


    if ($cedula=='14313211') {//esto crea la segunda pagina del pdf
        $fpdf->AddPage();

        $fpdf->AddFont("Concept Medium",'',"Concept Medium.php");
        $fpdf->SetFont('Concept Medium','',22);
        $fpdf->Cell(80);     

            $DoctipoLargo="NN";

            $sql = "SELECT id, tipo, nombres, DocTipo, correo FROM participantes WHERE id =".$_SESSION['id'];            

            if($result = $conec->query($sql)) {         


            while($row = $result->fetch_assoc()) { 
                $nombre=$row['nombres'];
                $cedula=$row['id'];
                $tipo=$row['DocTipo'];
                $largo = strlen($nombre);
                $calidad=$row['tipo'];
                $correo=$row['correo'];
                
            }    
                $result->free();
            }

            if ($tipo=='CC') {
                $DoctipoLargo="Cedula de Ciudadanía número ";
            }

            if ($tipo=='TI') {
                $DoctipoLargo="Tarjeta de Identidad número ";
            }

            if ($tipo=='NN') {
                $DoctipoLargo="Pasaporte ".$correo;
            }

            if ($calidad=='DOCENTEU') {
                $calidad="CONFERENCISTA".utf8_decode(" en representación de la Universidad de Nariño,");
            }          
    // Título
            $calidad="ASISTENTE";
            $fpdf->Cell(95,141, utf8_decode($nombre),0,1,'C');
            $fpdf->AddFont("Montserrat",'',"Montserrat-Regular.php");
            $fpdf->SetFont('Montserrat','',11);
            $fpdf->Cell(255,-128, "Identificado(a) con ".utf8_decode($DoctipoLargo)."".$cedula,0,1,'C');
            $fpdf->Cell(255,138, utf8_decode("Participó en calidad de ").utf8_decode($calidad).utf8_decode(" en el "),0,1,'C');
            // Salto de línea
            $fpdf->Ln(20);
    }






$fpdf->Output('', utf8_decode("CERTIFICACIÓN(").$_SESSION['id'].") X Congreso Internacional de Derecho Constitucional 2023-UDENAR.pdf");

 
}
session_destroy();
