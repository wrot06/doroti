<?php
session_start();
require "../rene/conexion3.php";

/* =========================
   CAPTURA Y VALIDACIÓN
========================= */
$dependencia_id = intval($_POST['dependencia_id'] ?? 0);
$caja           = intval($_POST['caja'] ?? 0);
$carpeta        = intval($_POST['carpeta'] ?? 0);
$folios         = intval($_POST['folios'] ?? 0);

$serie_id       = intval($_POST['serie'] ?? 0);
$subserie_id    = intval($_POST['subserie'] ?? 0);

$tituloCarpeta=$_POST['tituloCarpeta']??'';

/* =========================
   NORMALIZAR TITULO CARPETA
========================= */
// quitar espacios al inicio y final
$tituloCarpeta=trim($tituloCarpeta);

// reemplazar múltiples espacios por uno solo
$tituloCarpeta=preg_replace('/\s+/u',' ',$tituloCarpeta);

// asegurar que el primer carácter no sea espacio y esté en mayúscula si es letra
if($tituloCarpeta!==''){
 $primerChar=mb_substr($tituloCarpeta,0,1,'UTF-8');
 if(preg_match('/[a-záéíóúñ]/u',$primerChar)){
  $tituloCarpeta=mb_strtoupper($primerChar,'UTF-8').mb_substr($tituloCarpeta,1,null,'UTF-8');
 }
}

$fechaInicial   = $_POST['fechaInicial'] ?? '';
$fechaFinal     = $_POST['fechaFinal'] ?? '';
$estado         = 'C';

if (
    $dependencia_id <= 0 ||
    $caja <= 0 ||
    $carpeta <= 0 ||
    $folios <= 0 ||
    $serie_id <= 0 ||
    $tituloCarpeta === '' ||
    !$fechaInicial ||
    !$fechaFinal
) {
    die("Error: Datos incompletos o inválidos.");
}

if ($fechaInicial > $fechaFinal) {
    die("Error: La fecha inicial no puede ser mayor que la final.");
}

/* =========================
   OBTENER NOMBRE DE SERIE
========================= */
$stmt = $conec->prepare("SELECT nombre FROM Serie WHERE id = ?");
$stmt->bind_param("i", $serie_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    die("Error: Serie inválida.");
}

$Serie = strtoupper($row['nombre']);

/* =========================
   OBTENER NOMBRE DE SUBSERIE (OPCIONAL)
========================= */
$Subs = null;

if ($subserie_id > 0) {
    $stmt = $conec->prepare("SELECT Subs FROM Subs WHERE id = ?");
    $stmt->bind_param("i", $subserie_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if ($row) {
        $Subs = strtoupper($row['Subs']);
    }
}

/* =========================
   VALIDAR CAPÍTULOS
========================= */
$stmt = $conec->prepare(
    "SELECT COUNT(*) AS total
     FROM IndiceTemp
     WHERE Caja = ? AND Carpeta = ? AND dependencia_id = ?"
);
$stmt->bind_param("iii", $caja, $carpeta, $dependencia_id);
$stmt->execute();
$res = $stmt->get_result();
$totalCapitulos = (int)($res->fetch_assoc()['total'] ?? 0);
$stmt->close();

if ($totalCapitulos === 0) {
    die("No se puede finalizar la carpeta sin capítulos.");
}

/* =========================
   TRANSACCIÓN
========================= */
$conec->begin_transaction();

try {

    /* ---------- UPDATE Carpetas ---------- */
    $stmt = $conec->prepare(
        "UPDATE Carpetas
         SET Serie = ?, Subs = ?, Titulo = ?, FInicial = ?, FFinal = ?, Folios = ?, Estado = ?
         WHERE Caja = ? AND Carpeta = ? AND dependencia_id = ?"
    );

    if (!$stmt) {
        throw new Exception($conec->error);
    }

    $stmt->bind_param(
        "sssssisiii",
        $Serie,        // nombre de la serie
        $Subs,         // nombre de la subserie o NULL
        $tituloCarpeta,
        $fechaInicial,
        $fechaFinal,
        $folios,
        $estado,
        $caja,
        $carpeta,
        $dependencia_id
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $stmt->close();

    /* ---------- INSERT IndiceDocumental ---------- */
    $sqlInsert = "
        INSERT INTO IndiceDocumental ( dependencia_id, Caja, Carpeta, carpeta_id, Serie, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas, Soporte, FechaIngreso
        )
        SELECT dependencia_id, Caja, Carpeta, carpeta_id, Serie, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, paginas, Soporte, NOW()
        FROM IndiceTemp
        WHERE Caja = ?
          AND Carpeta = ?
          AND dependencia_id = ?
    ";

    $stmt = $conec->prepare($sqlInsert);
    if (!$stmt) {
        throw new Exception($conec->error);
    }

    $stmt->bind_param("iii", $caja, $carpeta, $dependencia_id);

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $stmt->close();

    /* ---------- DELETE IndiceTemp ---------- */
    $stmt = $conec->prepare(
        "DELETE FROM IndiceTemp
         WHERE Caja = ? AND Carpeta = ? AND dependencia_id = ?"
    );

    if (!$stmt) {
        throw new Exception($conec->error);
    }

    $stmt->bind_param("iii", $caja, $carpeta, $dependencia_id);

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $stmt->close();

    /* ---------- COMMIT ---------- */
    $conec->commit();

} catch (Exception $e) {
    $conec->rollback();
    die("Error crítico: " . $e->getMessage());
}

$conec->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../img/hueso.png">
    <title>Carpeta Finalizada</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5 d-flex justify-content-center">
    <div class="card shadow-sm" style="max-width: 500px;">
        <div class="card-body text-center">
            <h4 class="mb-3">Carpeta finalizada correctamente</h4>
            <a href="../index.php" class="btn btn-primary btn-lg">Volver al inicio</a>
        </div>
    </div>
</div>
</body>
</html>
