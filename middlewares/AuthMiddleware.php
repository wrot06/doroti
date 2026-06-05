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
        if (session_status() === PHP_SESSION_NONE) {
            if (ob_get_level() === 0) {
                ob_start();
            }
            
            session_start();
        }
        
        // Control de caché
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
    }

    /**
     * Validar token CSRF para peticiones POST/mutaciones
     */
    public static function checkCsrf(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
                http_response_code(403);
                die("Error: Token CSRF inválido o ausente.");
            }
        }
    }
    
    /**
     * Verificar si el usuario está autenticado
     * Redirige a login si no está autenticado
     */
    public static function checkAuth(string $redirectUrl = 'login/login.php'): void {
        if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            ResponseHelper::redirect($redirectUrl);
        }
    }
    
    /**
     * Manejar cierre de sesión
     */
    public static function handleLogout(string $redirectUrl = 'login/login.php'): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_seccion'])) {
            // Limpiar cookie de recordarme si existe
            if (isset($_COOKIE['remember_me'])) {
                setcookie('remember_me', '', time() - 3600, "/");
            }
            session_destroy();
            ResponseHelper::redirect($redirectUrl);
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
    public static function validateUser(string $redirectUrl = 'login/login.php'): int {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['mensaje'] = "No se ha definido el ID de usuario. Por favor, inicie sesión nuevamente.";
            ResponseHelper::redirect($redirectUrl);
        }
        return (int)$_SESSION['user_id'];
    }
}
