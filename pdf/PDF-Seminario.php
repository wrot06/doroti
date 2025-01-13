<?php
require('fpdf.php');
session_start();
require('../rene/coneicon.php');

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
    $this->Image('../img/Certificado- II Seminario Regional.png',3,7,291,'C');
    $this->Image('../phpqrcode/temp2/test2.png',262,2,33);    
}

// Pie de página

}

// Creación del objeto de la clase heredada
$pdf = new PDF('L','mm','A4');
$pdf->SetTitle(utf8_decode("CERTIFICACIÓN(").$_SESSION['id'].") II Seminario Regional ICON-S Colombia");
$pdf->AliasNbPages();
$pdf->AddPage();            

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

            $link='https://ciesju.udenar.edu.co/app/consulta.php?id2='.$cedula.'&submit='; 

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

            if ($tipo=='DD') {
                $DoctipoLargo="Documento "; 
            } 

            if ($calidad=='DOCENTEU') {
                $calidad="CONFERENCISTA";
            }

            if ($cedula=='05725418') {
                $cedula="05725418"."M";
            }

            if ($cedula=='2007716461') {
                $cedula="2007716461"."-4";
            }
            
    // Título
    $pdf->AddFont("DancingScript-Regular",'',"DancingScript-Regular.php");
    $pdf->SetFont('DancingScript-Regular','',20); 
    $pdf->ln(99);  
    $pdf->SetTextColor(255,255,255);
    $pdf->Cell(192,10, utf8_decode($nombre),0,1,'C');
    $pdf->ln(3); 
    $pdf->AddFont("Montserrat",'',"Montserrat-Regular.php");
    $pdf->SetFont('Montserrat','',10);
    $pdf->SetTextColor(20,96,83);
    $pdf->Cell(192,5, "Identificado(a) con ".utf8_decode($DoctipoLargo)."".$cedula,0,1,'C');
    $pdf->ln(0); 
    $pdf->Cell(192,5, utf8_decode("Participó en Calidad de ").utf8_decode($calidad).utf8_decode(". dado en Pasto-Nariño el 1 Marzo de 2024"),0,1,'C');
    // Salto de línea
    $pdf->ln(57);
    $pdf->Write(0, utf8_decode("Verificación"), $link);


$pdf->Output('', utf8_decode("CERTIFICACIÓN(").$_SESSION['id'].") II Seminario Regional ICON-S Colombia.pdf");

 
}
session_destroy();


?>   

                 
            
            
                  
       


  
