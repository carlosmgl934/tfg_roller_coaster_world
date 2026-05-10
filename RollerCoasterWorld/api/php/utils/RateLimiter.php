<?php

/**
 * RateLimiter — Limitador de peticiones por IP usando archivos temporales.
 * Soporta cabeceras X-RateLimit-* y respuesta 429 con Retry-After.
 */
class RateLimiter
{
    /**
     * Comprueba el límite de peticiones. Si se supera, responde 429 y detiene la ejecución.
     *
     * @param string $key    Identificador del endpoint (ej: 'auth', 'contact', 'upload')
     * @param int    $max    Máximo de peticiones en la ventana
     * @param int    $window Ventana de tiempo en segundos
     */
    public static function check(string $key, int $max = 100, int $window = 60): void
    {
        $ip   = self::getClientIp();
        $file = sys_get_temp_dir() . '/rl_' . md5($key . $ip) . '.json';
        $now  = time();

        // Leer datos existentes
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (!is_array($data) || !isset($data['hits'])) {
                $data = ['hits' => []];
            }
            // Purgar hits fuera de la ventana
            $data['hits'] = array_values(array_filter(
                $data['hits'],
                fn($t) => ($now - $t) < $window
            ));
        } else {
            $data = ['hits' => []];
        }

        $remaining = max(0, $max - count($data['hits']));

        if (count($data['hits']) >= $max) {
            $oldest     = min($data['hits']);
            $retryAfter = $window - ($now - $oldest);

            // Log del bloqueo
            error_log("[RATE_LIMIT][" . date('Y-m-d H:i:s') . "] IP bloqueada: {$ip} | endpoint: {$key}");

            // Respuesta 429
            http_response_code(429);
            if (!headers_sent()) {
                header('Content-Type: application/json');
                header("Retry-After: {$retryAfter}");
                header("X-RateLimit-Limit: {$max}");
                header("X-RateLimit-Remaining: 0");
                header("X-RateLimit-Reset: " . ($now + $retryAfter));
            }
            echo json_encode([
                'success' => false,
                'error'   => "Demasiadas peticiones. Inténtalo en {$retryAfter} segundos.",
                'retry_after' => $retryAfter,
            ]);
            exit;
        }

        // Registrar hit actual
        $data['hits'][] = $now;
        file_put_contents($file, json_encode($data), LOCK_EX);

        // Cabeceras informativas
        if (!headers_sent()) {
            header("X-RateLimit-Limit: {$max}");
            header("X-RateLimit-Remaining: " . ($remaining - 1));
            header("X-RateLimit-Reset: " . ($now + $window));
        }
    }

    /**
     * Obtiene la IP real del cliente sin confiar en cabeceras falsificables.
     * En producción detrás de un proxy de confianza, descomentar X-Forwarded-For.
     */
    private static function getClientIp(): string
    {
        // En XAMPP local / sin proxy de confianza: usar REMOTE_ADDR siempre.
        // Para producción con proxy nginx/cloudflare, usar:
        // return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
