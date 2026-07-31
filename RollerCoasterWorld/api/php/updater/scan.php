<?php
// SSE endpoint
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable buffering for Nginx just in case

require_once __DIR__ . '/../utils/SessionManager.php';
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    echo "data: [ERROR] Unauthorized\n\n";
    exit;
}

// Get type (quick or full)
$type = $_GET['type'] ?? 'quick';
$args = ($type === 'full') ? '--web-full' : '--web-scan';

$pythonScript = realpath(__DIR__ . '/../../scraper/updater.py');
$pythonCmd = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'python' : 'python3';
// -u = unbuffered; PYTHONIOENCODING passed via env to avoid UnicodeEncodeError with emojis on Windows
$cmd = $pythonCmd . " -u " . escapeshellarg($pythonScript) . " " . escapeshellarg($args);

// Execute command and stream output
$descriptorSpec = array(
    0 => array("pipe", "r"),  // stdin
    1 => array("pipe", "w"),  // stdout
    2 => array("pipe", "w")   // stderr
);

// Inherit current environment and force UTF-8 Python I/O
$env = array_merge($_ENV ?: [], getenv() ?: [], ['PYTHONIOENCODING' => 'utf-8', 'PYTHONUTF8' => '1']);

$process = proc_open($cmd, $descriptorSpec, $pipes, null, $env);

if (is_resource($process)) {
    while ($s = fgets($pipes[1])) {
        // SSE formatting requires newlines properly managed
        $clean = trim($s);
        if ($clean !== '') {
            echo "data: " . $clean . "\n\n";
            @ob_flush();
            @flush();
        }
    }

    // Read any errors
    while ($e = fgets($pipes[2])) {
        $clean = trim($e);
        if ($clean !== '') {
            echo "data: [ERROR] " . $clean . "\n\n";
            @ob_flush();
            @flush();
        }
    }

    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $return_value = proc_close($process);

    echo "data: [DONE] Exit code: $return_value\n\n";
    @ob_flush();
    @flush();
} else {
    echo "data: [ERROR] Failed to start process\n\n";
}
?>