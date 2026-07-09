<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gallery;

/**
 * Galerías fotográficas con imágenes reales de San José de Chimbo y Ecuador.
 * Todas las imágenes son de Wikimedia Commons (licencia libre CC).
 * Seguro de re-ejecutar: updateOrCreate por título.
 *   php artisan db:seed --class=GallerySeeder
 */
class GallerySeeder extends Seeder
{
    private const WC = 'https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/';

    public function run(): void
    {
        $galerias = [

            // ── 1 · Vista general de la ciudad ─────────────────────────────────
            [
                'title'    => 'San José de Chimbo — Vista de la Ciudad',
                'category' => 'Arquitectura',
                'images'   => [
                    self::WC . 'Chimbo%2C_Ecuador.JPG&width=1400',
                    self::WC . 'Vista_de_San_Jos%C3%A9_de_Chimbo.jpg&width=1400',
                    self::WC . 'San_Jose_de_Chimbo%2C_Ecuador.jpg&width=1400',
                ],
            ],

            // ── 2 · Centro Histórico y Patrimonio ─────────────────────────────
            [
                'title'    => 'Centro Histórico y Patrimonio de Chimbo',
                'category' => 'Cultura',
                'images'   => [
                    self::WC . 'Parque_Central_de_San_Jos%C3%A9_de_Chimbo.jpg&width=1400',
                    self::WC . 'El_Torre%C3%B3n_San_Jos%C3%A9_de_Chimbo.jpg&width=1400',
                    self::WC . 'Calle_Tres_de_Marzo_en_San_Jos%C3%A9_de_Chimbo.jpg&width=1400',
                ],
            ],

            // ── 3 · Naturaleza y Paisajes ─────────────────────────────────────
            // Volcán Chimborazo, vecino natural de Chimbo en la sierra ecuatoriana
            [
                'title'    => 'Naturaleza y Paisajes de los Andes',
                'category' => 'Naturaleza',
                'images'   => [
                    self::WC . 'Chimborazo_desde_San_Juan.jpg&width=1400',
                    self::WC . 'Vicu%C3%B1a_-_Chimborazo%2C_Ecuador.jpg&width=1400',
                    self::WC . 'Chimborazo.JPG&width=1400',
                ],
            ],

            // ── 4 · Gastronomía Chimbeña ──────────────────────────────────────
            [
                'title'    => 'Gastronomía Típica de San José de Chimbo',
                'category' => 'Gastronomía',
                'images'   => [
                    self::WC . 'Hornado_de_Riobamba.jpg&width=1400',
                    self::WC . 'Hornado.jpg&width=1400',
                    self::WC . 'Fritada.jpg&width=1400',
                    self::WC . 'Fanesca_Ecuatoriana.jpg&width=1400',
                ],
            ],

            // ── 5 · Fiestas y Tradiciones ─────────────────────────────────────
            [
                'title'    => 'Fiestas y Tradiciones de Chimbo',
                'category' => 'Fiestas',
                'images'   => [
                    self::WC . 'Carnaval_de_Guaranda%2C_Ecuador.JPG&width=1400',
                    self::WC . 'Carnaval_de_Guaranda_2019.jpg&width=1400',
                    self::WC . 'Carnaval_de_Guaranda_2019_-_2.jpg&width=1400',
                    self::WC . 'Danzantes_del_Corpus_Christi%2C_Pujil%C3%AD.jpg&width=1400',
                    self::WC . 'Espect%C3%A1culo_de_fuegos_artificiales.jpg&width=1400',
                ],
            ],
        ];

        foreach ($galerias as $g) {
            Gallery::updateOrCreate(['title' => $g['title']], $g);
        }

        $total = array_sum(array_map(fn($g) => count($g['images']), $galerias));
        $this->command->info('✅ ' . count($galerias) . ' galerías cargadas con ' . $total . ' imágenes reales (Wikimedia Commons).');
    }
}
