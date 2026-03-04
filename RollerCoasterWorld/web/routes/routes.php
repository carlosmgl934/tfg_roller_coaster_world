<?php

$routes = [
    // Vistas públicas
    'home' => $base_url . '/web/views/public/home.php',
    'index' => $base_url . '/web/views/public/index.php',
    'profile' => $base_url . '/web/views/public/profile.php',
    'coasters' => $base_url . '/web/views/public/coasters.php',
    'parks' => $base_url . '/web/views/public/parks.php',
    'forums' => $base_url . '/web/views/public/forums.php',
    'trips' => $base_url . '/web/views/public/trips.php',
    'carrito' => $base_url . '/web/views/public/carrito.php',
    'contact' => $base_url . '/web/views/public/contact.php',
    'privacy' => $base_url . '/web/views/public/privacy.php',

    // Admin (requieren rol admin)
    'admin' => $base_url . '/web/views/admin/dashboard.php',
    'admin_coasters' => $base_url . '/web/views/admin/coasters.php',
    'admin_parks' => $base_url . '/web/views/admin/parks.php',
    'admin_users' => $base_url . '/web/views/admin/users.php',
    'admin_messages' => $base_url . '/web/views/admin/messages.php',

    // Auth (Firebase)
    'login' => $base_url . '/web/views/auth/login.php',
    'register' => $base_url . '/web/views/auth/register.php',
    'logout' => $base_url . '/web/views/auth/logout.php',
];
