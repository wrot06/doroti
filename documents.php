<?php
declare(strict_types=1);
ob_start();
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

// Cierre de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_seccion'])) {
    session_destroy();
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Obtener todos los documentos del usuario
$sql = "
  SELECT id, serie, fecha_creacion, titulo_documento, fecha_subida
    FROM Documentos
   WHERE user_id = ?
ORDER BY fecha_subida DESC
";
$stmt = $conec->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Digitalización</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        /* Opcional: ocultar ítems que no coincidan con el filtro */
        .list-item-hidden {
            display: none !important;
        }
    </style>
</head>
<body class="container py-4">

    <!-- Encabezado y botones -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <h2 class="mb-0">Documentos</h2>
  <div class="d-flex align-items-center gap-2">
    <input
      type="text"
      id="buscarInput"
      class="form-control form-control-sm"
      placeholder="Buscar por Serie o Título..."
      onkeyup="filtrarDocumentos()"
      style="width: 200px;"
    >
    <a href="digital.php" class="btn btn-success btn-sm">
      <i class="bi bi-upload me-1"></i>Subir nuevo
    </a>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-house me-1"></i>Inicio
    </a>
  </div>
</div>




    <?php if ($result->num_rows > 0): ?>
        <ul class="list-group" id="listaDocumentos">
            <?php while ($doc = $result->fetch_assoc()): ?>
                <li class="list-group-item d-flex justify-content-between align-items-start list-item"
                    data-serie="<?= htmlspecialchars(strtolower($doc['serie'])) ?>"
                    data-titulo="<?= htmlspecialchars(strtolower($doc['titulo_documento'])) ?>"
                >

            <div class="ms-2 me-auto">
                <div>
                <span class="fw-bold"><?= htmlspecialchars($doc['serie']) ?>:</span>
                <span class="text-body-secondary"> <?= htmlspecialchars($doc['titulo_documento']) ?></span>
            </div>

            <div class="mb-2">
                <span class="text-muted" style="font-size: 0.7rem;">
                <strong>Creación:</strong> <?= htmlspecialchars($doc['fecha_creacion']) ?> |
                <strong>Subido:</strong> <?= htmlspecialchars($doc['fecha_subida']) ?>
                </span>
            </div>


            </div>
                    <a
                        href="download.php?id=<?= intval($doc['id']) ?>"
                        target="_blank"
                        class="btn btn-primary btn-sm"
                    >
                        <i class="bi bi-file-earmark-pdf-fill me-1"></i>Ver PDF
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <div class="alert alert-info">
            No has subido ningún documento aún.
        </div>
    <?php endif; ?>

    <script>
    function filtrarDocumentos() {
        const filtro = document.getElementById('buscarInput').value.toLowerCase();
        const items = document.querySelectorAll('.list-item');
        
        items.forEach(item => {
            const serie  = item.getAttribute('data-serie');
            const titulo = item.getAttribute('data-titulo');
            
            if (serie.includes(filtro) || titulo.includes(filtro)) {
                item.classList.remove('list-item-hidden');
            } else {
                item.classList.add('list-item-hidden');
            }
        });
    }
    </script>
</body>
</html>
<?php ob_end_flush(); ?>
