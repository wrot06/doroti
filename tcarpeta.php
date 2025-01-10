<?php
require "rene/conexion3.php";

// Inicialización de variables desde POST
$caja = htmlspecialchars($_POST['caja'] ?? '', ENT_QUOTES, 'UTF-8');
$carpeta = htmlspecialchars($_POST['carpeta'] ?? '', ENT_QUOTES, 'UTF-8');
$folios = max(1, intval($_POST['folios'] ?? 1)); // Asegura que folios nunca sea negativo
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
    <!-- Título con botón "Regresar" -->
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0">Caja <?= $caja ?> | Carpeta <?= $carpeta ?></h5>
        <form action="indice.php" method="post" class="ml-3">
            <input type="hidden" name="Caja" value="<?= $caja ?>">
            <input type="hidden" name="Car2" value="<?= $carpeta ?>">
            <button type="submit" class="btn btn-primary btn-sm">Regresar</button>
        </form>
    </div>

    <!-- Detalles de la Carpeta -->
    <div class="card">
        <div class="card-header">Detalles Carpeta</div>
        <div class="card-body">
            <form action="procesar_tcarpeta.php" method="post">
                <input type="hidden" name="caja" value="<?= $caja ?>">
                <input type="hidden" name="carpeta" value="<?= $carpeta ?>">
                <input type="hidden" id="folios" name="folios" value="<?= $folios ?>">

                <?php
                try {
                    // Realizar la consulta
                    $result = $conec->query("SELECT nombre FROM serie");
                    if (!$result) {
                        throw new Exception("Error en la consulta: " . $conec->error);
                    }

                    // Obtener las etiquetas
                    $etiquetas = array_map('strtoupper', array_column($result->fetch_all(MYSQLI_ASSOC), 'nombre'));

                    // Mostrar el desplegable
                    echo '<div class="form-group">';
                    echo '<label for="tituloSerie">Serie:</label>';
                    echo '<select id="tituloSerie" name="tituloSerie" class="form-control form-control-sm" required>';
                    echo '<option value="">Seleccione una serie</option>'; // Opción por defecto

                    foreach ($etiquetas as $etiqueta) {
                        echo "<option value='" . htmlspecialchars($etiqueta) . "'>" . htmlspecialchars($etiqueta) . "</option>";
                    }

                    echo '</select>';
                    echo '</div>';

                } catch (Exception $e) {
                    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
                }
                ?>

                <div class="form-group">
                    <label for="subserie">Subserie:</label>
                    <select id="subserie" name="subserie" class="form-control form-control-sm" required>
                        <option value="" disabled selected>Seleccione una Subserie</option>
                        <?php
                        $query = $conec->query("SELECT Subs FROM Subs");
                        while ($subserie = $query->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($subserie['Subs']) . "'>" . htmlspecialchars($subserie['Subs']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tituloCarpeta">Título de Carpeta:</label>                    
                    <textarea id="tituloCarpeta" name="tituloCarpeta" class="form-control form-control-sm" placeholder="Ingrese el título de la carpeta" rows="3" required></textarea>
                    <button type="button" id="grabarBoton" class="btn btn-warning btn-sm float-right">Grabar (F2)</button>
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

                <div class="form-group">
                    <label for="totalFolios">Folios: <?= $folios ?></label>
                </div>
                <button type="submit" class="btn btn-success btn-sm">Finalizar Carpeta</button>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<script>
//Inicia el puntero  en el textarea
document.addEventListener('DOMContentLoaded', function() {
        // Enfocar el textarea
        document.getElementById('tituloCarpeta').focus();
});
 

// Grabar voz con el micrófono y convertir a texto
let grabando = false; 
let recognition; 

function iniciarReconocimiento() {
    if (!('webkitSpeechRecognition' in window)) {
        alert("Lo siento, tu navegador no soporta esta función.");
        return;
    }

    recognition = new webkitSpeechRecognition();
    recognition.lang = 'es-ES'; 
    recognition.interimResults = true; 

    recognition.onresult = function(event) {
        for (let i = event.resultIndex; i < event.results.length; i++) {
            const resultado = event.results[i];
            if (resultado.isFinal) {
                const texto = resultado[0].transcript;
                const textarea = document.getElementById('tituloCarpeta');
                textarea.value += texto + " "; 
                textarea.focus();
                textarea.setSelectionRange(textarea.value.length, textarea.value.length);
            }
        }
    };

    recognition.onend = function() {
        if (grabando) {
            recognition.start();
        }
    };

    recognition.start(); 
    grabando = true; 
    document.getElementById('grabarBoton').classList.add('grabando'); 
}

function detenerReconocimiento() {
    if (recognition) {
        recognition.stop(); 
    }
    grabando = false; 
    document.getElementById('grabarBoton').classList.remove('grabando'); 

    const textarea = document.getElementById('tituloCarpeta');
    textarea.focus();
    textarea.setSelectionRange(textarea.value.length, textarea.value.length);
}

document.getElementById('grabarBoton').addEventListener('click', function() {
    if (!grabando) {
        iniciarReconocimiento(); 
    } else {
        detenerReconocimiento(); 
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'F2' && !grabando) { 
        iniciarReconocimiento(); 
    }
});

document.addEventListener('keyup', function(event) {
    if (event.key === 'F2' && grabando) { 
        detenerReconocimiento(); 
    }
});
</script>
</body>
</html>
