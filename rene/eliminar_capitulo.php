<?php
require "conexion3.php"; // Asegúrate de incluir tu archivo de conexión

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit();
}

if (isset($_POST['cerrar_seccion'])) {
    session_destroy();
    header("Location: login.php"); // Redirige a la página deseada
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['caja'] = $_POST['Caja'];
    $_SESSION['car2'] = $_POST['Car2'];
}

// Acceso a las variables de sesión
$caja = $_SESSION['caja'] ?? null;
$carpeta = $_SESSION['car2'] ?? null;

if ($conec->connect_error) {
    die("Error de conexión: " . $conec->connect_error);
}

// Consulta para obtener todos los capítulos para la Caja y Carpeta especificadas
$sql_get_capitulos = "SELECT id2, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin FROM IndiceTemp WHERE Caja = ? AND Carpeta = ?";
$stmt_get_capitulos = $conec->prepare($sql_get_capitulos);
$stmt_get_capitulos->bind_param("ii", $caja, $carpeta);
$stmt_get_capitulos->execute();
$result_capitulos = $stmt_get_capitulos->get_result();

// Almacenar los capítulos en un arreglo
$capitulos = [];
$ultimaPagina = 1; // Inicializa la última página

while ($row = $result_capitulos->fetch_assoc()) {
    $capitulos[] = $row;
    // Actualiza la última página
    $ultimaPagina = max($ultimaPagina, $row['NoFolioFin']);
}

$stmt_get_capitulos->close();
$conec->close();