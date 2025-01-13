<?php
require "rene/conexion3.php";

$caja = htmlspecialchars($_POST['caja'] ?? '', ENT_QUOTES, 'UTF-8');
$carpeta = htmlspecialchars($_POST['carpeta'] ?? '', ENT_QUOTES, 'UTF-8');
$car2 = $carpeta; 
$folios = max(1, intval($_POST['folios'] ?? 1));
$Serie = htmlspecialchars($_POST['serie'] ?? '', ENT_QUOTES, 'UTF-8'); // Cambiado a 'serie'
$subs = htmlspecialchars($_POST['subserie'] ?? '', ENT_QUOTES, 'UTF-8');
$tituloCarpeta = htmlspecialchars($_POST['tituloCarpeta'] ?? '', ENT_QUOTES, 'UTF-8');
$estado = 'C'; 

$fechaInicial = htmlspecialchars($_POST['fechaInicial'] ?? '', ENT_QUOTES, 'UTF-8');
$fechaFinal = htmlspecialchars($_POST['fechaFinal'] ?? '', ENT_QUOTES, 'UTF-8');

// Depuración
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Validar que todos los campos requeridos están completos
$requiredFields = ['caja', 'carpeta', 'folios', 'serie', 'tituloCarpeta', 'fechaInicial', 'fechaFinal'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        echo "<div class='alert alert-danger'>Error: Todos los campos deben ser completados.</div>";
        exit;
    }
}

// Validar el formato de las fechas
if (!DateTime::createFromFormat('Y-m-d', $fechaInicial) || !DateTime::createFromFormat('Y-m-d', $fechaFinal)) {
    echo "<div class='alert alert-danger'>Error: Las fechas no son válidas. Formato esperado: YYYY-MM-DD.</div>";
    exit;
}

// Resto del código para preparar y ejecutar la consulta...




try {
    // Prepara la consulta
    $stmt = $conec->prepare("UPDATE carpetas SET Car2 = ?, Serie = ?, Subs = ?, Titulo = ?, FInicial = ?, FFinal = ?, Folios = ?, Estado = ? WHERE Caja = ? AND Car2 = ?
");
    
    // Vincula los parámetros
    // El primer "s" en "ssssiissis" es para el primer parámetro ($car2) y el segundo es para $Serie, $subs, $tituloCarpeta, etc. 
    // Aquí estás tratando las fechas como strings, que es correcto.
    $stmt->bind_param("ssssssssii", $car2, $Serie, $subs, $tituloCarpeta, $fechaInicial, $fechaFinal, $folios, $estado, $caja, $car2);


    // Ejecuta la consulta
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Carpeta actualizada con éxito.</div>";
    } else {
        throw new Exception("Error al actualizar la carpeta: " . $stmt->error);
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
