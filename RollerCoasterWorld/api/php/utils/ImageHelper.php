<?php
/**
 * ImageHelper — Utilidades de procesamiento de imágenes del lado del servidor.
 *
 * Requiere la extensión GD de PHP (disponible por defecto en XAMPP).
 * Si GD no está disponible o la conversión falla, devuelve null y se usa
 * el archivo original sin optimizar (degradación graceful).
 */
class ImageHelper
{
    /**
     * Optimiza una imagen y la convierte a WebP.
     *
     * @param  string $sourcePath   Ruta al archivo temporal subido (tmp_name).
     * @param  int    $maxDimension Dimensión máxima en píxeles (ancho o alto).
     * @param  int    $quality      Calidad WebP de 0 a 100.
     * @param  bool   $keepAspect   (No usado, siempre se mantiene el aspect ratio.)
     * @return string|null          Ruta al archivo WebP generado, o null si falla.
     */
    public static function optimizeAndConvertToWebP(
        string $sourcePath,
        int $maxDimension = 1920,
        int $quality = 80,
        bool $keepAspect = true
    ): ?string {
        // ── Comprobar que GD está disponible ──────────────────────────────────
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        // ── Leer el archivo de origen ─────────────────────────────────────────
        $rawData = @file_get_contents($sourcePath);
        if ($rawData === false || strlen($rawData) === 0) {
            return null;
        }

        // ── Crear imagen GD desde los bytes brutos (agnóstico al tipo) ────────
        $srcImg = @imagecreatefromstring($rawData);
        if ($srcImg === false) {
            return null;
        }

        $origW = imagesx($srcImg);
        $origH = imagesy($srcImg);

        // ── Calcular nuevas dimensiones manteniendo proporción ────────────────
        $newW = $origW;
        $newH = $origH;

        if ($origW > $maxDimension || $origH > $maxDimension) {
            if ($origW >= $origH) {
                $newW = $maxDimension;
                $newH = (int) round($origH * ($maxDimension / $origW));
            } else {
                $newH = $maxDimension;
                $newW = (int) round($origW * ($maxDimension / $origH));
            }
        }

        // ── Redibujar con las nuevas dimensiones ──────────────────────────────
        $dstImg = imagecreatetruecolor($newW, $newH);

        // Preservar transparencia (PNG con alpha, etc.)
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
        $transparent = imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
        imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $transparent);
        imagealphablending($dstImg, true);

        imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        unset($srcImg); // imagedestroy() está deprecada desde PHP 8.5

        // ── Guardar como WebP en un fichero temporal ──────────────────────────
        // Añadir sufijo .webp al tmp_name (evita colisiones con el original)
        $destPath = $sourcePath . '.webp';

        if (!function_exists('imagewebp')) {
            // WebP no soportado: salida sin conversión
            unset($dstImg);
            return null;
        }

        $ok = @imagewebp($dstImg, $destPath, $quality);
        unset($dstImg);

        if (!$ok || !file_exists($destPath) || filesize($destPath) === 0) {
            @unlink($destPath);
            return null;
        }

        return $destPath;
    }
}
