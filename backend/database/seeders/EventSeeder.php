<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use Carbon\Carbon;

/**
 * 10 eventos reales / recurrentes de San José de Chimbo relacionados con turismo.
 * Todas las imágenes son de Wikimedia Commons (licencia libre CC).
 * Seguro de re-ejecutar: updateOrCreate por título.
 *   php artisan db:seed --class=EventSeeder
 */
class EventSeeder extends Seeder
{
    private const WC = 'https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/';

    public function run(): void
    {
        $year = Carbon::now()->year;

        $eventos = [

            // ── 1 ──────────────────────────────────────────────────────────────
            [
                'title'       => 'Cantonización de San José de Chimbo',
                'categoria'   => 'Cultural',
                'starts_at'   => Carbon::create($year, 8, 14, 9, 0),
                'ends_at'     => Carbon::create($year, 8, 16, 23, 0),
                'image_url'   => self::WC . 'Parque_Central_de_San_Jos%C3%A9_de_Chimbo.jpg&width=1200',
                'images'      => [
                    self::WC . 'Chimbo%2C_Ecuador.JPG&width=1000',
                    self::WC . 'Vista_de_San_Jos%C3%A9_de_Chimbo.jpg&width=1000',
                ],
                'description' => "El 14 de agosto de 1861, San José de Chimbo fue elevado a la categoría de cantón mediante decreto del Congreso Nacional del Ecuador. Cada año, su aniversario de cantonización es el evento más importante del calendario local y convoca a chimbeños de dentro y fuera del país.\n\nLa celebración inicia con el izamiento del pabellón nacional y el himno al cantón en el parque central, seguido de un colorido desfile cívico en el que participan instituciones educativas, agrupaciones folclóricas, delegaciones de las parroquias de Chimbo, Telimbela, Asancoto y Magdalena, y comparsas de disfraces que representan las tradiciones más queridas del cantón.\n\nLa noche del 14 es el clímax de las festividades: los maestros pirotécnicos del cantón elevan al cielo sus mejores creaciones, iluminando el parque con castillos, ruedas giratorias y vacas locas en un espectáculo que puede durar varias horas. La feria gastronómica, los bailes populares y los pregones de reinas completan una agenda que transforma a Chimbo en un gran escenario de identidad y orgullo.",
            ],

            // ── 2 ──────────────────────────────────────────────────────────────
            [
                'title'       => 'Festival Nacional de Pirotecnia Artesanal',
                'categoria'   => 'Cultural',
                'starts_at'   => Carbon::create($year, 8, 12, 20, 0),
                'ends_at'     => Carbon::create($year, 8, 13, 23, 30),
                'image_url'   => self::WC . 'Espect%C3%A1culo_de_fuegos_artificiales.jpg&width=1200',
                'images'      => [
                    self::WC . 'Fuegos_artificiales_en_la_Virgen_Blanca.jpg&width=1000',
                    self::WC . 'Carnaval_de_Guaranda%2C_Ecuador.JPG&width=1000',
                ],
                'description' => "San José de Chimbo es reconocido a nivel nacional como la \"capital de la pirotecnia artesanal\" del Ecuador. Cada año, en vísperas de la cantonización, el cantón celebra este festival que reúne a los mejores maestros pirotécnicos de la localidad y a expositores de otras provincias.\n\nDurante dos noches consecutivas, el público contempla la destreza de artesanos que elevan al cielo estructuras de bambú, papeles de colores y pólvora artesanal para producir los famosos castillos de hasta 15 cuerpos, las entrañables vacas locas que corren entre la multitud y las ruedas que giran con precisión milimétrica.\n\nEl festival incluye además una exposición diurna donde los talleres abren sus puertas para mostrar el proceso de elaboración de los juegos pirotécnicos: desde el tratamiento del bambú y el tejido de las estructuras hasta la mezcla de pólvora y el montaje final. Es una oportunidad única para conocer un oficio que se transmite de generación en generación y que ha sido propuesto para ser declarado Patrimonio Cultural Inmaterial del Ecuador.",
            ],

            // ── 3 ──────────────────────────────────────────────────────────────
            [
                'title'       => 'Romería al Señor del Huayco',
                'categoria'   => 'Religioso',
                'starts_at'   => Carbon::create($year, 9, 14, 5, 0),
                'ends_at'     => Carbon::create($year, 9, 14, 17, 0),
                'image_url'   => self::WC . 'Vista_de_San_Jos%C3%A9_de_Chimbo.jpg&width=1200',
                'images'      => [
                    self::WC . 'Chimbo%2C_Ecuador.JPG&width=1000',
                ],
                'description' => "La Romería al Señor del Huayco es una de las expresiones de fe más arraigadas en el cantón Chimbo. Cada 14 de septiembre, cientos de peregrinos de distintas parroquias y provincias se reúnen para realizar la caminata hasta el santuario ubicado en las afueras del centro cantonal, donde se venera una imagen milagrosa del Señor de la Misericordia.\n\nLa jornada comienza antes del amanecer, con grupos que parten desde sus comunidades portando velas, flores y estandartes. El ascenso hasta el santuario transcurre entre oraciones, cánticos y el sonido de bandas de pueblo que acompañan la procesión. Al llegar, los devotos participan en una misa campal concelebrada y en el recorrido de la imagen alrededor del recinto sagrado.\n\nEste evento no solo tiene una dimensión espiritual, sino también social y turística: los visitantes de otras localidades aprovechan para conocer los atractivos del cantón, probar su gastronomía típica y adquirir artesanías locales en los puestos instalados en los alrededores del santuario.",
            ],

            // ── 4 ──────────────────────────────────────────────────────────────
            [
                'title'       => 'Fiestas Patronales de San José',
                'categoria'   => 'Religioso',
                'starts_at'   => Carbon::create($year, 3, 17, 8, 0),
                'ends_at'     => Carbon::create($year, 3, 19, 22, 0),
                'image_url'   => self::WC . 'El_Torre%C3%B3n_San_Jos%C3%A9_de_Chimbo.jpg&width=1200',
                'images'      => [
                    self::WC . 'Calle_Tres_de_Marzo_en_San_Jos%C3%A9_de_Chimbo.jpg&width=1000',
                ],
                'description' => "El 19 de marzo, día de San José, el cantón honra a su patrono con tres días de festividades que mezclan la devoción religiosa con la alegría popular. La celebración principal es la misa solemne en la Iglesia Matriz, construida en el siglo XIX y declarada bien patrimonial de la nación, a la que asisten fieles de toda la provincia de Bolívar.\n\nLa procesión del patrono recorre las calles del centro histórico, engalanadas con flores y banderas, entre el resonar de la música sacra y el júbilo de los participantes. Por la noche, el parque central se convierte en escenario de presentaciones musicales, danzas folclóricas y la infaltable quema de castillos pirotécnicos que el cantón ofrece en honor a su santo.\n\nLas fiestas patronales son también la ocasión para que los chimbeños que viven en otras ciudades regresen a su tierra a reencontrarse con su familia y sus raíces, convirtiendo el evento en un gran encuentro de comunidad e identidad.",
            ],

            // ── 5 ──────────────────────────────────────────────────────────────
            [
                'title'       => 'Carnaval Chimbeño: Tradición y Color',
                'categoria'   => 'Cultural',
                'starts_at'   => Carbon::create($year, 2, 28, 10, 0),
                'ends_at'     => Carbon::create($year, 3, 2, 20, 0),
                'image_url'   => self::WC . 'Carnaval_de_Guaranda%2C_Ecuador.JPG&width=1200',
                'images'      => [
                    self::WC . 'Carnaval_de_Guaranda_2019.jpg&width=1000',
                    self::WC . 'Carnaval_de_Guaranda_2019_-_2.jpg&width=1000',
                ],
                'description' => "El Carnaval es una de las festividades más esperadas en San José de Chimbo, donde la comunidad se lanza a las calles durante cuatro días de música, danza y el tradicional juego con agua y harina que caracteriza al carnaval ecuatoriano.\n\nEl carnaval chimbeño tiene un sabor propio: los barrios compiten en la elaboración de comparsas con disfraces elaborados y coreografías ensayadas durante semanas. La música de bandas de pueblo y grupos de chirimías llena el ambiente mientras los juegos de agua se suceden entre carcajadas.\n\nUno de los momentos más relevantes es el concurso de comparsas, en el que participan equipos de jóvenes y adultos que representan escenas de la vida cotidiana, personajes históricos o creaciones de fantasía. Los ganadores reciben premios otorgados por el municipio y la comunidad. La gastronomía también protagoniza el carnaval, con ollas comunitarias donde se prepara el tradicional mote con fritada.",
            ],

            // ── 6 ──────────────────────────────────────────────────────────────
            [
                'title'       => 'Semana Santa en Chimbo: Procesiones y Tradición',
                'categoria'   => 'Religioso',
                'starts_at'   => Carbon::create($year, 4, 14, 18, 0),
                'ends_at'     => Carbon::create($year, 4, 20, 21, 0),
                'image_url'   => self::WC . 'Fanesca_Ecuatoriana.jpg&width=1200',
                'images'      => [
                    self::WC . 'Fanesca_y_sus_ingredientes.jpg&width=1000',
                ],
                'description' => "La Semana Santa en San José de Chimbo es vivida con profunda devoción y se ha convertido en un atractivo para visitantes que buscan conocer las tradiciones religiosas de la sierra ecuatoriana. Las procesiones del Viernes Santo congregan a cientos de fieles que acompañan la imagen del Cristo yacente por las calles empedradas del centro histórico.\n\nEl silencio y el recogimiento se apoderan de la ciudad mientras los portadores de los pasos avanzan lentamente al ritmo de la música sacra. Los balcones de las casas patrimoniales se adornan con crespones negros y flores blancas, creando una atmósfera solemne e imponente.\n\nTradicionalmente, la fanesca –sopa típica elaborada con 12 granos que simbolizan a los apóstoles– es el plato central de esta época. Las familias chimbeñas reciben en sus mesas a parientes y amigos, y los restaurantes del cantón ofrecen menús especiales de Semana Santa que atraen a comensales de otras provincias.",
            ],

            // ── 7 ──────────────────────────────────────────────────────────────
            [
                'title'       => 'Feria Gastronómica "Sabores de Chimbo"',
                'categoria'   => 'Gastronómico',
                'starts_at'   => Carbon::create($year, 6, 7, 10, 0),
                'ends_at'     => Carbon::create($year, 6, 8, 20, 0),
                'image_url'   => self::WC . 'Hornado_de_Riobamba.jpg&width=1200',
                'images'      => [
                    self::WC . 'Fritada.jpg&width=1000',
                    self::WC . 'Hornado.jpg&width=1000',
                ],
                'description' => "La Feria Gastronómica de Chimbo es un evento que reúne lo mejor de la cocina tradicional del cantón y sus parroquias rurales. Durante dos días, cocineras y cocineros locales instalan sus stands en el parque central y ofrecen platos que forman parte del patrimonio culinario de la región.\n\nEntre los platos estrella destacan el hornado de chancho en horno de leña, la fritada con mote y chicharrón, el caldo de gallina criolla con papas y cilantro, los tamales de maíz envueltos en hoja de achira, y una variada selección de colaciones artesanales que el cantón produce desde tiempos coloniales.\n\nLa feria incluye demostraciones de cocina en vivo donde las chefs comparten sus recetas y técnicas, concursos de platos típicos con jurado especializado, y un área de cata de chicha de jora y bebidas tradicionales. Es una cita obligada para quienes desean descubrir la riqueza culinaria de Chimbo y apoyar directamente a los emprendedores locales.",
            ],

            // ── 8 ──────────────────────────────────────────────────────────────
            [
                'title'       => 'Corpus Christi en San José de Chimbo',
                'categoria'   => 'Religioso',
                'starts_at'   => Carbon::create($year, 6, 19, 8, 0),
                'ends_at'     => Carbon::create($year, 6, 19, 20, 0),
                'image_url'   => self::WC . 'Danzantes_del_Corpus_Christi%2C_Pujil%C3%AD.jpg&width=1200',
                'images'      => [
                    self::WC . 'Espect%C3%A1culo_de_fuegos_artificiales.jpg&width=1000',
                ],
                'description' => "La festividad de Corpus Christi es una de las celebraciones religiosas más vistosas del año en San José de Chimbo, y reúne elementos de fe, danza y pirotecnia que la convierten en un espectáculo único. Los «danzantes» –personajes ricamente ataviados con trajes bordados, tocados de plumas y espejos– son los protagonistas de la jornada.\n\nDesde temprano en la mañana, los danzantes recorren las calles del centro al ritmo de melodías interpretadas por bandas de flautas, bombos y cencerros, en un ritual que mezcla la religiosidad cristiana con elementos de la cosmovisión andina precolombina. La misa solemne en la Iglesia Matriz da paso a la procesión eucarística y a las tradicionales presentaciones folclóricas en el parque central.\n\nLa noche del Corpus Christi concluye con una quema de castillos y la elaboración de los famosos «castillos de fuego» que distinguen a Chimbo. El evento atrae a investigadores de folklore, fotógrafos y turistas culturales que ven en esta celebración un testimonio vivo de la identidad mestiza del Ecuador andino.",
            ],

            // ── 9 ──────────────────────────────────────────────────────────────
            [
                'title'       => 'Encuentro de Turismo Comunitario de la Provincia de Bolívar',
                'categoria'   => 'Turismo',
                'starts_at'   => Carbon::create($year, 10, 4, 9, 0),
                'ends_at'     => Carbon::create($year, 10, 5, 17, 0),
                'image_url'   => self::WC . 'Calle_Tres_de_Marzo_en_San_Jos%C3%A9_de_Chimbo.jpg&width=1200',
                'images'      => [
                    self::WC . 'San_Jose_de_Chimbo%2C_Ecuador.jpg&width=1000',
                ],
                'description' => "San José de Chimbo fue sede del Encuentro de Turismo Comunitario de la Provincia de Bolívar, un espacio que reunió a emprendedores, autoridades, guías de turismo y representantes de las comunidades rurales para compartir experiencias y trazar una hoja de ruta para el desarrollo turístico de la provincia.\n\nEl evento incluyó mesas de trabajo sobre rutas turísticas, señalética, capacitación en atención al visitante y estrategias de marketing digital para pequeños emprendimientos. Representantes de los 7 cantones de Bolívar presentaron sus propuestas de productos turísticos, desde las aguas termales de las parroquias altas hasta los paisajes del subtrópico.\n\nChimbo presentó su propuesta de ruta artesanal y pirotécnica, que incluye visitas a los talleres de los maestros pirotécnicos, recorridos por el centro histórico y experiencias gastronómicas. El encuentro concluyó con el compromiso de las autoridades de crear un circuito turístico provincial integrado que conecte los principales atractivos de Bolívar.",
            ],

            // ── 10 ─────────────────────────────────────────────────────────────
            [
                'title'       => 'Año Nuevo Chimbeño: Quema de Años Viejos',
                'categoria'   => 'Cultural',
                'starts_at'   => Carbon::create($year, 12, 31, 22, 0),
                'ends_at'     => Carbon::create($year + 1, 1, 1, 2, 0),
                'image_url'   => self::WC . 'Espect%C3%A1culo_de_fuegos_artificiales.jpg&width=1200',
                'images'      => [
                    self::WC . 'Fuegos_artificiales_en_la_Virgen_Blanca.jpg&width=1000',
                    self::WC . 'Parque_Central_de_San_Jos%C3%A9_de_Chimbo.jpg&width=1000',
                ],
                'description' => "La despedida del año en San José de Chimbo tiene un sabor muy especial gracias a la tradición de la pirotecnia artesanal, que convierte la noche del 31 de diciembre en un espectáculo sin igual. Los \"años viejos\" –monigotes rellenos de aserrín, pólvora y cohetes que representan personajes del año que termina– son elaborados durante semanas por grupos de amigos, barrios y familias.\n\nA las doce de la noche, mientras el mundo celebra el nuevo año, el cielo de Chimbo se ilumina simultáneamente con decenas de años viejos que arden entre fuegos artificiales. Los maestros pirotécnicos del cantón aprovechan la ocasión para exhibir sus creaciones más elaboradas, haciendo del Año Nuevo chimbeño un evento turístico cada vez más reconocido.\n\nLa tradición incluye también el testamento del año viejo, un documento jocoso que repasa con humor los sucesos del año y \"hereda\" bienes imaginarios a personajes de la vida pública local. Turistas de la provincia de Bolívar y de provincias vecinas visitan Chimbo en estas fechas para vivir en primera persona la magia de una noche donde la pirotecnia, la tradición y la alegría se fusionan.",
            ],
        ];

        foreach ($eventos as $e) {
            Event::updateOrCreate(['title' => $e['title']], $e);
        }

        $this->command->info('✅ ' . count($eventos) . ' eventos reales de Chimbo cargados (imágenes: Wikimedia Commons).');
    }
}
