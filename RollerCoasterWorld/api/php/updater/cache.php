<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../utils/SessionManager.php';
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$cacheFile = __DIR__ . '/../../scraper/updater_cache.json';
if (file_exists($cacheFile)) {
    echo file_get_contents($cacheFile);
} else {
    echo json_encode(['error' => 'Cache file not found']);
}
?>