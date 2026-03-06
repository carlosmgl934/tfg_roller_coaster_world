<?php

$routes = [
    // Vistas públicas
    'index' => $base_url . '/web/views/public/index.php',
    'ranking' => $base_url . '/web/views/public/ranking.php',
    'coasters' => $base_url . '/web/views/public/coasters.php',
    'coaster_detail' => $base_url . '/web/views/public/coaster_detail.php',
    'parks' => $base_url . '/web/views/public/parks.php',
    'park_detail' => $base_url . '/web/views/public/park_detail.php',
    'forums' => $base_url . '/web/views/public/forums.php',
    'trips' => $base_url . '/web/views/public/trips.php',
    'carrito' => $base_url . '/web/views/public/carrito.php',
    'checkout' => $base_url . '/web/views/public/checkout.php',
    'orders' => $base_url . '/web/views/public/orders.php',
    'contact' => $base_url . '/web/views/public/contact.php',
    'privacy' => $base_url . '/web/views/public/privacy.php',
    'notice' => $base_url . '/web/views/public/notice.php',

    // Perfil y social
    'profile' => $base_url . '/web/views/public/profile.php',
    'friends' => $base_url . '/web/views/public/friends.php',
    'user_profile' => $base_url . '/web/views/public/user_profile.php',

    // Admin (requieren rol admin)
    'admin' => $base_url . '/web/views/admin/admin.php',
    'admin_dashboard' => $base_url . '/web/views/admin/dashboard.php',
    'admin_coasters' => $base_url . '/web/views/admin/coasters.php',
    'admin_parks' => $base_url . '/web/views/admin/parks.php',
    'admin_users' => $base_url . '/web/views/admin/users.php',
    'admin_messages' => $base_url . '/web/views/admin/messages.php',
    'admin_photos' => $base_url . '/web/views/admin/photos.php',
    'admin_comments' => $base_url . '/web/views/admin/comments.php',
    'admin_orders' => $base_url . '/web/views/admin/orders.php',

    // Auth (Firebase)
    'login' => $base_url . '/web/views/auth/login.php',
    'register' => $base_url . '/web/views/auth/register.php',
    'logout' => $base_url . '/web/views/auth/logout.php',
];
