<?php
/**
 * FLEXIcontent WebP Converter Helper
 * Converts JPEG/PNG uploads to WebP format for performance
 * @version 6.1.0
 */
defined('_JEXEC') or die;

class FlexiWebpConverter
{
    /**
     * Convert image to WebP if GD/Imagick available
     * @param string $src  Full path to source image
     * @return string|false Path to WebP file or false on failure
     */
    public static function convert(string $src): string|false
    {
        if (!file_exists($src)) return false;

        $ext  = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        $dest = preg_replace('/\.(jpe?g|png)$/i', '.webp', $src);

        if (file_exists($dest)) return $dest; // already converted

        // Try GD first
        if (function_exists('imagewebp')) {
            $img = match($ext) {
                'jpg', 'jpeg' => imagecreatefromjpeg($src),
                'png'         => imagecreatefrompng($src),
                default       => false,
            };
            if ($img === false) return false;

            // Preserve PNG transparency
            if ($ext === 'png') {
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
            }

            $result = imagewebp($img, $dest, 82); // 82% quality
            imagedestroy($img);
            return $result ? $dest : false;
        }

        // Try Imagick
        if (class_exists('Imagick')) {
            try {
                $im = new Imagick($src);
                $im->setImageFormat('webp');
                $im->setImageCompressionQuality(82);
                $im->writeImage($dest);
                $im->destroy();
                return $dest;
            } catch (ImagickException $e) {
                return false;
            }
        }

        return false; // No converter available
    }

    /**
     * Batch convert directory
     * @param string $dir   Directory path
     * @param bool   $recurse  Recurse into subdirs
     * @return array [converted, skipped, failed]
     */
    public static function convertDirectory(string $dir, bool $recurse = false): array
    {
        $converted = 0; $skipped = 0; $failed = 0;

        $pattern  = $recurse ? $dir . '/**/*.{jpg,jpeg,png}' : $dir . '/*.{jpg,jpeg,png}';
        $flags    = GLOB_BRACE | ($recurse ? 0 : 0);
        $files    = glob($pattern, $flags) ?: [];

        foreach ($files as $file) {
            $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file);
            if (file_exists($webp)) { $skipped++; continue; }

            $result = self::convert($file);
            if ($result) $converted++;
            else         $failed++;
        }

        return compact('converted', 'skipped', 'failed');
    }

    /**
     * Check if WebP conversion is available
     */
    public static function isAvailable(): bool
    {
        return function_exists('imagewebp') || class_exists('Imagick');
    }

    /**
     * Get WebP savings estimate for a directory
     */
    public static function estimateSavings(string $dir): array
    {
        $total_orig = 0;
        $total_webp = 0;
        $count = 0;

        foreach (glob($dir . '/*.{jpg,jpeg,png}', GLOB_BRACE) ?: [] as $file) {
            $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file);
            if (file_exists($webp)) {
                $total_orig += filesize($file);
                $total_webp += filesize($webp);
                $count++;
            }
        }

        $saving_pct = $total_orig > 0 ? round(($total_orig - $total_webp) / $total_orig * 100, 1) : 0;
        return [
            'files'       => $count,
            'original_kb' => round($total_orig / 1024, 1),
            'webp_kb'     => round($total_webp / 1024, 1),
            'saving_pct'  => $saving_pct,
        ];
    }
}
