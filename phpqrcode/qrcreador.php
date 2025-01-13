<?php    



session_start();

if(isset($_POST['id'])) {
    $_SESSION["idencargado"] = "B";
    $id=$_POST['id'];
    $_SESSION['id']=$_POST['id'];    
    
    $PNG_TEMP_DIR = dirname(__FILE__).DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR;    
   
    $PNG_WEB_DIR = 'temp/';

    include "qrlib.php";  
    QRtools::buildCache();  
    
    if (!file_exists($PNG_TEMP_DIR))
        mkdir($PNG_TEMP_DIR);
        
    $filename = $PNG_TEMP_DIR.'test.png';    
    
    $errorCorrectionLevel = 'H';
    if (isset($_REQUEST['level']) && in_array($_REQUEST['level'], array('L','M','Q','H')))
        $errorCorrectionLevel = $_REQUEST['level'];    

    $matrixPointSize = 10;
    if (isset($_REQUEST['size']))
        $matrixPointSize = min(max((int)$_REQUEST['size'], 1), 10);

    if (isset($_REQUEST['data'])) {     
       
        if (trim($_REQUEST['data']) == '')
            die('data cannot be empty! <a href="?">back</a>');            
        
        $filename = $PNG_TEMP_DIR.'test'.md5($_REQUEST['data'].'|'.$errorCorrectionLevel.'|'.$matrixPointSize).'.png';
        QRcode::png($_REQUEST['data'], $filename, $errorCorrectionLevel, $matrixPointSize, 2);    
        
    } else {        
        
           
        QRcode::png('https://ciesju.udenar.edu.co/app/consulta.php?id='.$id.'&submit=', $filename, $errorCorrectionLevel, $matrixPointSize, 2);            
    }    
        
    echo '<script>window.open("../pdf/PDFcertificado.php","_blank");</script>';
    echo '<META HTTP-EQUIV="REFRESH" CONTENT="1;URL=../consulta.php?id='.$id.'&submit=">';  
}

else
{
    $_SESSION["idencargado"] = "X";
    echo '<META HTTP-EQUIV="REFRESH" CONTENT="0;URL=../certificar.php">';
}  

    