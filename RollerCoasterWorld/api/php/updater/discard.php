<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../utils/SessionManager.php';
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$ids = $input['ids'] ?? [];

if (empty($ids)) {
    echo json_encode(['error' => 'No IDs provided']);
    exit;
}

$idString = implode(',', array_map('intval', $ids));
$pythonScript = realpath(__DIR__ . '/../../scraper/updater.py');
$pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'python' : 'python3';
$cmd = $pythonCmd . " -u " . escapeshellarg($pythonScript) . " --discard-ids=" . escapeshellarg($idString);

exec($cmd . " 2>&1", $outputArr, $returnCode);
$output = implode("\n", $outputArr);

if ($returnCode !== 0) {
    echo json_encode(['error' => "Error al descartar:\n" . $output]);
    exit;
}

echo json_encode(['success' => true, 'output' => $output]);
?>