<?php
require 'rene/conexion3.php';

echo "Table: IndiceDocumental\n";
$res = $conec->query("SHOW COLUMNS FROM IndiceDocumental");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "Error: " . $conec->error . "\n";
}

echo "\nTable: dependencias\n";
$res = $conec->query("SHOW COLUMNS FROM dependencias");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "Error: " . $conec->error . "\n";
}
?>
