<!DOCTYPE html>

<html lang="es">

<?php   

    require 'rene/head.php';    
    require "rene/coneicon.php";


    $sql ="SELECT * FROM participantes" ;
    $resultado = $conec->query("SELECT * FROM participantes");  
    $row_cnt = $resultado->num_rows; 


if ($resultado = $conec->query($sql)) {
    while ($fila = $resultado->fetch_assoc()) { 

        echo $fila['DocTipo'];    
        
        echo $fila['id'];

        echo "<br>";
    }
}