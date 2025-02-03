<?php
ob_start();

// Verificar si la sesión ya ha sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Iniciar la sesión si no está activa
}

// Configuración de la base de datos
$servername = "localhost"; // Cambia esto si es necesario
$username = "UArchivoCIESJU"; // Asegúrate de que el usuario tiene permisos
$password = "123UCiesjuArchivo"; // Cambia esto a tu contraseña real
$dbname = "ArchivoCIESJU"; // Asegúrate de que la base de datos existe

// Crear conexión
$conec = new mysqli($servername, $username, $password, $dbname);

// Comprobar conexión
if ($conec->connect_error) {
    die("Connection failed: " . $conec->connect_error); // Muestra un error si la conexión falla
}

// No cerrar la conexión aquí si necesitas usarla en otras partes del script
// echo "Conexión exitosa"; // Puedes mostrar un mensaje si es necesario
?>

	
