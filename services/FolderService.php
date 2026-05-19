<?php
declare(strict_types=1);

/**
 * FolderService - Lógica de negocio relacionada con carpetas
 */
class FolderService {
    
    private mysqli $conec;
    
    public function __construct(mysqli $conec) {
        $this->conec = $conec;
    }
    
    /**
     * Obtener carpetas del usuario
     * TODOS los usuarios (incluidos admin) solo ven sus propias carpetas
     * 
     * @return mysqli_result|false
     */
    public function getUserFolders(int $userId): mysqli_result|false {
        $sql = "
        SELECT 
            c.id AS carpeta_id,
            c.Caja,
            c.Carpeta,
            COALESCE(u.username, 'Usuario no registrado') AS username,
            dep.nombre AS oficina,
            c.dependencia_id
        FROM Carpetas c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN dependencias dep ON dep.id = c.dependencia_id
        WHERE c.Estado = 'A'
          AND c.user_id = ?
        ORDER BY c.Caja DESC, c.Carpeta DESC
        LIMIT 20
        ";
        
        $stmt = $this->conec->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        return $result;
    }
}
