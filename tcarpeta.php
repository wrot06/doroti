<?php
require "rene/conexion3.php";

// Verificación de valores enviados por POST
$caja = htmlspecialchars($_POST['caja'] ?? '', ENT_QUOTES, 'UTF-8');
$carpeta = htmlspecialchars($_POST['carpeta'] ?? '', ENT_QUOTES, 'UTF-8');
$folios = max(1, intval($_POST['folios'] ?? 1));

if (empty($caja) || empty($carpeta)) {
    die("Error: Caja o carpeta no definidas.");
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
            <input type="hidden" name="caja" value="<?= $caja ?>">
            <input type="hidden" name="carpeta" value="<?= $carpeta ?>">
            <button type="submit" class="btn btn-primary btn-sm">Regresar</button>
        </form>
    </div>


    <div class="card">
        <div class="card-header">Detalles Carpeta</div>
        <div class="card-body">
            <form action="finalizarcarpeta.php" method="post">
                <input type="hidden" name="caja" value="<?= $caja ?>">
                <input type="hidden" name="carpeta" value="<?= $carpeta ?>">
                <input type="hidden" name="folios" value="<?= $folios ?>">

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
                    <textarea id="tituloCarpeta" name="tituloCarpeta" class="form-control form-control-sm" placeholder="Ingrese el título de la carpeta" rows="3" required style="font-size: 1.2rem; height: 150px;"></textarea>
                    <div class="text-right mt-3">
                        <button type="button" id="grabarBoton" class="btn btn-warning btn-sm">Grabar Voz (F2)</button>
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
                try {
                    $sql = "SELECT MAX(NoFolioFin) AS ultima_pagina FROM IndiceTemp WHERE caja = ? AND carpeta = ?";
                    $stmt = $conec->prepare($sql);
                    $stmt->bind_param("ii", $caja, $carpeta);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $ultimaPagina = $result->fetch_assoc()['ultima_pagina'] ?? 0;
                } catch (Exception $e) {
                    error_log("Error al ejecutar la consulta: " . $e->getMessage());
                } finally {
                    if (isset($stmt)) {
                        $stmt->close();
                    }
                    if (isset($conec)) {
                        $conec->close();
                    }
                }
                ?>

                <div class="form-group">
                    <label for="totalFolios">Folios: <?= $ultimaPagina ?></label>
                </div>

                <button type="submit" class="btn btn-success btn-sm">Finalizar Carpeta</button>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function () {
    $('#serie').change(function () {
        var serieNombre = $(this).find("option:selected").text(); // Obtener el nombre de la serie seleccionada
        $('#subserie').empty(); // Limpiar el select de subseries
        $('#subserie').append('<option value="" selected>Seleccione una Subserie</option>');

        if (serieNombre) {
            $.ajax({
                url: 'rene/get_subseries.php', // Archivo PHP que obtendrá las subseries relacionadas
                type: 'POST',
                data: { serie_id: serieNombre }, // Enviar el nombre de la serie
                success: function (response) {
                    var subseries = JSON.parse(response); // Parsear la respuesta JSON
                    if (Array.isArray(subseries)) {
                        subseries.forEach(function (subserie) {
                            $('#subserie').append(
                                '<option value="' + subserie.nombre + '">' + subserie.nombre + '</option>'
                            );
                        });
                    } else {
                        alert(subseries.error); // Mostrar error si existe
                    }
                },
                error: function () {
                    alert('Error al cargar las subseries. Inténtalo de nuevo más tarde.');
                }
            });
        }
    });
});




document.addEventListener('DOMContentLoaded', function () {
    const finalizarBtn = document.querySelector('button[type="submit"]'); // Botón "Finalizar Carpeta"
    const form = document.querySelector('form'); // Formulario actual

    finalizarBtn.addEventListener('click', function (event) {
        event.preventDefault(); // Evitar el envío automático del formulario

        // Obtener los valores de Caja y Carpeta del formulario
        const formData = new FormData(form);
        const caja = formData.get('caja'); // Valor de Caja
        const carpeta = formData.get('carpeta'); // Valor de Carpeta

        // Redirigir directamente a indice.php con los parámetros
        const url = new URL('Doroti/indice.php', window.location.origin);
   

        // Redirigir a la URL generada
        window.location.href = url;
    });

    // Mostrar alerta de confirmación al finalizar carpeta
    const fechaInicialInput = document.getElementById('fechaInicial');
    const fechaFinalInput = document.getElementById('fechaFinal');

    const validarFechaActual = (input) => {
        const fechaActual = new Date().toISOString().split('T')[0];
        if (input.value > fechaActual) {
            alert('La fecha ingresada no puede ser mayor a la fecha actual.');
            input.value = '';
            input.focus();
        }
    };

    const validarRangoFechas = () => {
        if (fechaInicialInput.value && fechaFinalInput.value) {
            if (fechaInicialInput.value > fechaFinalInput.value) {
                alert('La fecha final no puede ser menor que la fecha inicial.');
                fechaFinalInput.value = ''; // Borra la fecha final si es menor
                fechaFinalInput.focus();
            }
        }
    };

    fechaInicialInput.addEventListener('change', function () {
        validarFechaActual(fechaInicialInput);
        validarRangoFechas();
    });

    fechaFinalInput.addEventListener('change', function () {
        validarFechaActual(fechaFinalInput);
        validarRangoFechas();
    });

    // Inicia el puntero en el textarea
    document.getElementById('tituloCarpeta').focus();

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
});
</script>
</body>
</html>