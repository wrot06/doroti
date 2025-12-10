<?php
declare(strict_types=1);
session_start();
require_once "rene/conexion3.php";

// Función de redirección
function redirect(string $url): void {
    header("Location: $url");
    exit();
}

// Verificar autenticación
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir documento PDF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .grabando { background-color: #dc3545 !important; color: white; }
    </style>
</head>
<body class="container py-5">

    <!-- Menú superior -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Subir nuevo documento</h2>
        <a href="documents.php" class="btn btn-secondary">← Volver al listado</a>
    </div>

    <!-- Formulario -->
    <form action="upload.php" method="post" enctype="multipart/form-data" class="border p-4 rounded shadow bg-light">

        <!-- Serie -->
        <div class="mb-3">
            <label for="serie" class="form-label">Serie:</label>
            <select id="serie" name="serie" class="form-select" required>
                <option value="" disabled selected>Seleccione una Serie</option>
                <?php
                    $query = $conec->query("SELECT id, nombre FROM Serie ORDER BY nombre ASC");
                    if ($query) {
                        while ($serie = $query->fetch_assoc()) {
                            $nombre = htmlspecialchars(mb_strtoupper($serie['nombre'], 'UTF-8'));
                            echo "<option value=\"{$nombre}\">{$nombre}</option>";
                        }
                    }
                ?>
            </select>
        </div>

        <!-- Fecha de creación -->
        <div class="mb-3">
            <label for="fecha_creacion" class="form-label">Fecha de creación:</label>
            <input type="date" name="fecha_creacion" id="fecha_creacion" class="form-control" max="<?= date('Y-m-d') ?>" required>
        </div>

        <!-- Título del documento -->
        <div class="mb-3">
            <label for="titulodocumento" class="form-label">Título del documento:</label>
            <textarea id="titulodocumento" name="titulodocumento" class="form-control" maxlength="156" rows="2" required placeholder="Descripción"></textarea>
            <div class="text-end mt-2">
                <button type="button" id="grabarBoton" class="btn btn-warning btn-sm">🎙️ Grabar Voz (F2)</button>
            </div>
        </div>

        <!-- Archivo PDF -->
        <div class="mb-4">
            <label for="archivo" class="form-label">Documento PDF:</label>
            <input type="file" name="archivo" id="archivo" accept="application/pdf" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">📤 Subir documento</button>
    </form>

    <!-- Scripts -->
    <script>
const titulo = document.getElementById('titulodocumento');
titulo.addEventListener('input', function () {
    if (this.value.length > 0) {
        this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
    }
    if (this.value.length > 156) {
        this.value = this.value.substring(0, 156);
    }
});

let recognition;
let grabando = false;

function iniciarReconocimiento() {
    if (!('webkitSpeechRecognition' in window)) {
        alert("Tu navegador no soporta reconocimiento de voz.");
        return;
    }

    recognition = new webkitSpeechRecognition();
    recognition.lang = 'es-ES';
    recognition.interimResults = false;

    recognition.onresult = function (event) {
        const texto = event.results[0][0].transcript;
        const area = document.getElementById('titulodocumento');
        const espacioLibre = 156 - area.value.length;
        area.value += texto.substring(0, espacioLibre) + " ";
    };

    recognition.onend = function () {
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
}

document.getElementById('grabarBoton').addEventListener('click', function () {
    grabando ? detenerReconocimiento() : iniciarReconocimiento();
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'F2' && !grabando) {
        e.preventDefault();
        iniciarReconocimiento();
    }
});

document.addEventListener('keyup', function (e) {
    if (e.key === 'F2' && grabando) {
        detenerReconocimiento();
    }
});
</script>

</body>
</html>
