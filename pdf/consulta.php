<!DOCTYPE html>

<html lang="es">

<?php   

    require 'rene/head.php';
    session_start();
    $_SESSION["idencargado"] = "X"; 
    $_SESSION["Selector"] = "Normal";
    $evento="";

    date_default_timezone_set('America/Bogota');
    $fechaActual=date("Y-m-d h:i:s A");
    $fechaEntera = strtotime($fechaActual);
    $dia = date("d", $fechaEntera);
    $mes = date("m", $fechaEntera);
    $ano = date("Y", $fechaEntera);

    if ($mes==1) $mesLargo="Enero";
    if ($mes==2) $mesLargo="Febrero";
    if ($mes==3) $mesLargo="Marzo";
    if ($mes==4) $mesLargo="Abril";
    if ($mes==5) $mesLargo="Mayo";
    if ($mes==6) $mesLargo="Junio";
    if ($mes==7) $mesLargo="Julio";
    if ($mes==8) $mesLargo="Agosto";
    if ($mes==9) $mesLargo="Septiembre";
    if ($mes==10) $mesLargo="Octubre";
    if ($mes==11) $mesLargo="Noviembre";
    if ($mes==12) $mesLargo="Diciembre";

  ?>



<body data-spy="scroll" data-target="#navbar" class="static-layout">
    
<center>

    <style>
        .ima1{
            max-width: 500px;
            width: 90%; 
            margin:auto;
            
        }

        p{
            padding-right: 15px;
            font-family: Calibri, sans-serif; 
            margin-right: 10px; 
            text-align: justify; 
        }

        @media (max-width: 768px) {
          .tu-imagen {
            display: none;
          }
        }
        /* Mostrar la imagen solo en modo escritorio */
        @media (min-width: 769px) {
          .tu-imagen {
            display: block;
          }
          .ima1{
            padding-right: 200px;
          }
        }

        .center {
            margin-left: auto;
            margin-right: auto;
            display: block;

        }
    </style>


<?php   

if(isset($_POST['id'])){
	$id=$_POST['id'];
    $_SESSION["idencargado"] = "B";
    $evento="X Congreso";    
} 

if(isset($_GET['id'])) {
    $id=$_GET['id'];
    $_SESSION["idencargado"] = "B";
    $evento="X Congreso";    
}

if(isset($_POST['id2'])){
	$id=$_POST['id2'];
    $_SESSION["idencargado"] = "B";
    $evento="Seminario_ICON-S";    
} 

if(isset($_GET['id2'])){
	$id=$_GET['id2'];
    $_SESSION["idencargado"] = "B";
    $evento="Seminario_ICON-S";    
} 

if($evento=="X Congreso"){
    require "rene/conexion.php";
    $redirección="qrcreador";
    $junto="";
    $textoOrganizadores="Centro de Investigaciones y Estudios Socio-Jurídicos (CIESJU) ".$junto;
    $textoEvento="X Congreso Internacional de Derecho Constitucional ";
    $textoFechaEvento="los días 10, 11 y 12 de mayo de 2023";
    $sitioEvento="las instalaciones del Club Colombia de la ciudad de Pasto, Departamento de Nariño, República de Colombia.";
    $horasAcademicas="36"; 
    echo '<img class="img" src="img/logo.png" style="max-width: 300px; min-width: 35%;"><br><br></center>';   
}

if($evento=="Seminario_ICON-S"){
    require "rene/coneicon.php";
    $redirección="qrcreador2";
    $junto=" junto a AICO-'confirmar nombre'";
    $textoOrganizadores="Centro de Investigaciones y Estudios Socio-Jurídicos (CIESJU) de la Universidad de Nariño".$junto;
    $textoEvento="II Seminario Regional ICON-S en Colombia: Miradas desde el Sur. Justicia Transicional, Democracia Constitucional y Literatura. ";
    $textoFechaEvento="el día 1 de marzo de 2023";
    $sitioEvento="la Universidad de Nariño.";
    $horasAcademicas="8"; 
    echo '<img class="tu-imagen" src="img/flayer seminario mujeres.jpeg" style="width: 150px;  float: left; margin-right: 15px;">'; 
    echo '<img class="ima1" src="img/logos seminario.jpg"><br><br></center>';  
}

echo '<center><div class="title-wrap" style="min-width: 375px; max-width: 500px; margin-right: 5px; margin-left: 5px; width: 100%;"><br>';


$sql ="SELECT * FROM participantes WHERE id ='".$id."'";
    $resultado = $conec->query("SELECT id FROM participantes WHERE id =".$id."");  
    $row_cnt = $resultado->num_rows; 

if ($row_cnt==1) {
    echo "<b>CERTIFICACIÓN</b><br><br>";
    $larguito="NN";

if ($result = $conec->query($sql)) {
    while ($fila = $result->fetch_assoc()) { 

        if ($fila['DocTipo']=='CC') {
            $larguito="Cedula de Ciudadanía número ";
        }

        if ($fila['DocTipo']=='TI') {
            $larguito="Tarjeta de Identidad número ";  
        }

        if ($fila['DocTipo']=='NN') {
            $larguito="Pasaporte ".$fila['correo']; 
        } 
        
        $calidad2="";

        if ($fila['id']=='14313211') {
            $calidad2=" Y ASISTENTE"; 
        } 


    if ($fila['tipo']=="DOCENTEU") {  
            echo "<p style='text-align: justify; margin-right: 25px; margin-left: 5px;'>El ".$textoOrganizadores." hace constar que <b>".mb_convert_encoding($fila['nombres'],"UTF-8")."</b>, identificado(a) con ".$larguito." ".$fila['id'].", asistió al ".$textoEvento." en calidad de CONFERENCISTA en representación de la Universidad de Nariño, realizado ".$textoFechaEvento.", con una intensidad de ".$horasAcademicas." horas académicas,  el cual se desarrolló en ".$sitioEvento."</p>";
            }

    if ($fila['tipo']!="CONFERENCISTA" AND $fila['tipo']!="DOCENTEU") {    //PARA ASISTENTE
             echo "<p style='text-align: justify; margin-right: 25px; margin-left: 5px;'>El ".$textoOrganizadores." hace constar que <b>".mb_convert_encoding($fila['nombres'],"UTF-8")."</b>, identificado(a) con ".$larguito." ".$fila['id'].", asistió al ".$textoEvento." en calidad de ".mb_convert_encoding($fila['tipo'],"UTF-8").", realizado ".$textoFechaEvento.", con una intensidad de ".$horasAcademicas." horas académicas,  el cual se desarrolló en ".$sitioEvento."</p>";
            }

    if ($fila['tipo']=="CONFERENCISTA" ) {    
             echo "<p style='text-align: justify; margin-right: 25px; margin-left: 5px;'>El ".$textoOrganizadores." hace constar que el Doctor (a) <b>".mb_convert_encoding($fila['nombres'],"UTF-8")."</b>, identificado(a) con ".$larguito."".$fila['id'].", asistió al ".$textoEvento." en calidad de ".Strtoupper(mb_convert_encoding($fila['tipo'],"UTF-8")).$calidad2.", realizado ".$textoFechaEvento.",  el cual se desarrolló en ".$sitioEvento."</p>";
            }
            echo "<p style='text-align: justify; margin-right: 25px; margin-left: 5px;'>En constancia de lo anterior, se firma en San Juan de Pasto, ".$dia." de ".$mesLargo." de ".$ano."</p><br>";


    if ($fila['id']!="98398521") { 
            echo "<p style='text-align: justify; '><b>LEONARDO A. ENRÍQUEZ MARTÍNEZ</b><br>";
            echo "Decano <br>Facultad de Derecho y Ciencias Políticas <br>Universidad de Nariño</p><br>";
        }

    if ($fila['id']!="13069293") {  
            echo "<p style='text-align: justify;'><b>CRISTHIAN ALEXANDER PEREIRA OTERO</b><br>";
            echo "Director <br>Centro de Investigación y Estudios Socio-Jurídicos<br>Universidad de Nariño</p><br>";
        }

            $intentos = $conec->query("SELECT id FROM descargas WHERE id =".$fila['id']."");
            $totalintentos = $intentos->num_rows;

            if ($totalintentos<50) {
?>
                    <form method="POST" action='phpqrcode/<?php echo $redirección.".php"; ?>' >
                        <div class="row">     
                        <input id="prodId" name="id" type="hidden" value="<?php echo $id; ?>">    
                            <div class="col-md-12 text-center">
                                <button class="btn btn-primary btn-shadow btn-lg" type="submit" name="submit">DESCARGAR CERTIFICADO EN PDF</button>
                            </div>
                        </div>
                    </form>

                     <?php
                    echo "DIGITAL SECURITY</p>";               

}  



if ($totalintentos>5000) {
    session_destroy();
    echo "El máximo de descargas a sido alcanzado";
    }
} 
}
}


if ($row_cnt==0) {
 echo "<center><p style='text-align: justify; margin-right: 25px; margin-left: 5px;'>Este número de identificación no aparece en nuestra base de datos</p></center>";
    if($evento=="X Congreso"){ 
        header("refresh:8;certificar.php");		
    }
    if($evento=="Seminario_ICON-S"){
        header("refresh:8;seminarioICON-S.php");	
    }	

}



//if ($_SERVER['REQUEST_METHOD'] != 'POST') {

  // Procesa los datos del formulario

  // Redirige al usuario a otra página

  //exit();

//}

                    ?>



</div>
</center>

<script>
    if (performance.navigation.type == 1 && window.location.href.indexOf('#') == -1) {
        window.location.href = 'certificar.php';
    }

</script>

