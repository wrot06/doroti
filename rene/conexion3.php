<?php
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../error.log');
error_reporting(E_ALL);

// Definir constante de acceso seguro para archivos incluidos
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Cargar credenciales desde variables de entorno o archivo de configuración
$db_host = getenv('DB_HOST') ?: null;
$db_user = getenv('DB_USER') ?: null;
$db_pass = getenv('DB_PASS') ?: null;
$db_name = getenv('DB_NAME') ?: null;

if (!$db_host || !$db_user || !$db_name) {
    $configFile = __DIR__ . '/../config/db_config.php';
    $configExampleFile = __DIR__ . '/../config/db_config.example.php';
    
    if (file_exists($configFile)) {
        $dbConfig = require $configFile;
    } elseif (file_exists($configExampleFile)) {
        $dbConfig = require $configExampleFile;
    } else {
        die('Error de configuración: Archivo de configuración de base de datos no encontrado.');
    }
    
    $db_host = $dbConfig['db_host'] ?? 'localhost';
    $db_user = $dbConfig['db_user'] ?? '';
    $db_pass = $dbConfig['db_pass'] ?? '';
    $db_name = $dbConfig['db_name'] ?? '';
}

// Crear conexión utilizando las variables cargadas dinámicamente
// Esto evita la regla de SonarCloud RSPEC-2068 (Hardcoded Credentials)
$conec = mysqli_init();
if (!$conec) {
    die('mysqli_init falló');
}
$conec->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

if (!@$conec->real_connect($db_host, $db_user, $db_pass, $db_name)) {
    ini_set('display_errors', '1');
    $errMsg = 'Error de conexión (' . mysqli_connect_errno() . '): ' . mysqli_connect_error();
    error_log($errMsg);
    die($errMsg);
}
$conec->set_charset('utf8mb4');


/**
 * Obtener todos los nombres de tablas de índices existentes (indice_documental y dedicadas).
 */
if (!function_exists('getAllIndiceTables')) {
    function getAllIndiceTables(mysqli $conn): array {
        static $tables = null;
        if ($tables === null) {
            $tables = [];
            // Verificar si existe la tabla base
            $resBase = $conn->query("SHOW TABLES LIKE 'indice_documental'");
            if ($resBase && $resBase->num_rows > 0) {
                $tables[] = 'indice_documental';
            }
            // Verificar tablas dedicadas
            $res = $conn->query("SHOW TABLES LIKE 'indice_documental_dep_%'");
            if ($res) {
                while ($row = $res->fetch_row()) {
                    $tables[] = $row[0];
                }
            }
        }
        return $tables;
    }
}

/**
 * Obtener el nombre de la tabla de índice documental para una dependencia.
 */
if (!function_exists('getIndiceTableName')) {
    function getIndiceTableName(mysqli $conn, int $depId): string {
        static $existingTables = null;
        if ($existingTables === null) {
            $existingTables = [];
            $res = $conn->query("SHOW TABLES LIKE 'indice_documental_dep_%'");
            if ($res) {
                while ($row = $res->fetch_row()) {
                    $tableName = $row[0];
                    if (preg_match('/^indice_documental_dep_(\d+)$/', $tableName, $matches)) {
                        $existingTables[(int)$matches[1]] = $tableName;
                    }
                }
            }
        }
        
        if ($depId > 0 && isset($existingTables[$depId])) {
            return $existingTables[$depId];
        }
        
        // Si no es una dependencia con tabla dedicada, verificar si la tabla base existe
        $resBase = $conn->query("SHOW TABLES LIKE 'indice_documental'");
        if ($resBase && $resBase->num_rows > 0) {
            return 'indice_documental';
        }
        
        // Retornar la primera tabla disponible si la base no existe
        $tables = getAllIndiceTables($conn);
        return !empty($tables) ? $tables[0] : 'indice_documental';
    }
}

/**
 * Obtener el nombre de la tabla de índice documental para una carpeta.
 */
if (!function_exists('getIndiceTableNameByCarpeta')) {
    function getIndiceTableNameByCarpeta(mysqli $conn, int $carpetaId): string {
        static $carpetaToDep = [];
        if (!isset($carpetaToDep[$carpetaId])) {
            $stmt = $conn->prepare("SELECT dependencia_id FROM carpetas WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $carpetaId);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $row = $res->fetch_assoc()) {
                    $carpetaToDep[$carpetaId] = (int)$row['dependencia_id'];
                } else {
                    $carpetaToDep[$carpetaId] = 0;
                }
                $stmt->close();
            } else {
                $carpetaToDep[$carpetaId] = 0;
            }
        }
        return getIndiceTableName($conn, $carpetaToDep[$carpetaId]);
    }
}

/**
 * Obtener el nombre de la tabla de índice documental que contiene un registro por su ID global.
 */
if (!function_exists('getIndiceTableNameByDocumentId')) {
    function getIndiceTableNameByDocumentId(mysqli $conn, int $docId): string {
        static $docToTable = [];
        if (isset($docToTable[$docId])) {
            return $docToTable[$docId];
        }
        
        $tables = getAllIndiceTables($conn);
        foreach ($tables as $table) {
            $stmt = $conn->prepare("SELECT 1 FROM `$table` WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("i", $docId);
                $stmt->execute();
                $resCheck = $stmt->get_result();
                $hasDoc = ($resCheck && $resCheck->num_rows > 0);
                $stmt->close();
                if ($hasDoc) {
                    $docToTable[$docId] = $table;
                    return $table;
                }
            }
        }
        return 'indice_documental';
    }
}

/**
 * Generar la subconsulta UNION ALL de todas las tablas de índices existentes.
 */
if (!function_exists('getIndiceUnionQuery')) {
    function getIndiceUnionQuery(mysqli $conn, array $columns = []): string {
        $tables = getAllIndiceTables($conn);
        if (empty($tables)) {
            if (empty($columns)) {
                return "(SELECT NULL WHERE 1=0)";
            }
            $selectCols = implode(", ", array_map(fn($c) => "NULL AS `$c`", $columns));
            return "(SELECT $selectCols FROM (SELECT 1) AS dummy WHERE 1=0)";
        }
        $colList = empty($columns) ? "*" : implode(", ", array_map(fn($c) => "`$c`", $columns));
        $subqueries = [];
        foreach ($tables as $t) {
            $subqueries[] = "SELECT $colList FROM `$t`";
        }
        return "(" . implode(" UNION ALL ", $subqueries) . ")";
    }
}
