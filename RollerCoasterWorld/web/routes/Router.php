<?php

class Router {
    private static $baseUrl = null;
    private static $routes = [];

    /**
     * Inicializa el router y define las rutas
     */
    public static function init() {
        if (self::$baseUrl !== null) {
            return;
        }

        // Calcular URL base automáticamente
        // Busca /RollerCoasterWorld en el path y se queda con lo anterior + /RollerCoasterWorld
        self::$baseUrl = preg_replace('#/RollerCoasterWorld/.*$#', '/RollerCoasterWorld', $_SERVER['SCRIPT_NAME']) ?? '';

        // Definición de TODAS las rutas del proyecto
        self::$routes = [
            // Públicas - Home
            'home' => '/web/views/public/index.php',
            'index' => '/web/views/public/index.php',
            
            // Públicas - Coasters
            'coasters' => '/web/views/public/coasters/coasters.php',
            'coaster_search' => '/web/views/public/coasters/coaster_search.php',
            'coaster_detail' => '/web/views/public/coasters/coaster_detail.php', // Check if this file exists, likely logic handled in coasters.php? No, routes.php had it.
            'coaster_reviews' => '/web/views/public/coasters/coaster_reviews.php',
            'coaster_tops' => '/web/views/public/coasters/coaster_tops.php',
            'form_rating' => '/web/views/public/coasters/form_rating.php',

            // Públicas - Parques
            'parks' => '/web/views/public/parks/parks.php',
            'park_search' => '/web/views/public/parks/park_search.php',
            'park_detail' => '/web/views/public/parks/park_detail.php',
            'park_tops' => '/web/views/public/parks/park_tops.php',
            'park_reviews' => '/web/views/public/parks/park_reviews.php',
            'form_park_rating' => '/web/views/public/parks/form_park_rating.php',

            // Públicas - Foros
            'forums' => '/web/views/public/forums/forum_search.php', // Redirige a search si forums.php está vacío
            'forum_search' => '/web/views/public/forums/forum_search.php',
            'forum_detail' => '/web/views/public/forums/forums.php', // Mantener por si acaso

            // Públicas - Legal/Info
            'contact' => '/web/views/public/contact.php',
            'privacy' => '/web/views/public/privacy.php',
            'notice' => '/web/views/public/notice.php',

            // Usuarios / Social
            'ranking' => '/web/views/public/users/ranking.php',
            'profile' => '/web/views/public/users/profile.php',
            'friends' => '/web/views/public/users/friends.php',
            'user_profile' => '/web/views/public/users/user_profile.php',

            // Tienda
            'carrito' => '/web/views/public/shop/carrito.php',
            'checkout' => '/web/views/public/shop/checkout.php',
            'orders' => '/web/views/public/shop/orders.php',

            // Viajes
            'trips' => '/web/views/public/trips/trips.php',
            'trip_generator' => '/web/views/public/trips/trip_generator.php',

            // Auth
            'login' => '/web/views/auth/login.php',
            'register' => '/web/views/auth/register.php',
            'logout' => '/web/views/auth/logout.php',

            // Admin
            'admin' => '/web/views/admin/admin.php',
            'admin_dashboard' => '/web/views/admin/dashboard.php',
            'admin_coasters' => '/web/views/admin/coasters.php',
            'admin_parks' => '/web/views/admin/parks.php',
            'admin_users' => '/web/views/admin/users.php',
            'admin_forums' => '/web/views/admin/forums.php',
            'admin_messages' => '/web/views/admin/messages.php',
            'admin_photos' => '/web/views/admin/photos.php',
            'admin_comments' => '/web/views/admin/comments.php',
            'admin_orders' => '/web/views/admin/orders.php',
        ];
    }

    /**
     * Obtiene la URL absoluta para una ruta por su nombre.
     * @param string $name Nombre de la ruta (clave del array)
     * @return string URL completa
     */
    public static function url($name) {
        if (self::$baseUrl === null) self::init();

        if (isset(self::$routes[$name])) {
            return self::$baseUrl . self::$routes[$name];
        }
        
        // Si no existe la ruta, devolver base para evitar errores fatales, o loguear error
        error_log("Ruta no encontrada: $name");
        return self::$baseUrl;
    }

    /**
     * Obtiene la URL base del proyecto
     */
    public static function getBaseUrl() {
        if (self::$baseUrl === null) self::init();
        return self::$baseUrl;
    }

    /**
     * Genera una URL para un asset (CSS, JS, imágenes)
     * @param string $path Ruta relativa desde la raíz del proyecto (ej: 'web/css/style.css')
     */
    public static function asset($path) {
        if (self::$baseUrl === null) self::init();
        return self::$baseUrl . '/' . ltrim($path, '/');
    }
    
    /**
     * Redirige a una ruta interna
     */
    public static function redirect($name) {
        header('Location: ' . self::url($name));
        exit;
    }

    /**
     * Devuelve todas las rutas (para compatibilidad si es necesario)
     */
    public static function getRoutes() {
        if (self::$baseUrl === null) self::init();
        $fullRoutes = [];
        foreach (self::$routes as $key => $path) {
            $fullRoutes[$key] = self::$baseUrl . $path;
        }
        return $fullRoutes;
    }

    /**
     * Obtiene el path relativo de una ruta (sin dominio/base)
     * @param string $name Nombre de la ruta
     * @return string Path relativo (ej: /web/views/...)
     */
    public static function getRoutePath($name) {
        if (self::$baseUrl === null) self::init();
        return self::$routes[$name] ?? '';
    }
}
