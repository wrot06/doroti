<?php
declare(strict_types=1);

require_once __DIR__ . '/../utils/ResponseHelper.php';

/**
 * AuthMiddleware - Manejo de autenticación, sesiones y CSRF
 */
class AuthMiddleware {
    
    /**
     * Inicializar sesión y configurar headers de caché
     */
    public static function initSession(): void {
        ob_start();
        session_start();
        
        // Control de caché
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
    }
    
    /**
     * Verificar si el usuario está autenticado
     * Redirige a login si no está autenticado
     */
    public static function checkAuth(): void {
        if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            ResponseHelper::redirect('login/login.php');
        }
    }
    
    /**
     * Manejar cierre de sesión
     */
    public static function handleLogout(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_seccion'])) {
            session_destroy();
            ResponseHelper::redirect('login/login.php');
        }
    }
    
    /**
     * Generar token CSRF si no existe
     */
    public static function generateCsrf(): void {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    /**
     * Validar que el usuario tenga ID de sesión
     * Retorna el user_id validado
     */
    public static function validateUser(): int {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['mensaje'] = "No se ha definido el ID de usuario. Por favor, inicie sesión nuevamente.";
            ResponseHelper::redirect('login.php');
        }
        return (int)$_SESSION['user_id'];
    }
}
