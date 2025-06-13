<?php
declare(strict_types=1);
session_start();
require_once "rene/conexion3.php";

// 1) Verificar que el usuario esté autenticado
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 2) Recoger y validar campos del formulario
    $usuarioId       = $_SESSION['user_id'] ?? null;
    $serie           = trim($_POST["serie"]         ?? '');
    $fechaCreacion   = trim($_POST["fecha_creacion"] ?? '');
    $tituloDocumento = trim($_POST["titulodocumento"] ?? '');
    $archivo         = $_FILES["archivo"]           ?? null;

    // Comprobar que llegan todos los datos obligatorios
    if (
        !$usuarioId ||
        $serie === '' ||
        $fechaCreacion === '' ||
        $tituloDocumento === '' ||
        empty($archivo) ||
        !isset($archivo["tmp_name"])
    ) {
        die("Faltan datos obligatorios.");
    }

    // 3) Validar que sea un PDF (tipo MIME)
    $finfo       = new finfo(FILEINFO_MIME_TYPE);
    $tipoArchivo = $finfo->file($archivo["tmp_name"]);
    if ($tipoArchivo !== "application/pdf") {
        die("Solo se permiten archivos PDF.");
    }

    if ($archivo["error"] !== UPLOAD_ERR_OK) {
        die("Error al subir el archivo (código: {$archivo['error']}).");
    }

    // 4) Leer el contenido binario crudo del PDF
    $pdfBinario = file_get_contents($archivo["tmp_name"]);
    if ($pdfBinario === false) {
        die("No se pudo leer el contenido del PDF.");
    }

    // 5) Sanitizar el nombre original del archivo (solo como metadato)
    $nombre_archivo = basename($archivo["name"]);
    $nombre_archivo = preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $nombre_archivo);

    // 6) Preparar consulta con parámetro BLOB
    $sql = "
      INSERT INTO Documentos
        (serie, fecha_creacion, archivo_nombre, archivo_ruta, user_id, titulo_documento, archivo_pdf)
      VALUES
        (?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conec->prepare($sql);
    if (!$stmt) {
        die("Error al preparar la consulta: " . $conec->error);
    }

    // 7) Bind de parámetros:
    // s = string, i = integer, b = blob
    // Para archivo_ruta utilizamos cadena vacía ("")
    $emptyRuta = ""; 
    $vacioBlob = null; // placeholder para el BLOB

    $stmt->bind_param(
        "ssssisb",
        $serie,            // s: serie
        $fechaCreacion,    // s: fecha_creacion
        $nombre_archivo,   // s: archivo_nombre
        $emptyRuta,        // s: archivo_ruta (cadena vacía)
        $usuarioId,        // i: user_id
        $tituloDocumento,  // s: titulo_documento
        $vacioBlob         // b: archivo_pdf (se envía con send_long_data)
    );

    // 8) Enviar el contenido binario crudo del PDF (índice 6 → 0-based, séptimo parámetro)
    $stmt->send_long_data(6, $pdfBinario);

    // 9) Ejecutar la inserción
    if ($stmt->execute()) {
        header("Location: documents.php?mensaje=Documento_subido");
        exit();
    } else {
        die("Error en la base de datos: " . $stmt->error);
    }
}
?>
