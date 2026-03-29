<?php

class ImageHelper
{
    /**
     * Resizes an image and converts it to WebP format.
     * Deletes the original file if requested.
     * 
     * @param string $source_path Path to the source image
     * @param int $max_width Maximum width allowed (default 1920)
     * @param int $quality WebP quality (0-100)
     * @param bool $delete_original Whether to delete the source file after conversion
     * @return string|bool Path to the new WebP image or false on failure
     */
    public static function optimizeAndConvertToWebP($source_path, $max_width = 1920, $quality = 80, $delete_original = false)
    {
        if (!file_exists($source_path)) {
            return false;
        }

        $info = getimagesize($source_path);
        if (!$info) {
            return false;
        }

        $mime = $info['mime'];
        $width = $info[0];
        $height = $info[1];

        // Create image from source
        switch ($mime) {
            case 'image/jpeg':
                $img = imagecreatefromjpeg($source_path);
                break;
            case 'image/png':
                $img = imagecreatefrompng($source_path);
                // Handle PNG transparency
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
                break;
            case 'image/webp':
                // Already webp, but maybe we want to resize
                $img = imagecreatefromwebp($source_path);
                break;
            default:
                return false;
        }

        if (!$img) {
            return false;
        }

        // Resize if width is greater than max_width
        if ($width > $max_width) {
            $new_width = $max_width;
            $new_height = floor($height * ($max_width / $width));
            
            $resized_img = imagescale($img, $new_width, $new_height);
            imagedestroy($img);
            $img = $resized_img;
        }

        // Destination path (same name, .webp extension)
        $path_parts = pathinfo($source_path);
        $dest_path = $path_parts['dirname'] . DIRECTORY_SEPARATOR . $path_parts['filename'] . '.webp';

        // Save as WebP
        if (imagewebp($img, $dest_path, $quality)) {
            imagedestroy($img);
            
            // Delete original if it's not the same as destination and requested
            if ($delete_original && $source_path !== $dest_path) {
                unlink($source_path);
            }
            
            return $dest_path;
        }

        imagedestroy($img);
        return false;
    }
}
