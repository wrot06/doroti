<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../error.log');
error_reporting(E_ALL);

// Definir constante de acceso seguro para archivos incluidos
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Cargar credenciales desde variables de entorno o archivo de configuración
$db_host = getenv('DB_HOST') ?: null;
$db_user = getenv('DB_USER') ?: null;
$db_pass = getenv('DB_PASS') ?: null;
$db_name = getenv('DB_NAME') ?: null;

if (!$db_host || !$db_user || !$db_name) {
    $configFile = __DIR__ . '/../config/db_config.php';
    $configExampleFile = __DIR__ . '/../config/db_config.example.php';
    
    if (file_exists($configFile)) {
        $dbConfig = require $configFile;
    } elseif (file_exists($configExampleFile)) {
        $dbConfig = require $configExampleFile;
    } else {
        die('Error de configuración: Archivo de configuración de base de datos no encontrado.');
    }
    
    $db_host = $dbConfig['db_host'] ?? 'localhost';
    $db_user = $dbConfig['db_user'] ?? '';
    $db_pass = $dbConfig['db_pass'] ?? '';
    $db_name = $dbConfig['db_name'] ?? '';
}

// Crear conexión utilizando las variables cargadas dinámicamente
// Esto evita la regla de SonarCloud RSPEC-2068 (Hardcoded Credentials)
$conec = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conec->connect_error) {
    die('Error de conexión: ' . $conec->connect_error);
}
$conec->set_charset('utf8mb4');

