<?php
/**
 * Script para limpiar sesiones expiradas de la base de datos.
 * Debe ser ejecutado mediante un Cron Job periódicamente (ej. una vez al día).
 */

require_once __DIR__ . '/../../database/db_conexion.php';
require_once __DIR__ . '/DatabaseSessionHandler.php';

$dbSession = new DBConexion();
$handler = new DatabaseSessionHandler($dbSession);

// 30 días de duración
$lifetime = 30 * 24 * 60 * 60;
$deletedRows = $handler->gc($lifetime);

echo "[" . date('Y-m-d H:i:s') . "] Tarea programada (Cron): Eliminadas $deletedRows sesiones caducadas.\n";
