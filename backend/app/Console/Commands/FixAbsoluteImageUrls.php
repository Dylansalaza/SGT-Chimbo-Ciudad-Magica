<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Gallery;
use App\Models\News;
use App\Models\TouristPlace;
use Illuminate\Console\Command;

/**
 * Convierte las URLs de imagen ABSOLUTAS (http://host:puerto/storage/...) que
 * quedaron guardadas en BD a RELATIVAS (/storage/...).
 *
 * Las URLs absolutas quedan "ancladas" al host/puerto que estaba activo en
 * APP_URL en el momento de subir la foto: si el panel se abre después desde
 * otro host/puerto (dev en otro puerto, producción con otro dominio), esa
 * imagen se ve rota (icono roto) aunque el archivo siga existiendo en el
 * servidor. Los uploads NUEVOS ya guardan la URL relativa (los controladores
 * de subida ya fueron corregidos); este comando repara las filas antiguas.
 *
 *   php artisan images:fix-urls            (aplica los cambios)
 *   php artisan images:fix-urls --dry-run   (solo muestra qué cambiaría)
 */
class FixAbsoluteImageUrls extends Command
{
    protected $signature = 'images:fix-urls {--dry-run : Solo mostrar qué se cambiaría, sin guardar}';

    protected $description = 'Convierte URLs de imagen absolutas (http://host/storage/...) a relativas (/storage/...) en toda la BD';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // [Modelo, campos de texto simple, campos array/JSON]
        $objetivos = [
            [TouristPlace::class, ['imagen_url'], ['galeria']],
            [Event::class,        ['image_url'],  ['images']],
            [News::class,         ['image_url'],  ['images']],
            [Gallery::class,      [],             ['images']],
        ];

        $totalFilas = 0;
        $totalUrls  = 0;

        foreach ($objetivos as [$modelo, $camposTexto, $camposArray]) {
            foreach ($modelo::all() as $fila) {
                $cambio = false;

                foreach ($camposTexto as $campo) {
                    $nueva = $this->relativizar($fila->{$campo});
                    if ($nueva !== null) {
                        $fila->{$campo} = $nueva;
                        $cambio = true;
                        $totalUrls++;
                    }
                }

                foreach ($camposArray as $campo) {
                    $valores = (array) ($fila->{$campo} ?? []);
                    $nuevos  = [];
                    $cambioArray = false;

                    foreach ($valores as $item) {
                        if (is_array($item)) {
                            $nueva = $this->relativizar($item['url'] ?? null);
                            if ($nueva !== null) {
                                $item['url']  = $nueva;
                                $cambioArray  = true;
                                $totalUrls++;
                            }
                            $nuevos[] = $item;
                        } else {
                            $nueva = $this->relativizar($item);
                            if ($nueva !== null) {
                                $cambioArray = true;
                                $totalUrls++;
                            }
                            $nuevos[] = $nueva ?? $item;
                        }
                    }

                    if ($cambioArray) {
                        $fila->{$campo} = $nuevos;
                        $cambio = true;
                    }
                }

                if ($cambio) {
                    $totalFilas++;
                    $nombre = $fila->nombre ?? $fila->title ?? "#{$fila->id}";
                    $this->line('  · [' . class_basename($modelo) . " #{$fila->id}] {$nombre}");
                    if (! $dryRun) {
                        $fila->save();
                    }
                }
            }
        }

        if ($dryRun) {
            $this->warn("(dry-run) Se cambiarían {$totalUrls} URLs en {$totalFilas} filas. Corre sin --dry-run para aplicar.");
        } else {
            $this->info("✅ Corregidas {$totalUrls} URLs en {$totalFilas} filas.");
        }

        return self::SUCCESS;
    }

    /** Convierte una URL absoluta (http://host/storage/x) a relativa (/storage/x). Null si no aplica (ya es relativa o no tiene el marcador). */
    private function relativizar(?string $url): ?string
    {
        if (! $url || ! str_starts_with($url, 'http')) {
            return null;
        }
        $marcador = '/storage/';
        $idx = strpos($url, $marcador);

        return $idx === false ? null : substr($url, $idx);
    }
}
