<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../utils/SessionManager.php';
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$ids = $input['ids'] ?? [];
$overrides = $input['overrides'] ?? [];

if (empty($ids)) {
    echo json_encode(['error' => 'No IDs provided']);
    exit;
}

$idString = implode(',', array_map('intval', $ids));
$pythonScript = realpath(__DIR__ . '/../../scraper/updater.py');
$pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'python' : 'python3';
$cmd = $pythonCmd . " -u " . escapeshellarg($pythonScript) . " --apply-ids=" . escapeshellarg($idString);

if (!empty($overrides)) {
    $overridesFile = __DIR__ . '/overrides.json';
    file_put_contents($overridesFile, json_encode($overrides, JSON_UNESCAPED_UNICODE));
    $cmd .= " --overrides=" . escapeshellarg($overridesFile);
}

// Force UTF-8 Python I/O to avoid UnicodeEncodeError with emojis on Windows
putenv('PYTHONIOENCODING=utf-8');
putenv('PYTHONUTF8=1');
exec($cmd . " 2>&1", $outputArr, $returnCode);
$output = implode("\n", $outputArr);

if (!empty($overrides) && file_exists($overridesFile)) {
    unlink($overridesFile);
}

if ($returnCode !== 0) {
    echo json_encode(['error' => "El actualizador falló:\n" . $output]);
    exit;
}

echo json_encode(['success' => true, 'output' => $output]);
?>