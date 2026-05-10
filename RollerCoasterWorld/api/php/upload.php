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

require_once __DIR__ . '/utils/SessionManager.php';
header('Content-Type: application/json');

// ── DEBUG: función helper para loggear con timestamp ─────────────────────────
function dbg(string $msg, mixed $data = null): void
{
    $line = '[UPLOAD_DEBUG ' . date('H:i:s') . '] ' . $msg;
    if ($data !== null) {
        $line .= ' | ' . (is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    error_log($line);
}

dbg('===== INICIO upload.php =====');
dbg('SESSION uid', $_SESSION['firebase_uid'] ?? 'NO SESSION');
dbg('REQUEST_METHOD', $_SERVER['REQUEST_METHOD'] ?? '?');
dbg('User-Agent', $_SERVER['HTTP_USER_AGENT'] ?? 'desconocido');
dbg('Content-Type header recibido', $_SERVER['CONTENT_TYPE'] ?? 'ninguno');
dbg('$_POST keys', array_keys($_POST));
dbg('$_FILES keys', array_keys($_FILES));

if (!empty($_FILES['file'])) {
    dbg('$_FILES[file] info', [
        'name' => $_FILES['file']['name'],
        'type' => $_FILES['file']['type'],
        'size' => $_FILES['file']['size'],
        'error' => $_FILES['file']['error'],
        'tmp_name' => $_FILES['file']['tmp_name'],
    ]);
} else {
    dbg('$_FILES[file] está vacío o no existe');
}

// ── Handler global: cualquier excepción no capturada devuelve JSON, nunca HTML ──
set_exception_handler(function (\Throwable $e) {
    dbg('EXCEPCIÓN NO CAPTURADA', $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    dbg('Stack trace', $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
    exit;
});

// ── Handler de errores fatales (parse errors, out of memory, etc.) ────────────
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        dbg('ERROR FATAL shutdown', $err);
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode(['success' => false, 'error' => 'Error fatal PHP: ' . $err['message']]);
    }
    dbg('===== FIN upload.php (shutdown) =====');
});

// ── Validaciones básicas ──────────────────────────────────────────────────────
if (!isset($_SESSION['firebase_uid'])) {
    dbg('ABORT: no autenticado');
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    dbg('ABORT: método no POST');
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

if (empty($_FILES['file'])) {
    dbg('ABORT: no se recibió $_FILES[file]');
    echo json_encode(['success' => false, 'error' => 'No se recibió ningún archivo']);
    exit;
}

if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    dbg('ABORT: error de subida PHP', $_FILES['file']['error']);
    echo json_encode(['success' => false, 'error' => 'Error de subida PHP: ' . $_FILES['file']['error']]);
    exit;
}

// ── Requires ──────────────────────────────────────────────────────────────────
dbg('Cargando db_conexion.php...');
require_once __DIR__ . '/../database/db_conexion.php';
dbg('db_conexion.php OK');

dbg('Cargando RateLimiter.php...');
require_once __DIR__ . '/utils/RateLimiter.php';
RateLimiter::check('upload', 20, 60); // 20 subidas/min por IP

dbg('Cargando ImageHelper.php...');
require_once __DIR__ . '/../utils/ImageHelper.php';
dbg('ImageHelper.php OK');

$supabaseUrl = $_ENV['SUPABASE_URL'] ?? null;
$supabaseKey = $_ENV['SUPABASE_SERVICE_KEY'] ?? null;
dbg('SUPABASE_URL presente', $supabaseUrl ? 'SÍ' : 'NO (modo local)');
dbg('SUPABASE_SERVICE_KEY presente', $supabaseKey ? 'SÍ' : 'NO');

// ── Parámetros de la subida ───────────────────────────────────────────────────
$file = $_FILES['file'];
$bucket = preg_replace('/[^a-z0-9\-_]/', '', $_POST['bucket'] ?? 'avatars');
$subpath = trim($_POST['path'] ?? '', '/');

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

dbg('Parámetros resueltos', [
    'bucket'       => $bucket,
    'subpath'      => $subpath,
    'ext'          => $ext,
    'mime_browser' => $file['type'],
]);

// iOS puede enviar HEIC/HEIF; también aceptamos los formatos habituales
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'];

// ── Validación real de MIME type con finfo (no confiar en el browser) ─────────
$allowedMimes = [
    'image/jpeg' => ['jpg', 'jpeg'],
    'image/png'  => ['png'],
    'image/gif'  => ['gif'],
    'image/webp' => ['webp'],
    'image/heic' => ['heic', 'heif'],
    'image/heif' => ['heic', 'heif'],
];

if (function_exists('finfo_open')) {
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($file['tmp_name']);
    dbg('MIME real detectado', $realMime);

    if (!array_key_exists($realMime, $allowedMimes)) {
        dbg('ABORT: MIME real no permitido', $realMime);
        echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido']);
        exit;
    }

    // Cross-check: extensión debe coincidir con el MIME real
    $validExtsForMime = $allowedMimes[$realMime];
    if (!in_array($ext, $validExtsForMime, true)) {
        // Corregir la extensión automáticamente (ej: HEIC enviado como .jpg)
        $ext = $validExtsForMime[0];
        dbg('Extensión corregida al MIME real', $ext);
    }
} else {
    // Fallback si finfo no está disponible: validar solo extensión
    dbg('finfo no disponible, validando solo extensión');
    if (!in_array($ext, $allowed, true)) {
        dbg('ABORT: extensión no permitida', $ext);
        echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido']);
        exit;
    }
}

if ($file['size'] > 10 * 1024 * 1024) {
    dbg('ABORT: archivo demasiado grande', $file['size']);
    echo json_encode(['success' => false, 'error' => 'El archivo supera el límite de 10 MB']);
    exit;
}

$filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
dbg('Filename generado', $filename);

// ── Optimizar imagen ──────────────────────────────────────────────────────────
// POSIBLE FALLO 500 NÚMERO 1: Memoria excedida.
// En Mac al subir fotos originales de iPhone (HEIC de +15MB o JPEG de +20MB), 
// 'file_get_contents' o 'imagecreatefromstring' de GD pueden exceder el límite de memoria de PHP.
$optimized = false;
$is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
dbg('¿Es imagen optimizable?', $is_image ? 'SÍ' : 'NO (gif o heic/heif)');

// Comprobar soporte GD
// POSIBLE FALLO 500 NÚMERO 2: La extensión GD no está instalada o habilitada 
// en la versión XAMPP/MAMP que usa la otra persona.
if (function_exists('gd_info')) {
    $gdInfo = gd_info();
    dbg('GD info', [
        'version' => $gdInfo['GD Version'] ?? '?',
        'jpeg_support' => !empty($gdInfo['JPEG Support']),
        'png_support' => !empty($gdInfo['PNG Support']),
        'webp_support' => !empty($gdInfo['WebP Support']),
    ]);
} else {
    dbg('GD NO disponible en este servidor');
}

if ($is_image) {
    dbg('Intentando optimizeAndConvertToWebP...');
    try {
        $optimized = ImageHelper::optimizeAndConvertToWebP($file['tmp_name'], 1920, 80, false);
        if ($optimized) {
            dbg('Optimización OK → archivo webp', $optimized);
            $ext = 'webp';
            $filename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
        } else {
            dbg('optimizeAndConvertToWebP devolvió false/null → se usará original');
        }
    } catch (\Throwable $e) {
        dbg('EXCEPCIÓN en optimizeAndConvertToWebP', $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
        // GD no pudo procesar el formato (ej: HEIC disfrazado de JPG desde iOS)
        // → se continuará con el archivo original sin optimizar
        $optimized = false;
    }
} elseif (in_array($ext, ['heic', 'heif'])) {
    dbg('Archivo HEIC/HEIF nativo → saltando optimización, subiendo original');
    $optimized = false;
}

dbg('Estado tras optimización', [
    'ext_final' => $ext,
    'filename_final' => $filename,
    'optimized_path' => $optimized ?: 'ninguno (se usa tmp_name)',
]);

$objectPath = $subpath ? "{$subpath}/{$filename}" : $filename;
dbg('objectPath final', $objectPath);

// ── MIME type final ───────────────────────────────────────────────────────────
$mimeTypeMap = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'heic' => 'image/heic',
    'heif' => 'image/heif',
];
$finalMime = $mimeTypeMap[$ext] ?? ($file['type'] ?: 'application/octet-stream');
dbg('MIME type final', $finalMime);

// ── Ruta real del archivo a subir ─────────────────────────────────────────────
$pathToUpload = ($optimized) ? $optimized : $file['tmp_name'];
dbg('Ruta real a subir', $pathToUpload);
dbg('¿Archivo legible?', is_readable($pathToUpload) ? 'SÍ' : 'NO — PROBLEMA AQUÍ');
dbg('Tamaño real del archivo a subir', (string) (filesize($pathToUpload) ?: '0 o false'));

// ── Almacenamiento ────────────────────────────────────────────────────────────
// POSIBLE FALLO 500 NÚMERO 3: Fallo de sistema de archivos en MacOS.
// En Mac (MAMP/XAMPP), si el db_conexion.php falla porque no encuentra un archivo .env, 
// o si no tiene permisos de escritura en la carpeta htdocs, esto puede causar un volcado fatal.
$publicUrl = '';

if ($supabaseUrl && $supabaseKey) {
    dbg('Modo: Supabase Storage');
    $uploadUrl = rtrim($supabaseUrl, '/') . "/storage/v1/object/{$bucket}/{$objectPath}";
    dbg('URL de subida Supabase', $uploadUrl);

    $fileData = @file_get_contents($pathToUpload);
    if ($fileData === false) {
        dbg('ABORT: no se pudo leer el archivo para enviarlo a Supabase');
        echo json_encode(['success' => false, 'error' => 'No se pudo leer el archivo local antes de enviarlo a Supabase.']);
        exit;
    }
    dbg('Bytes leídos para Supabase', (string) strlen($fileData));

    $ch = curl_init($uploadUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $fileData,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$supabaseKey}",
            "Content-Type: {$finalMime}",
            "x-upsert: true",
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    dbg('Supabase respuesta HTTP', (string) $httpCode);
    dbg('Supabase respuesta body', $response);

    if ($curlError) {
        dbg('ABORT: cURL error', $curlError);
        echo json_encode(['success' => false, 'error' => 'Error de red: ' . $curlError]);
        exit;
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        $decoded = json_decode($response, true);
        $msg = $decoded['message'] ?? $response;
        dbg('ABORT: Supabase devolvió error', "HTTP $httpCode: $msg");
        echo json_encode(['success' => false, 'error' => "Supabase error {$httpCode}: {$msg}"]);
        exit;
    }

    $publicUrl = rtrim($supabaseUrl, '/') . "/storage/v1/object/public/{$bucket}/{$objectPath}";
    dbg('URL pública generada', $publicUrl);

} else {
    dbg('Modo: almacenamiento local (XAMPP)');
    $uploadDirBase = __DIR__ . '/../../web/img/uploads/' . $bucket;
    if ($subpath) {
        $uploadDirBase .= '/' . $subpath;
    }
    dbg('Directorio destino local', $uploadDirBase);
    dbg('¿Directorio existe?', is_dir($uploadDirBase) ? 'SÍ' : 'NO → intentando crear');

    if (!is_dir($uploadDirBase)) {
        $mkdirResult = mkdir($uploadDirBase, 0777, true);
        dbg('mkdir resultado', $mkdirResult ? 'OK' : 'FALLÓ');
        if (!$mkdirResult) {
            echo json_encode(['success' => false, 'error' => 'No se pudo crear el directorio de subida local']);
            exit;
        }
    }

    $localFilePath = $uploadDirBase . '/' . $filename;
    dbg('Ruta local destino', $localFilePath);

    if ($optimized) {
        dbg('Moviendo archivo optimizado con rename...');
        $moved = rename($optimized, $localFilePath);
    } else {
        dbg('Moviendo uploaded file con move_uploaded_file...');
        $moved = move_uploaded_file($file['tmp_name'], $localFilePath);
    }

    dbg('¿Archivo movido?', $moved ? 'SÍ' : 'NO — PROBLEMA AQUÍ');

    if (!$moved) {
        echo json_encode(['success' => false, 'error' => 'Error al guardar el archivo localmente']);
        exit;
    }

    $appRootUrl = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '\\/');
    $publicUrl = $appRootUrl . '/web/img/uploads/' . $bucket . '/' . $objectPath;
    dbg('URL pública local', $publicUrl);
}

dbg('TODO OK → devolviendo URL', $publicUrl);
echo json_encode(['success' => true, 'url' => $publicUrl]);