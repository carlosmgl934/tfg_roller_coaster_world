<?php

// Ruta correcta: mismo directorio que db_conexion.php
require_once __DIR__ . '/db_conexion.php';

try {
    $db = new DBConexion();
    echo "<h2 style='color: green;'>Conexión a Supabase exitosa ✅</h2>";

    // Versión de PostgreSQL
    $result = $db->query("SELECT version()");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo "<p>Versión de PostgreSQL: <strong>" . htmlspecialchars($row['version']) . "</strong></p>";

    // Conteo de tablas en el esquema public
    $result = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'");
    $tableCount = $result->fetchColumn();
    echo "<p>Número de tablas en el esquema public: <strong>$tableCount</strong></p>";

    // Prueba de una tabla (si ya la creaste)
    try {
        $result = $db->query("SELECT COUNT(*) FROM users");
        $usersCount = $result->fetchColumn();
        echo "<p>Usuarios en la tabla users: <strong>$usersCount</strong></p>";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>Tabla 'users' aún no existe (normal si no la has creado): " . $e->getMessage() . "</p>";
    }

} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error de conexión ❌</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
?>