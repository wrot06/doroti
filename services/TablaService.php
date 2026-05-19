<?php

declare(strict_types=1);

/**
 * TablaService - Lógica de negocio para el módulo de Tablas
 * Provee conteos y estadísticas sobre los tipos documentales
 * registrados en IndiceDocumental.
 */
class TablaService
{

    private mysqli $conec;

    public function __construct(mysqli $conec)
    {
        $this->conec = $conec;
    }

    /**
     * Obtener conteo global de tipos documentales (series) agrupados.
     * Incluye registros sin serie clasificados como "Sin clasificar".
     *
     * @return array Lista de ['serie', 'total', 'total_paginas', 'pct']
     */
    public function getSerieCount(): array
    {
        $sql = "
        SELECT
            COALESCE(NULLIF(TRIM(serie), ''), 'Sin clasificar') AS serie,
            COUNT(*)                                            AS total,
            COALESCE(SUM(paginas), 0)                          AS total_paginas
        FROM IndiceDocumental
        GROUP BY serie
        ORDER BY total DESC
        ";

        $result = $this->conec->query($sql);
        if (!$result) return [];

        $rows = [];
        $grand_total = 0;

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
            $grand_total += (int)$row['total'];
        }

        // Agregar porcentaje
        foreach ($rows as &$row) {
            $row['pct'] = $grand_total > 0
                ? round(((int)$row['total'] / $grand_total) * 100, 1)
                : 0;
        }
        unset($row);

        return ['rows' => $rows, 'grand_total' => $grand_total];
    }

    /**
     * Obtener conteo agrupado por dependencia y serie.
     *
     * @return array Lista indexada por dependencia
     */
    public function getSerieByDependencia(): array
    {
        $sql = "
        SELECT
            COALESCE(dep.nombre, 'Sin dependencia')            AS dependencia,
            COALESCE(NULLIF(TRIM(i.serie), ''), 'Sin clasificar') AS serie,
            COUNT(*)                                           AS total,
            COALESCE(SUM(i.paginas), 0)                        AS total_paginas
        FROM IndiceDocumental i
        LEFT JOIN dependencias dep ON dep.id = i.dependencia_id
        GROUP BY i.dependencia_id, i.serie
        ORDER BY dependencia, total DESC
        ";

        $result = $this->conec->query($sql);
        if (!$result) return [];

        $grouped = [];
        while ($row = $result->fetch_assoc()) {
            $dep = $row['dependencia'];
            if (!isset($grouped[$dep])) {
                $grouped[$dep] = ['series' => [], 'subtotal' => 0, 'carpetas' => 0];
            }
            $grouped[$dep]['series'][]  = $row;
            $grouped[$dep]['subtotal'] += (int)$row['total'];
        }

        // Contar carpetas por dependencia
        $sqlCarpetas = "
        SELECT
            COALESCE(dep.nombre, 'Sin dependencia') AS dependencia,
            COUNT(*) AS total_carpetas
        FROM Carpetas c
        LEFT JOIN dependencias dep ON dep.id = c.dependencia_id
        WHERE c.Estado = 'C'
        GROUP BY c.dependencia_id
        ";
        $resCarpetas = $this->conec->query($sqlCarpetas);
        if ($resCarpetas) {
            while ($row = $resCarpetas->fetch_assoc()) {
                $dep = $row['dependencia'];
                if (isset($grouped[$dep])) {
                    $grouped[$dep]['carpetas'] = (int)$row['total_carpetas'];
                }
            }
        }

        return $grouped;
    }

    /**
     * Obtener conteo agrupado por usuario y serie.
     *
     * @return array Lista indexada por usuario
     */
    public function getSerieByUsuario(): array
    {
        $sql = "
        SELECT
            COALESCE(u.id, 0)                                  AS user_id,
            COALESCE(u.username, 'Sin usuario')                AS usuario,
            COALESCE(NULLIF(TRIM(i.serie), ''), 'Sin clasificar') AS serie,
            COUNT(*)                                           AS total,
            COALESCE(SUM(i.paginas), 0)                        AS total_paginas
        FROM IndiceDocumental i
        LEFT JOIN Carpetas c ON c.id = i.carpeta_id
        LEFT JOIN users u ON u.id = c.user_id
        GROUP BY u.id, i.serie
        ORDER BY usuario, total DESC
        ";

        $result = $this->conec->query($sql);
        if (!$result) return [];

        $grouped = [];
        while ($row = $result->fetch_assoc()) {
            $usu = $row['usuario'];
            if (!isset($grouped[$usu])) {
                $grouped[$usu] = [
                    'user_id' => (int)$row['user_id'],
                    'series' => [],
                    'subtotal' => 0,
                    'carpetas' => 0
                ];
            }
            $grouped[$usu]['series'][]  = $row;
            $grouped[$usu]['subtotal'] += (int)$row['total'];
        }

        // Contar carpetas por usuario
        $sqlCarpetas = "
        SELECT
            COALESCE(u.username, 'Sin usuario') AS usuario,
            COUNT(*) AS total_carpetas
        FROM Carpetas c
        LEFT JOIN users u ON u.id = c.user_id
        WHERE c.Estado = 'C'
        GROUP BY c.user_id
        ";
        $resCarpetas = $this->conec->query($sqlCarpetas);
        if ($resCarpetas) {
            while ($row = $resCarpetas->fetch_assoc()) {
                $usu = $row['usuario'];
                if (isset($grouped[$usu])) {
                    $grouped[$usu]['carpetas'] = (int)$row['total_carpetas'];
                }
            }
        }

        return $grouped;
    }

    /**
     * Totales globales: documentos y folios.
     *
     * @return array ['total_docs', 'total_paginas']
     */
    public function getGlobalTotals(): array
    {
        $sql = "SELECT COUNT(*) AS total_docs, COALESCE(SUM(paginas),0) AS total_paginas FROM IndiceDocumental";
        $result = $this->conec->query($sql);
        $data = $result ? $result->fetch_assoc() : ['total_docs' => 0, 'total_paginas' => 0];

        $sqlCarpetas = "SELECT COUNT(*) AS total_carpetas FROM Carpetas WHERE Estado = 'C' ";
        $resCarpetas = $this->conec->query($sqlCarpetas);
        $data['total_carpetas'] = $resCarpetas ? (int) $resCarpetas->fetch_assoc()['total_carpetas'] : 0;

        return $data;
    }

    /**
     * Número de series únicas registradas.
     */
    public function countSeriesUnicas(): int
    {
        $sql = "SELECT COUNT(DISTINCT NULLIF(TRIM(serie),'')) AS n FROM IndiceDocumental WHERE serie IS NOT NULL AND TRIM(serie) != ''";
        $result = $this->conec->query($sql);
        if (!$result) return 0;
        $row = $result->fetch_assoc();
        return (int)($row['n'] ?? 0);
    }
}
