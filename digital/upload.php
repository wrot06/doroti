<?php
declare(strict_types=1);
session_start();
require_once "../rene/conexion3.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Verificar autenticación
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuarioId       = $_SESSION['user_id'] ?? null;
    $serie           = trim($_POST["serie"] ?? '');
    $fechaCreacion   = trim($_POST["fecha_creacion"] ?? '');
    $tituloDocumento = trim($_POST["titulodocumento"] ?? '');
    $archivo         = $_FILES["archivo"] ?? null;

    if (
        !$usuarioId || $serie === '' || $fechaCreacion === '' ||
        $tituloDocumento === '' || empty($archivo) || !isset($archivo["tmp_name"])
    ) {
        die("Faltan datos obligatorios.");
    }

    if (!DateTime::createFromFormat('Y-m-d', $fechaCreacion)) {
        die("Formato de fecha inválido (use YYYY-MM-DD).");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $tipoArchivo = $finfo->file($archivo["tmp_name"]);
    if ($tipoArchivo !== "application/pdf") {
        die("Solo se permiten archivos PDF.");
    }

    if ($archivo["size"] > 10 * 1024 * 1024) {
        die("El archivo excede el tamaño permitido (10MB).");
    }

    if ($archivo["error"] !== UPLOAD_ERR_OK) {
        die("Error al subir el archivo.");
    }

    // Carpeta donde guardar archivos
    $directorio = __DIR__ . "/tmp";
    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    $nombreOriginal = basename($archivo["name"]);
    $nombreSanitizado = preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $nombreOriginal);
    $nombreFinal = uniqid("doc_", true) . "_" . $nombreSanitizado;
    $rutaRelativa = "tmp/" . $nombreFinal;
    $rutaCompleta = $directorio . "/" . $nombreFinal;

    if (!move_uploaded_file($archivo["tmp_name"], $rutaCompleta)) {
        die("No se pudo mover el archivo.");
    }

    $stmt = $conec->prepare("
        INSERT INTO Documentos (
            serie, fecha_creacion, archivo_nombre,
            archivo_ruta, user_id, titulo_documento, archivo_pdf
        ) VALUES (?, ?, ?, ?, ?, ?, NULL)
    ");
    $stmt->bind_param(
        "ssssis",
        $serie,
        $fechaCreacion,
        $nombreOriginal,
        $rutaRelativa,
        $usuarioId,
        $tituloDocumento
    );
    $stmt->execute();

    header("Location: documents.php?mensaje=Documento_subido");
    exit();
}
?>
