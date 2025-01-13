<?php
require "rene/conexion3.php";

$caja = htmlspecialchars($_POST['caja'] ?? '', ENT_QUOTES, 'UTF-8');
$carpeta = htmlspecialchars($_POST['carpeta'] ?? '', ENT_QUOTES, 'UTF-8');
$car2 = htmlspecialchars($_POST['Car2'] ?? '', ENT_QUOTES, 'UTF-8'); // Nueva variable
$folios = max(1, intval($_POST['folios'] ?? 1));
$Serie = htmlspecialchars($_POST['Serie'] ?? '', ENT_QUOTES, 'UTF-8');
$subs = htmlspecialchars($_POST['subserie'] ?? '', ENT_QUOTES, 'UTF-8');
$tituloCarpeta = htmlspecialchars($_POST['tituloCarpeta'] ?? '', ENT_QUOTES, 'UTF-8');
$fechaInicial = htmlspecialchars($_POST['fechaInicial'] ?? '', ENT_QUOTES, 'UTF-8');
$fechaFinal = htmlspecialchars($_POST['fechaFinal'] ?? '', ENT_QUOTES, 'UTF-8');
$estado = 'A'; // Asignar 'A' a la variable estado

try {
    $stmt = $conec->prepare("INSERT INTO carpetas (Caja, Carpeta, Car2, Serie, Subs, Titulo, FInicial, FFinal, Folios, Estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Cadena de tipos:
    // - Caja: int (i)
    // - Carpeta: int (i)
    // - Car2: int (i)
    // - Serie: string (s)
    // - Subs: string (s)
    // - Titulo: string (s)
    // - FInicial: string (s)
    // - FFinal: string (s)
    // - Folios: int (i)
    // - Estado: string (s)
    $stmt->bind_param("iiisssssis", $caja, $carpeta, $car2, $Serie, $subs, $tituloCarpeta, $fechaInicial, $fechaFinal, $folios, $estado);
    
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Carpeta finalizada con éxito.</div>";
    } else {
        throw new Exception("Error al finalizar la carpeta: " . $stmt->error);
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
} finally {
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
    $conec->close();
}
?>

<a href="indice.php" class="btn btn-primary">Regresar</a>
