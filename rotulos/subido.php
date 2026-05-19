<?php
session_start();
function flash($msg, $type='info'){
    echo "<div class='flash {$type}'>{$msg}</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Resultado de la subida</title>
  <style>
    body { font-family: Arial; padding:20px; background:#f4f4f4; }
    .flash { padding:10px; border-radius:5px; margin-bottom:15px; }
    .flash.success { background:#d4edda; color:#155724; }
    .flash.error   { background:#f8d7da; color:#721c24; }
    a { text-decoration:none; color:#0069d9; }
  </style>
</head>
<body>
  <?php
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo "<div class='flash {$f['type']}'>".htmlspecialchars($f['message'])."</div>";
    } else {
        echo "<div class='flash'>No hay notificaciones.</div>";
    }
  ?>
  <p><a href="idcargar.php?id=<?=urlencode($_GET['id'] ?? '')?>">Volver al formulario</a></p>
</body>
</html>
h