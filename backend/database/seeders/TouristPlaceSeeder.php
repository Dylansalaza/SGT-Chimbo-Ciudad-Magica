<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TouristPlace;

/**
 * Lugares turísticos reales de San José de Chimbo (provincia de Bolívar, Ecuador).
 * Usa updateOrCreate (por nombre) para que sea seguro ejecutarlo varias veces
 * sin duplicar registros.
 *
 *   php artisan db:seed --class=TouristPlaceSeeder
 */
class TouristPlaceSeeder extends Seeder
{
    public function run(): void
    {
        $lugares = [
            [
                'nombre'      => 'Iglesia Matriz de San José',
                'categoria'   => 'Cultura',
                'descripcion' => 'Templo principal del cantón, de arquitectura tradicional, ubicado frente al parque central. Es uno de los íconos religiosos y patrimoniales de San José de Chimbo.',
                'lat'         => -1.676600, 'lng' => -79.038600,
                'direccion'   => 'Frente al Parque Central, San José de Chimbo',
                'telefono'    => '+593 3 298 0100',
                'horario'     => '07:00 - 19:00',
                'precio'      => 'Gratis',
                'imagen_url'  => 'https://images.unsplash.com/photo-1548276145-69a9521f0499?auto=format&fit=crop&w=1000&q=80',
                'destacado'   => true,
            ],
            [
                'nombre'      => 'Parque Central',
                'categoria'   => 'Parque',
                'descripcion' => 'Corazón de la ciudad y punto de encuentro de propios y visitantes. Rodeado de edificaciones tradicionales y áreas verdes.',
                'lat'         => -1.676900, 'lng' => -79.038900,
                'direccion'   => 'Centro de San José de Chimbo',
                'telefono'    => '+593 3 298 0100',
                'horario'     => '24 horas',
                'precio'      => 'Gratis',
                'imagen_url'  => 'https://images.unsplash.com/photo-1519331379826-f10be5486c6f?auto=format&fit=crop&w=1000&q=80',
                'destacado'   => true,
            ],
            [
                'nombre'      => 'Santuario del Huayco',
                'categoria'   => 'Cultura',
                'descripcion' => 'Importante santuario religioso, sitio de peregrinación y devoción mariana. Vistas del valle y entorno natural.',
                'lat'         => -1.690000, 'lng' => -79.030000,
                'direccion'   => 'Sector El Huayco, San José de Chimbo',
                'telefono'    => '+593 3 298 0150',
                'horario'     => '06:00 - 18:00',
                'precio'      => 'Gratis',
                'imagen_url'  => 'https://images.unsplash.com/photo-1438032005730-c779502df39b?auto=format&fit=crop&w=1000&q=80',
                'destacado'   => true,
            ],
            [
                'nombre'      => 'Tambán — Artesanías',
                'categoria'   => 'Cultura',
                'descripcion' => 'Comunidad reconocida por la artesanía tradicional y el trabajo de sus maestros artesanos. Atractivo cultural emblemático del cantón.',
                'lat'         => -1.665000, 'lng' => -79.050000,
                'direccion'   => 'Parroquia Tambán, San José de Chimbo',
                'telefono'    => '+593 3 298 0200',
                'horario'     => '08:00 - 17:00',
                'precio'      => 'Gratis',
                'imagen_url'  => 'https://images.unsplash.com/photo-1528728329032-2972f65dfb3f?auto=format&fit=crop&w=1000&q=80',
                'destacado'   => true,
            ],
            [
                'nombre'      => 'Mirador de Chimbo',
                'categoria'   => 'Mirador',
                'descripcion' => 'Punto panorámico con vistas del valle de Chimbo y los Andes. Ideal para fotografía y atardeceres.',
                'lat'         => -1.682500, 'lng' => -79.043500,
                'direccion'   => 'Cerro de Chimbo',
                'telefono'    => '+593 3 298 0100',
                'horario'     => '24 horas',
                'precio'      => 'Gratis',
                'imagen_url'  => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=1000&q=80',
                'destacado'   => false,
            ],
            [
                'nombre'      => 'Cascada La Chorrera',
                'categoria'   => 'Naturaleza',
                'descripcion' => 'Caída de agua rodeada de vegetación nativa, con pozas naturales. Perfecta para senderismo y contacto con la naturaleza.',
                'lat'         => -1.672500, 'lng' => -79.055000,
                'direccion'   => 'Vía rural, San José de Chimbo',
                'telefono'    => '+593 3 298 0100',
                'horario'     => '08:00 - 16:00',
                'precio'      => 'Gratis',
                'imagen_url'  => 'https://images.unsplash.com/photo-1432405972618-c60b0225b8f9?auto=format&fit=crop&w=1000&q=80',
                'destacado'   => false,
            ],
            [
                'nombre'      => 'Plaza Gastronómica',
                'categoria'   => 'Gastronomía',
                'descripcion' => 'Espacio para disfrutar de la gastronomía típica del cantón: hornado, fritada, y dulces tradicionales.',
                'lat'         => -1.677500, 'lng' => -79.037800,
                'direccion'   => 'Mercado central, San José de Chimbo',
                'telefono'    => '+593 3 298 0100',
                'horario'     => '08:00 - 20:00',
                'precio'      => 'Variable',
                'imagen_url'  => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1000&q=80',
                'destacado'   => false,
            ],
            [
                'nombre'      => 'Laguna de la zona alta',
                'categoria'   => 'Naturaleza',
                'descripcion' => 'Cuerpo de agua andino rodeado de páramo, ideal para avistamiento de aves y caminatas.',
                'lat'         => -1.700000, 'lng' => -79.020000,
                'direccion'   => 'Zona alta de San José de Chimbo',
                'telefono'    => '+593 3 298 0100',
                'horario'     => '08:00 - 17:00',
                'precio'      => '$1',
                'imagen_url'  => 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?auto=format&fit=crop&w=1000&q=80',
                'destacado'   => false,
            ],

            // ── 🍽️ ¿QUÉ COMER? — Restaurantes y cafeterías ──────────────────
            [
                'nombre'      => 'Restaurante El Hornado Chimbeño',
                'categoria'   => 'Restaurante',
                'descripcion' => 'Cocina típica del cantón: hornado en horno de leña, fritada con mote y caldo de gallina criolla.',
                'lat'         => -1.677200, 'lng' => -79.039400,
                'direccion'   => 'Calle Sucre y 3 de Marzo, San José de Chimbo',
                'telefono'    => '+593 3 298 0300',
                'horario'     => '08:00 - 21:00',
                'precio'      => '$5',
                'imagen_url'  => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1000&q=80',
                'destacado'   => false,
            ],
            [
                'nombre'      => 'Cafetería Colaciones y Café',
                'categoria'   => 'Cafetería',
                'descripcion' => 'Café de altura, colaciones tradicionales y repostería artesanal frente al parque central.',
                'lat'         => -1.676300, 'lng' => -79.038300,
                'direccion'   => 'Frente al Parque Central, San José de Chimbo',
                'telefono'    => '+593 3 298 0310',
                'horario'     => '07:30 - 20:00',
                'precio'      => '$3',
                'imagen_url'  => 'https://images.unsplash.com/photo-1445116572660-236099ec97a0?auto=format&fit=crop&w=1000&q=80',
                'destacado'   => false,
            ],

            // ── 🛏️ ¿DÓNDE DORMIR? — Hoteles, hostales y hosterías ───────────
            [
                'nombre'      => 'Hotel San José Plaza',
                'categoria'   => 'Hotel',
                'descripcion' => 'Hotel céntrico con habitaciones confortables, desayuno incluido y fácil acceso a los atractivos del cantón.',
                'lat'         => -1.678100, 'lng' => -79.039900,
                'direccion'   => 'Av. 3 de Marzo, San José de Chimbo',
                'telefono'    => '+593 3 298 0400',
                'horario'     => 'Recepción 24 horas',
                'precio'      => '$35',
                'imagen_url'  => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1000&q=80',
                'destacado'   => false,
            ],
            [
                'nombre'      => 'Hostal El Viajero',
                'categoria'   => 'Hostal',
                'descripcion' => 'Hospedaje económico y acogedor, ideal para mochileros y visitantes de paso por el cantón.',
                'lat'         => -1.675400, 'lng' => -79.037200,
                'direccion'   => 'Calle Bolívar, San José de Chimbo',
                'telefono'    => '+593 3 298 0410',
                'horario'     => 'Recepción 24 horas',
                'precio'      => '$15',
                'imagen_url'  => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1000&q=80',
                'destacado'   => false,
            ],
            [
                'nombre'      => 'Hostería Valle Verde',
                'categoria'   => 'Hostería',
                'descripcion' => 'Hostería campestre con jardines, piscina y gastronomía típica, a pocos minutos del centro.',
                'lat'         => -1.686000, 'lng' => -79.045000,
                'direccion'   => 'Vía a Telimbela, San José de Chimbo',
                'telefono'    => '+593 3 298 0420',
                'horario'     => 'Recepción 24 horas',
                'precio'      => '$45',
                'imagen_url'  => 'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?auto=format&fit=crop&w=1000&q=80',
                'destacado'   => false,
            ],
        ];

        foreach ($lugares as $lugar) {
            TouristPlace::updateOrCreate(['nombre' => $lugar['nombre']], $lugar);
        }

        $this->command->info('Lugares turísticos de San José de Chimbo cargados: ' . count($lugares));
    }
}
