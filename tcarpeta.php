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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carpeta</title>
    <link rel="stylesheet" href="css/estiloindice.css">
    <link rel="stylesheet" href="css/botongrabar.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0">Caja <?= $caja ?> | Carpeta <?= $carpeta ?></h5>
        
    <form action="indice.php" method="post" class="ml-3">
        <input type="hidden" name="consulta" value="<?= $caja ?>"> <!-- ¡este nombre es clave! -->
        <input type="hidden" name="carpeta" value="<?= $carpeta ?>">
        <button type="submit" class="btn btn-primary btn-sm">Regresar</button>
    </form>

    </div>


    <div class="card" style="background-color:rgb(235, 255, 216); line-height: 1.2;">
        <div class="card-header">Detalles Carpeta</div>
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
                                $serieId = htmlspecialchars($serie['id']);
                                $serieNombre = htmlspecialchars(mb_strtoupper($serie['nombre'], 'UTF-8'));
                                echo "<option value='{$serieNombre}'>{$serieNombre}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subserie">Subserie:</label>
                    <select id="subserie" name="subserie" class="form-control form-control-sm" >
                        <option value="" selected>Seleccione una Subserie</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tituloCarpeta">Título de Carpeta:</label>
                    <textarea id="tituloCarpeta" name="tituloCarpeta" class="form-control form-control-sm" placeholder="Ingrese el título de la carpeta" rows="3" required style="font-size: 1.2rem; height: 70px;" maxlength="56" onkeypress="return event.charCode < 32 || this.value.length < 56"></textarea>
                    <div class="text-right mt-3">
                        <button type="button" id="grabarBoton" class="btn btn-warning btn-sm">Grabar(F2-F9)</button>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="fechaInicial">Fecha Inicial:</label>
                        <input type="date" id="fechaInicial" name="fechaInicial" class="form-control form-control-sm" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="fechaFinal">Fecha Final:</label>
                        <input type="date" id="fechaFinal" name="fechaFinal" class="form-control form-control-sm" required>
                    </div>
                </div>

                <?php
                // Consultar la última página
                $ultimaPagina = 0; // Valor por defecto

                try {
                    $sql = "SELECT MAX(NoFolioFin) AS ultima_pagina FROM IndiceTemp WHERE caja = ? AND carpeta = ?";
                    $stmt = $conec->prepare($sql);
                    $stmt->bind_param("ii", $caja, $carpeta);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($fila = $result->fetch_assoc()) {
                        $ultimaPagina = intval($fila['ultima_pagina'] ?? 0);
                    }
                } catch (Exception $e) {
                    error_log("Error al ejecutar la consulta: " . $e->getMessage());
                } finally {
                    if (isset($stmt)) $stmt->close();
                    if (isset($conec)) $conec->close();
                }

                ?>

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
                <button type="submit" class="btn btn-sm" style="background-color: #28a745; color: black; font-weight: bold;" onmouseover="this.style.backgroundColor='#218838'" onmouseout="this.style.backgroundColor='#28a745'">
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
    textarea.addEventListener('input', function () {
        this.value = this.value.slice(0, 56);
        if (this.value.length > 0) {
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