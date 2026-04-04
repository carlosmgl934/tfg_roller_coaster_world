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

// ── Leer credenciales de Supabase usando el cargador central ─────────────────────────────────
require_once __DIR__ . '/../database/db_conexion.php';
require_once __DIR__ . '/../utils/ImageHelper.php';

$supabaseUrl = $_ENV['SUPABASE_URL'] ?? null;
$supabaseKey = $_ENV['SUPABASE_SERVICE_KEY'] ?? null;

// ── Parámetros de la subida ───────────────────────────────────────────────────
$file = $_FILES['file'];
$bucket = preg_replace('/[^a-z0-9\-_]/', '', $_POST['bucket'] ?? 'avatars');
$subpath = trim($_POST['path'] ?? '', '/');

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido']);
    exit;
}

if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'El archivo supera el límite de 10 MB']);
    exit;
}

$filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

// ── Optimizar imagen si es JPG/PNG/WEBP ─────────────────────────────────────
$is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
if ($is_image) {
    $optimized = ImageHelper::optimizeAndConvertToWebP($file['tmp_name'], 1920, 80, false); 
    if ($optimized) {
        // Actualizar datos para que el resto del script use el archivo .webp optimizado
        $ext = 'webp';
        $filename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
        // Nota: optimizeAndConvertToWebP crea un nuevo archivo con extensión .webp en la misma carpeta que el original (tmp)
        // Pero en Windows/PHP el tmp_name suele no tener extensión. 
        // El helper lo guardará como [tmp_name].webp
    }
}

$objectPath = $subpath ? "{$subpath}/{$filename}" : $filename;

// ── Determinar URL y Almacenamiento ──────────────────────────────────────────────────
$publicUrl = '';

if ($supabaseUrl && $supabaseKey) {
    // ── Subir a Supabase Storage ──────────────────────────────────────────────────
    $uploadUrl = rtrim($supabaseUrl, '/') . "/storage/v1/object/{$bucket}/{$objectPath}";
    $mimeType = $is_image ? 'image/webp' : ($file['type'] ?: 'application/octet-stream');
    
    // Si se optimizó, leemos el nuevo archivo .webp
    $pathToUpload = ($is_image && isset($optimized) && $optimized) ? $optimized : $file['tmp_name'];
    $fileData = file_get_contents($pathToUpload);

    $ch = curl_init($uploadUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $fileData,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$supabaseKey}",
            "Content-Type: {$mimeType}",
            "x-upsert: true",
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

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
} else {
    // ── FALLBACK: Subida en local (XAMPP) ─────────────────────────────────────────
    $uploadDirBase = __DIR__ . '/../../web/img/uploads/' . $bucket;
    if ($subpath) {
        $uploadDirBase .= '/' . $subpath;
    }
    
    if (!is_dir($uploadDirBase)) {
        if (!mkdir($uploadDirBase, 0777, true)) {
            echo json_encode(['success' => false, 'error' => 'No se pudo crear el directorio de subida local']);
            exit;
        }
    }
    
    $localFilePath = $uploadDirBase . '/' . $filename;
    
    $pathToMove = ($is_image && isset($optimized) && $optimized) ? $optimized : $file['tmp_name'];
    
    // Si es optimizado, usamos rename/copy en lugar de move_uploaded_file porque ya no es un "uploaded file" estrictamente
    if ($is_image && isset($optimized) && $optimized) {
        if (!rename($optimized, $localFilePath)) {
            echo json_encode(['success' => false, 'error' => 'Error al mover el archivo optimizado localmente']);
            exit;
        }
    } else {
        if (!move_uploaded_file($file['tmp_name'], $localFilePath)) {
            echo json_encode(['success' => false, 'error' => 'Error al guardar el archivo localmente']);
            exit;
        }
    }
    
    // Construir la URL relativa para el frontend (ej. /tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/...)
    $appRootUrl = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '\\/'); 
    $publicUrl = $appRootUrl . '/web/img/uploads/' . $bucket . '/' . $objectPath;
}

echo json_encode(['success' => true, 'url' => $publicUrl]);
