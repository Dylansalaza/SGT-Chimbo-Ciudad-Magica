<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Convierte y guarda imágenes subidas en formato WebP (mucho más liviano que
 * JPEG/PNG → páginas más rápidas), usando la extensión GD nativa de PHP (no
 * requiere ninguna librería de Composer).
 *
 * Es defensivo por diseño: si el archivo no es una foto JPEG/PNG (p. ej. un
 * video o un GIF animado), o si la conversión falla por cualquier motivo, se
 * guarda el archivo ORIGINAL tal cual. Así una subida nunca se rompe por esto.
 */
class ImageOptimizer
{
    /**
     * Guarda el archivo en el disco público, convirtiéndolo a WebP cuando es
     * una foto JPEG/PNG. Devuelve la ruta relativa guardada (ej: "home/xy.webp").
     *
     * @param  UploadedFile  $file     Archivo recibido en la petición.
     * @param  string        $folder   Carpeta dentro del disco público (ej: "home").
     * @param  int           $quality  Calidad WebP 0-100 (82 = buen balance).
     */
    public static function storeWebp(UploadedFile $file, string $folder, int $quality = 82): string
    {
        $mime = $file->getMimeType();

        // Solo convertimos fotos JPEG/PNG. Los GIF (posible animación), los
        // WebP ya optimizados y cualquier no-imagen (video) se guardan igual.
        if (function_exists('imagewebp') && in_array($mime, ['image/jpeg', 'image/png'], true)) {
            $webp = self::convertir($file, $mime, $quality);

            if ($webp !== null) {
                $nombre = self::nombreBase($file) . '.webp';
                $rel    = $folder . '/' . $nombre;
                Storage::disk('public')->put($rel, $webp);

                return $rel;
            }
        }

        // Fallback: guardar el archivo original sin modificar.
        $nombre = self::nombreBase($file) . '.' . $file->getClientOriginalExtension();

        return $file->storeAs($folder, $nombre, 'public');
    }

    /** Devuelve el binario WebP, o null si la conversión no fue posible. */
    private static function convertir(UploadedFile $file, string $mime, int $quality): ?string
    {
        try {
            $img = $mime === 'image/png'
                ? @imagecreatefrompng($file->getRealPath())
                : @imagecreatefromjpeg($file->getRealPath());

            if ($img === false) {
                return null;
            }

            // Preserva la transparencia del PNG en el WebP resultante.
            if ($mime === 'image/png') {
                imagepalettetotruecolor($img);
                imagealphablending($img, false);
                imagesavealpha($img, true);
            }

            ob_start();
            $ok = imagewebp($img, null, $quality);
            $contenido = ob_get_clean();
            imagedestroy($img);

            return ($ok && $contenido !== false && strlen($contenido) > 0) ? $contenido : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Nombre base único y saneado (sin extensión), igual criterio que antes. */
    private static function nombreBase(UploadedFile $file): string
    {
        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        return time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $original);
    }
}
