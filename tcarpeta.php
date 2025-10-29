<?php
require "rene/conexion3.php";

// Verificación de valores enviados por POST
$caja = htmlspecialchars($_POST['caja'] ?? '', ENT_QUOTES, 'UTF-8');
$carpeta = htmlspecialchars($_POST['carpeta'] ?? '', ENT_QUOTES, 'UTF-8');
$folios = max(1, intval($_POST['folios'] ?? 1));

if (empty($caja) || empty($carpeta)) {
    die("Error: Caja o carpeta no definidas.");
}


$ultimaPagina = 0;

try {
    $sql = "SELECT MAX(NoFolioFin) AS ultima_pagina FROM IndiceTemp WHERE caja = ? AND carpeta = ?";
    $stmt = $conec->prepare($sql);
    $stmt->bind_param("ii", $caja, $carpeta);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($fila = $result->fetch_assoc()) {
        $ultimaPagina = intval($fila['ultima_pagina'] ?? 0);
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error al obtener última página: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="img/hueso.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carpeta</title>
    <link rel="stylesheet" href="css/estiloindice.css">
    <link rel="stylesheet" href="css/botongrabar.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body >
<div class="container mt-4" style="padding: 20px; border-radius: 8px;">
    <div class="card" style="background-color: #C6D2FF; line-height: 1.2;">
        <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #C6D2FF;">
            <h5 class="mb-0">Caja <?= $caja ?> - Carpeta <?= $carpeta ?></h5>
            <form action="indice.php" method="post" class="mb-0">
                <input type="hidden" name="consulta" value="<?= $caja ?>">
                <input type="hidden" name="carpeta" value="<?= $carpeta ?>">
                <button type="submit" class="btn btn-primary btn-sm">Regresar</button>
            </form>
        </div>

        <div class="card-body">
            <form action="rene/finalizarcarpeta.php" method="post">
                <input type="hidden" name="caja" value="<?= $caja ?>">
                <input type="hidden" name="carpeta" value="<?= $carpeta ?>">

                <div class="form-group">
                    <label for="serie">Serie:</label>
                    <select id="serie" name="serie" class="form-control form-control-sm" required>
                        <option value="" disabled selected>Seleccione una Serie</option>
                        <?php
                        $query = $conec->query("SELECT id, nombre FROM Serie ORDER BY nombre ASC");
                        if ($query) {
                            while ($serie = $query->fetch_assoc()) {
                                $serieNombre = htmlspecialchars(mb_strtoupper($serie['nombre'], 'UTF-8'));
                                echo "<option value='{$serieNombre}'>{$serieNombre}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subserie">Subserie:</label>
                    <select id="subserie" name="subserie" class="form-control form-control-sm">
                        <option value="" selected>Seleccione una Subserie</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tituloCarpeta">Título de Carpeta:</label>
                    <textarea id="tituloCarpeta" name="tituloCarpeta"
                        class="form-control form-control-sm"
                        placeholder="Ingrese el título de la carpeta"
                        rows="3" required maxlength="56"
                        style="font-size: 1.2rem; height: 70px;"
                        onkeypress="return event.charCode < 32 || this.value.length < 56"></textarea>
                    <div class="text-right mt-3">
                        <button type="button" id="grabarBoton" class="btn btn-warning btn-sm">Grabar(F2-F9)</button>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="fechaInicial" class="h6 font-weight-bold">Fecha Inicial:</label>
                        <input type="date" id="fechaInicial" name="fechaInicial"
                            class="form-control form-control-sm" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="fechaFinal" class="h6 font-weight-bold">Fecha Final:</label>
                        <input type="date" id="fechaFinal" name="fechaFinal"
                            class="form-control form-control-sm" required>
                    </div>
                </div>

                <input type="hidden" name="folios" value="<?= $ultimaPagina ?>">

                <div class="form-group">
                    <label for="totalFolios">Folios: <?= $ultimaPagina ?></label>
                </div>

                <?php if ($ultimaPagina < 1): ?>
                    <div class="alert alert-warning">
                        No se puede finalizar la carpeta sin haber agregado al menos un capítulo.
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" disabled>
                        Finalizar Carpeta
                    </button>
                <?php else: ?>
                    <button type="submit" class="btn btn-success btn-lg btn-block font-weight-bold">
                        Finalizar Carpeta
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script type="module" src="js/finalizarCarpeta.js"></script>
<script src="js/voz.js"></script>
<script src="js/validacionesFecha.js"></script>
<script src="js/subseries.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const textarea = document.getElementById('tituloCarpeta');

    // Capitalizar y limitar
textarea.addEventListener('blur', function () {
    // Cuando el usuario termine de escribir (al salir del campo)
    if (this.value.trim().length > 0) {
        this.value = this.value.trim();
        this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
    }
});


    // Inicializar funciones individuales
    configurarFechas('fechaInicial', 'fechaFinal');
    configurarSubseries('serie', 'subserie');
    iniciarVoz('tituloCarpeta', 'grabarBoton');

    // Enfocar al cargar
    textarea.focus();
});
</script>




</body>
</html>