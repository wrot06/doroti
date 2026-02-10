<?php
declare(strict_types=1);

/**
 * ResponseHelper - Funciones auxiliares para respuestas HTTP y HTML
 */
class ResponseHelper {
    
    /**
     * Escapar HTML para prevenir XSS
     */
    public static function h(mixed $str): string {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Redireccionar a una URL y terminar ejecución
     */
    public static function redirect(string $url): void {
        header("Location: $url");
        exit();
    }
}
