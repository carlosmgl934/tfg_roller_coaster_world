<?php

require_once __DIR__ . '/Response.php';

class ApiRouter
{
    private string $defaultAction;
    private array $routes = [];

    // Acción por defecto si la URL no lleva ?action=
    public function __construct(string $defaultAction = 'list')
    {
        $this->defaultAction = $defaultAction;
    }

    // Guarda la relación: "nombre de la acción" -> "función a ejecutar"
    public function register(string $action, $handler, string $method = '*'): void
    {
        $this->routes[$action] = [
            'handler' => $handler,
            'method' => strtoupper($method)
        ];
    }

    // Lee el $_GET['action'] y lanza la función que toca
    public function dispatch(): void
    {
        $action = $_GET['action'] ?? $this->defaultAction;

        if (!array_key_exists($action, $this->routes)) {
            Response::error('Acción no válida', 400);
        }

        $route = $this->routes[$action];

        if ($route['method'] !== '*' && $_SERVER['REQUEST_METHOD'] !== $route['method']) {
            Response::error('Método HTTP no permitido para esta acción', 405);
        }

        // Ejecuta la función asociada a la acción
        call_user_func($route['handler']);
    }
}
