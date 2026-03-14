<?php

// Base URL calculada automáticamente (funciona en cualquier servidor)
/** @var string $base_url */
$base_url = preg_replace('#/RollerCoasterWorld/.*$#', '/RollerCoasterWorld', $_SERVER['SCRIPT_NAME']) ?? '';

$routes = [
    // Vistas públicas
    'index' => $base_url . '/web/views/public/index.php',
    'ranking' => $base_url . '/web/views/public/users/ranking.php',
    'coasters' => $base_url . '/web/views/public/coasters/coasters.php',
    'coaster_detail' => $base_url . '/web/views/public/coasters/coaster_detail.php',
    'coaster_search' => $base_url . '/web/views/public/coasters/coaster_search.php',
    'coaster_reviews' => $base_url . '/web/views/public/coasters/coaster_reviews.php',
    'coaster_tops' => $base_url . '/web/views/public/coasters/coaster_tops.php',
    'parks' => $base_url . '/web/views/public/parks/parks.php',
    'park_detail' => $base_url . '/web/views/public/parks/park_detail.php',
    'forums' => $base_url . '/web/views/public/forums/forums.php',
    'trips' => $base_url . '/web/views/public/trips/trips.php',
    'carrito' => $base_url . '/web/views/public/shop/carrito.php',
    'checkout' => $base_url . '/web/views/public/shop/checkout.php',
    'orders' => $base_url . '/web/views/public/shop/orders.php',
    'contact' => $base_url . '/web/views/public/contact.php',
    'privacy' => $base_url . '/web/views/public/privacy.php',
    'notice' => $base_url . '/web/views/public/notice.php',
    'form_rating' => $base_url . '/web/views/public/coasters/form_rating.php',
    'trip_generator' => $base_url . '/web/views/public/trips/trip_generator.php',

    // Perfil y social
    'profile' => $base_url . '/web/views/public/users/profile.php',
    'friends' => $base_url . '/web/views/public/users/friends.php',
    'user_profile' => $base_url . '/web/views/public/users/user_profile.php',

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
