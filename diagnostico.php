<?php
header('Content-Type: text/plain');

$host = "sql103.infinityfree.com";
$user = "if0_38210727";
$pass = "yBxDZHWJ45vhR"; // Tomado de tu configuración SFTP

echo "--------------------------------------------------\n";
echo " DIAGNÓSTICO DE CONEXIÓN A BASE DE DATOS \n";
echo "--------------------------------------------------\n";
echo "Intentando conectar con:\n";
echo "Host: $host\n";
echo "User: $user\n";
echo "Pass: **** (oculto)\n\n";

$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    echo "❌ ERROR DE CONEXIÓN: " . $conn->connect_error . "\n";
    echo "\nPosibles causas:\n";
    echo "1. La contraseña en sftp.json no es la misma que la del panel de control/MySQL.\n";
    echo "2. El servidor de base de datos está inactivo o bloqueando la conexión.\n";
} else {
    echo "✅ ¡CONEXIÓN EXITOSA AL SERVIDOR MYSQL!\n\n";
    
    echo "Listando bases de datos disponibles:\n";
    $result = $conn->query("SHOW DATABASES");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo " - " . $row['Database'] . "\n";
        }
    } else {
        echo "⚠️ No se pudieron listar las bases de datos.\n";
    }
    
    $conn->close();
}

echo "\n--------------------------------------------------\n";
echo "Verifica también la estructura de carpetas:\n";
if (file_exists('rene')) {
    echo "📁 Carpeta 'rene' encontrada en este directorio.\n";
    if (file_exists('rene/conexion3.php')) {
        echo "   ✅ 'rene/conexion3.php' existe.\n";
    } else {
        echo "   ❌ 'rene' existe pero 'conexion3.php' no está dentro.\n";
    }
} else {
    echo "❌ Carpeta 'rene' NO encontrada en este directorio (htdocs).\n";
    echo "   Esto explica el error 'open_basedir' si intentas acceder con '../rene'.\n";
}
?>
