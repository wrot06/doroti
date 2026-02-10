<?php
declare(strict_types=1);

/**
 * UserService - Lógica de negocio relacionada con usuarios
 */
class UserService {
    
    private mysqli $conec;
    
    public function __construct(mysqli $conec) {
        $this->conec = $conec;
    }
    
    /**
     * Obtener información del usuario (nombre y oficina)
     * 
     * @return array ['username' => string, 'oficina' => string]
     */
    public function getUserInfo(int $userId): array {
        $sql = "
        SELECT 
            u.username,
            dep.nombre AS oficina
        FROM users u
        LEFT JOIN dependencias dep ON dep.id = u.dependencia_id
        WHERE u.id = ?
        LIMIT 1
        ";
        
        $stmt = $this->conec->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $usuario = 'Usuario';
        $oficina = '';
        
        if ($result && mysqli_num_rows($result) === 1) {
            $row = $result->fetch_assoc();
            $usuario = $row['username'] ?? 'Usuario';
            $oficina = $row['oficina'] ?? '';
        }
        
        $stmt->close();
        
        return [
            'username' => $usuario,
            'oficina' => $oficina
        ];
    }
    
    /**
     * Obtener avatar del usuario
     * 
     * @return string Ruta del avatar o default
     */
    public function getUserAvatar(int $userId): string {
        $defaultAvatar = 'uploads/avatars/default.png';
        
        $stmt = $this->conec->prepare("SELECT avatar FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if ($row['avatar'] && file_exists('uploads/avatars/' . basename($row['avatar']))) {
                $stmt->close();
                return 'uploads/avatars/' . basename($row['avatar']);
            }
        }
        
        $stmt->close();
        return $defaultAvatar;
    }
}
