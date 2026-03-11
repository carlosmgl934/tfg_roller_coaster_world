<?php
/**
 * upload.php — Endpoint para subir archivos a Supabase Storage
 * Evita el CORS de Firebase Storage subiendo los archivos desde el servidor PHP.
 *
 * Uso: POST multipart/form-data
 *   - file   : el archivo a subir
 *   - bucket : nombre del bucket (ej: "avatars", "coasters")
 *   - path   : subcarpeta opcional (ej: "coasters/123")
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['firebase_uid'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

if (empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'No se recibió ningún archivo']);
    exit;
}

// ── Leer credenciales de Supabase desde .env ─────────────────────────────────
$envPath = __DIR__ . '/../../.env';
$supabaseUrl  = null;
$supabaseKey  = null;

if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if ($k === 'SUPABASE_URL')         $supabaseUrl = $v;
            if ($k === 'SUPABASE_SERVICE_KEY')  $supabaseKey = $v;
        }
    }
}

if (!$supabaseUrl || !$supabaseKey) {
    echo json_encode(['success' => false, 'error' => 'Credenciales de Supabase no configuradas en .env']);
    exit;
}

// ── Parámetros de la subida ───────────────────────────────────────────────────
$file     = $_FILES['file'];
$bucket   = preg_replace('/[^a-z0-9\-_]/', '', $_POST['bucket'] ?? 'avatars');
$subpath  = trim($_POST['path'] ?? '', '/');

$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido']);
    exit;
}

if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'El archivo supera el límite de 10 MB']);
    exit;
}

$filename   = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$objectPath = $subpath ? "{$subpath}/{$filename}" : $filename;

// ── Subir a Supabase Storage ──────────────────────────────────────────────────
$uploadUrl = rtrim($supabaseUrl, '/') . "/storage/v1/object/{$bucket}/{$objectPath}";
$mimeType  = $file['type'] ?: 'application/octet-stream';
$fileData  = file_get_contents($file['tmp_name']);

$ch = curl_init($uploadUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_POSTFIELDS     => $fileData,
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer {$supabaseKey}",
        "Content-Type: {$mimeType}",
        "x-upsert: true",
    ],
]);

$response   = curl_exec($ch);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError  = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'error' => 'Error de red: ' . $curlError]);
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    $decoded = json_decode($response, true);
    $msg = $decoded['message'] ?? $response;
    echo json_encode(['success' => false, 'error' => "Supabase error {$httpCode}: {$msg}"]);
    exit;
}

// ── URL pública del archivo ───────────────────────────────────────────────────
$publicUrl = rtrim($supabaseUrl, '/') . "/storage/v1/object/public/{$bucket}/{$objectPath}";
echo json_encode(['success' => true, 'url' => $publicUrl]);
