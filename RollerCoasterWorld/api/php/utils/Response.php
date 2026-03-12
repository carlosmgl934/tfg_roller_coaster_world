<?php

class Response {
    // Devuelve un JSON con success = true

    public static function success(array $data = []): void {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => true], $data));
        exit;
    }

    // Devuelve un JSON de error con el código HTTP correspondiente

    public static function error(string $message, int $statusCode = 400): void {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode(['success' => false, 'error' => $message]);
        exit;
    }

    // Atajo para errores de falta de permisos (401)

    public static function unauthorized(string $message = 'No autorizado'): void {
        self::error($message, 401);
    }

    // Atajo para errores de "no se ha encontrado" (404)

    public static function notFound(string $message = 'No encontrado'): void {
        self::error($message, 404);
    }
}
