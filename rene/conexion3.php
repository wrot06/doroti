<?php
$servername = "localhost"; // Cambia esto si es necesario
$username = "UArchivoCIESJU";
$password = "123UCiesjuArchivo";
$dbname = "ArchivoCIESJU";

// Crear conexión
$conec = new mysqli($servername, $username, $password, $dbname);

// Comprobar conexión
if ($conec->connect_error) {
    die("Connection failed: " . $conec->connect_error);
}

// No cerrar la conexión aquí si necesitas usarla en otras partes del script
// echo "Conexión exitosa"; // Puedes mostrar un mensaje si es necesario
?>






	
