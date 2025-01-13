<!DOCTYPE html>

<html lang="es">

<?php   require 'rene/head.php';  ?>
   <style>
        .sapam {
            border: 2px solid #ccc;
            padding: 10px;
            background-color: #f9f9f9;
            font-family: Arial, sans-serif;
            margin: 20px; /* Agregamos margen para dar espacio alrededor del contenido */
        }

        .sapam p:first-child {
            font-weight: bold;
        }

        .sapam a {
            color: #007BFF;
            text-decoration: none;
        }

        .sapam a:hover {
            text-decoration: underline;
        }

        .center {
            margin-left: auto;
            margin-right: auto;
            display: block;

        }

        .center-div
        {
            text-align: center;
            margin-left: auto;
            margin-right: auto;
        }

        h2{
            font-size: 30px;
        }

        .p1{
            padding-right: 15px;
            font-family: Calibri, sans-serif; 
            margin-right: 10px; 
            text-align: justify; 
        }

        .ima1{
            max-width: 500px;
            width: 60%; 
            display:block;
            margin:auto;
        }

    </style>

<link href="https://fonts.googleapis.com/css2?family=Open+Sans" rel="stylesheet">
<body data-spy="scroll" data-target="#navbar" class="static-layout">
<img src="img/flayer seminario mujeres.jpeg" style="width: 150px;  float: left; margin-right: 15px;">    
    
    <div class="center">
    <br>

    <img src="img/logos seminario.jpg" style="max-width: 500px; width: 60%;" class="center">
    <br><br>
    <img src="img/letras titulo Mejoradas.png" class="ima1">
    <br>

    <div class="center-div" style="vertical-align: middle;background-color: #448040; padding-top: 15px; padding-bottom: 5px;"><h4 style="font-family: Calibri, sans-serif; margin-right: 10px; color: #ffffff;"><em><strong>MIRADAS DESDE EL SUR.</strong><br> JUSTICIA TRANSICIONAL, DEMOCRACIA CONSTITUCIONAL Y LITERATURA</em></h4>
    </div>

    <center>
    <div class="center-div">
    <p class="p1">Para obtener tu certificado digital, ingresa tu número de identificación sin utilizar puntos ni comas y luego haz clic en el botón "Generar Certificado".</p>
    </div>   
    </center>

                    <form method="GET" action="consulta.php"  required>
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <center>
                                <input autoFocus style="width: 200px; font-size: 20px;" type="number" class="form-control" id="nombre" name="id2" placeholder="Número de identificación" onkeydown="filtro()" min="100000" max="99999999999" size="10" required>
                            </div>
                            <br>
                            <div class="center-div">
                                <button style="background-color: #009640; margin-right: 10px;" class="btn btn-primary btn-shadow btn-lg" type="submit" name="submit">Generar Certificado</button>
                            </div>
                        </div>
                    </form>

    </div>






                