<?php
if (!isset($base_url)) {
    $base_url = preg_replace('#/RollerCoasterWorld/.*$#', '/RollerCoasterWorld', $_SERVER['SCRIPT_NAME']);
}

$routes = [
    // Vistas a las que redirige la API
    'contact' => $base_url . '/web/views/public/contact.php',
    'home' => $base_url . '/web/views/public/home.php',
    'index' => $base_url . '/web/views/public/index.php',
    'profile' => $base_url . '/web/views/public/profile.php',

    // Auth
    'login' => $base_url . '/web/views/auth/login.php',
    'logout' => $base_url . '/web/views/auth/logout.php',
    'register' => $base_url . '/web/views/auth/register.php',
];
