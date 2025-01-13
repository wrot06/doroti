<!DOCTYPE html>
<html lang="en">




<?php   
require 'rene/head.php';  
require "rene/conexion.php";
?>

<style>
  .mi-tabla {
    border: 1px solid #ddd; /* Agrega un borde de 1 píxel y un color gris claro */
    border-collapse: collapse; /* Combina las celdas de la tabla en una sola línea */
    margin: 20px; /* Agrega un margen de 20 píxeles alrededor de la tabla */
  }
  .mi-tabla th, .mi-tabla td {
    border: 1px solid #ddd; /* Agrega un borde de 1 píxel y un color gris claro a todas las celdas */
    padding: 8px; /* Agrega un relleno de 8 píxeles a todas las celdas */
    text-align: left; /* Alinea el texto en la celda a la izquierda */
  }
  .mi-tabla th {
    background-color: #f2f2f2; /* Agrega un color de fondo gris claro a las celdas de encabezado */
  }
</style>




<body data-spy="scroll" data-target="#navbar" class="static-layout"><br><br>


<?php
$sql = "SELECT * FROM participantes ORDER BY celular, tipo, id ASC ";
$resultado = mysqli_query($conec, $sql);
$contador=0;
?>




    <div class="title-wrap">


    <p>X Congreso Internacional de Derecho Constitucional.</p>
    <p>Listado General</p>
    

                    
                        <div class="row">
|
                            <div class="col-md-12 form-group">
                                <center>

<table class="mi-tabla">
  <tr>
    <th>Numero</th>
    <th>Tipo</th>
    <th>Identificación</th>
    <th>Nombres y Apellidos</th>
    <th>Modalidad</th>
    <th>Lista</th>
  </tr>
  <?php
  while ($fila = mysqli_fetch_assoc($resultado)) {
    ?>
    <tr>
        <td><?php echo  $contador=$contador+1; ?></td>
      <td><?php echo  $fila["DocTipo"]; ?></td>
      <td><?php echo utf8_encode( $fila["id"]); ?></td>
      <td><?php echo $fila["nombres"]; ?></td>
      <td><?php echo utf8_encode($fila["tipo"]); ?></td>
      <td><?php echo utf8_encode($fila["recibo"]); ?></td>
      <td><?php echo "Listado ".utf8_encode($fila["celular"]); ?></td>
    </tr>
    <?php
  }
  ?>
</table>





                                </center>
                            </div>
                                                    
                            

                        </div>
                    
</div>


                