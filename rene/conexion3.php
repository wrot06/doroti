<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'sql103.infinityfree.com';
$usuario = 'if0_38210727';
$contraseña = 'yBxDZHWJ45vhR';
$base_datos = 'if0_38210727_doroti';

$conec = new mysqli($host, $usuario, $contraseña, $base_datos);
if ($conec->connect_error) {
    die('Error de conexión: ' . $conec->connect_error);
}
$conec->set_charset('utf8mb4');
?>
