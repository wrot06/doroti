<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../rene/conexion3.php';

if ($conec->connect_error) {
    die("Conexión fallida: " . $conec->connect_error . "\n");
}

// 1. Obtener dependencias activas con índices en la base de datos
$sql = "
    SELECT DISTINCT c.dependencia_id 
    FROM indice_documental i
    JOIN carpetas c ON i.carpeta_id = c.id
";
$res = $conec->query($sql);
if (!$res) {
    die("Error al consultar dependencias con índices: " . $conec->error . "\n");
}

$deps = [];
while ($row = $res->fetch_assoc()) {
    if ($row['dependencia_id']) {
        $deps[] = (int)$row['dependencia_id'];
    }
}

echo "Dependencias con índices a migrar: " . implode(", ", $deps) . "\n";

foreach ($deps as $depId) {
    $tableName = "indice_documental_dep_{$depId}";
    echo "Procesando dependencia ID: $depId (Tabla: $tableName)...\n";
    
    // Crear tabla si no existe
    $sqlCreate = "
        CREATE TABLE IF NOT EXISTS `$tableName` (
          `id` int NOT NULL AUTO_INCREMENT,
          `carpeta_id` int NOT NULL COMMENT 'ID desde Carpetas',
          `serie` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
          `DescripcionUnidadDocumental` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
          `NoFolioInicio` int DEFAULT NULL,
          `NoFolioFin` int DEFAULT NULL,
          `Soporte` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
          `FechaIngreso` datetime DEFAULT CURRENT_TIMESTAMP,
          `paginas` int DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `fk_indicedocumental_carpeta_{$depId}` (`carpeta_id`),
          CONSTRAINT `fk_indicedocumental_carpeta_{$depId}` FOREIGN KEY (`carpeta_id`) REFERENCES `carpetas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";
    
    if (!$conec->query($sqlCreate)) {
        die("Error al crear tabla $tableName: " . $conec->error . "\n");
    }
    
    // Migrar registros
    $conec->begin_transaction();
    try {
        $sqlMigrate = "
            INSERT IGNORE INTO `$tableName` (id, carpeta_id, serie, DescripcionUnidadDocumental, NoFolioInicio, NoFolioFin, Soporte, FechaIngreso, paginas)
            SELECT i.id, i.carpeta_id, i.serie, i.DescripcionUnidadDocumental, i.NoFolioInicio, i.NoFolioFin, i.Soporte, i.FechaIngreso, i.paginas
            FROM indice_documental i
            JOIN carpetas c ON i.carpeta_id = c.id
            WHERE c.dependencia_id = ?
        ";
        $stmt = $conec->prepare($sqlMigrate);
        $stmt->bind_param("i", $depId);
        $stmt->execute();
        $inserted = $stmt->affected_rows;
        $stmt->close();
        
        echo " - Migrados $inserted registros a $tableName\n";
        
        // Eliminar de indice_documental
        $sqlDelete = "
            DELETE i FROM indice_documental i
            JOIN carpetas c ON i.carpeta_id = c.id
            WHERE c.dependencia_id = ?
        ";
        $stmtDel = $conec->prepare($sqlDelete);
        $stmtDel->bind_param("i", $depId);
        $stmtDel->execute();
        $deleted = $stmtDel->affected_rows;
        $stmtDel->close();
        
        echo " - Eliminados $deleted registros de la tabla original\n";
        
        $conec->commit();
    } catch (Exception $e) {
        $conec->rollback();
        die("Error durante la migración de dependencia $depId: " . $e->getMessage() . "\n");
    }
}

echo "Migración completada con éxito.\n";
