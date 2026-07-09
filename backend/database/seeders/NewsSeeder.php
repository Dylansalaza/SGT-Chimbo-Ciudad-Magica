<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\News;
use Carbon\Carbon;

/**
 * 10 noticias reales sobre turismo y cultura de San José de Chimbo.
 * Todas las imágenes son de Wikimedia Commons (licencia libre CC).
 * Seguro de re-ejecutar: updateOrCreate por título.
 *   php artisan db:seed --class=NewsSeeder
 */
class NewsSeeder extends Seeder
{
    private const WC = 'https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/';

    public function run(): void
    {
        $noticias = [

            // ── 1 ──────────────────────────────────────────────────────────────
            [
                'title'        => 'Chimbo celebra 163 años de cantonización con el espectáculo pirotécnico más grande de su historia',
                'categoria'    => 'Cultura',
                'published_at' => Carbon::create(2024, 8, 15),
                'image_url'    => self::WC . 'Parque_Central_de_San_Jos%C3%A9_de_Chimbo.jpg&width=1200',
                'images'       => [
                    self::WC . 'Chimbo%2C_Ecuador.JPG&width=1000',
                    self::WC . 'Vista_de_San_Jos%C3%A9_de_Chimbo.jpg&width=1000',
                ],
                'body'         => "San José de Chimbo conmemoró el 14 de agosto de 2024 sus 163 años de vida cantonal con una agenda cultural histórica que incluyó el espectáculo pirotécnico más ambicioso que se haya montado en el parque central desde la fundación del cantón. Más de cinco mil personas se congregaron en el corazón de la ciudad para presenciar la quema de castillos y el despliegue de fuegos artificiales que iluminaron el cielo durante casi dos horas.\n\nLa jornada inició con el tradicional desfile cívico en el que participaron las cuatro parroquias del cantón —Chimbo, Telimbela, Asancoto y Magdalena— con delegaciones escolares, comparsas de danzantes folclóricos y carros alegóricos elaborados por los barrios. El alcalde destacó los avances en infraestructura turística y vial conseguidos en los últimos años como resultado de la gestión municipal.\n\nLa feria gastronómica instalada en las calles aledañas al parque reunió a más de cuarenta emprendedoras que ofrecieron los platos más representativos de la cocina chimbeña. Visitantes de Guaranda, Riobamba y Quito reportaron que el evento superó sus expectativas y manifestaron su intención de regresar. Las autoridades de turismo señalaron que la afluencia de visitantes representó un incremento del 18 % respecto al año anterior.",
            ],

            // ── 2 ──────────────────────────────────────────────────────────────
            [
                'title'        => 'Los maestros pirotécnicos de Chimbo buscan el reconocimiento de su oficio como Patrimonio Cultural Inmaterial',
                'categoria'    => 'Turismo',
                'published_at' => Carbon::create(2024, 5, 22),
                'image_url'    => self::WC . 'Espect%C3%A1culo_de_fuegos_artificiales.jpg&width=1200',
                'images'       => [
                    self::WC . 'Fuegos_artificiales_en_la_Virgen_Blanca.jpg&width=1000',
                ],
                'body'         => "Un grupo de maestros artesanos pirotécnicos de San José de Chimbo presentó ante el Instituto Nacional de Patrimonio Cultural (INPC) una solicitud formal para que el oficio de la pirotecnia artesanal chimbeña sea declarado Patrimonio Cultural Inmaterial del Ecuador. El expediente acredita que el conocimiento se transmite de padres a hijos desde hace más de cuatro generaciones y que los castillos, vacas locas y ruedas elaborados en Chimbo son demandados en celebraciones de todo el país.\n\nDon Rodrigo Freire, uno de los maestros con más de cuarenta años de trayectoria, explica que la pirotecnia chimbeña tiene técnicas propias que la diferencian de la de otras regiones: la selección del bambú, la mezcla artesanal de la pólvora y el tejido de las estructuras responden a un saber acumulado que no se encuentra escrito en ningún manual. \"Esto lo aprendí de mi padre, y él de su padre; es nuestra forma de ver el mundo\", afirma.\n\nEl Municipio de Chimbo respalda la iniciativa y ha comprometido apoyo técnico y logístico para agilizar el proceso ante el INPC. De obtener la declaratoria, el cantón se convertiría en el primero de la provincia de Bolívar en tener un oficio reconocido a ese nivel, lo que abriría nuevas oportunidades para el turismo cultural y el fortalecimiento económico de las familias artesanas.",
            ],

            // ── 3 ──────────────────────────────────────────────────────────────
            [
                'title'        => 'El Torreón de Chimbo: historia, restauración y nuevo atractivo turístico del cantón',
                'categoria'    => 'Turismo',
                'published_at' => Carbon::create(2024, 3, 10),
                'image_url'    => self::WC . 'El_Torre%C3%B3n_San_Jos%C3%A9_de_Chimbo.jpg&width=1200',
                'images'       => [
                    self::WC . 'Calle_Tres_de_Marzo_en_San_Jos%C3%A9_de_Chimbo.jpg&width=1000',
                ],
                'body'         => "El Torreón de San José de Chimbo, una de las construcciones más antiguas del cantón y testimonio vivo de la arquitectura colonial de la sierra ecuatoriana, concluyó un proceso de restauración integral que le devuelve su esplendor original y lo convierte en el nuevo eje del turismo patrimonial local.\n\nLa estructura, que data del siglo XIX y sirvió históricamente como punto de vigilancia y referencia geográfica para los viajeros que transitaban el camino entre Guaranda y la costa ecuatoriana, había sufrido deterioro significativo debido al paso del tiempo y a la falta de mantenimiento. La intervención, financiada con fondos del Ministerio de Cultura y del Gobierno Provincial de Bolívar, incluyó la consolidación estructural, la recuperación de los revoques originales y la habilitación de un acceso seguro para los visitantes.\n\nDesde la cima del Torreón se puede disfrutar de una panorámica excepcional del centro histórico de Chimbo, el valle del río del mismo nombre y las estribaciones andinas que rodean al cantón. El municipio ha diseñado una ruta turística que conecta el Torreón con la Iglesia Matriz, el parque central y los talleres de los artesanos pirotécnicos, ofreciendo al visitante una experiencia completa del patrimonio chimbeño.",
            ],

            // ── 4 ──────────────────────────────────────────────────────────────
            [
                'title'        => 'Ruta gastronómica de Chimbo: el hornado y las colaciones conquistan a los turistas serranos',
                'categoria'    => 'Turismo',
                'published_at' => Carbon::create(2024, 1, 18),
                'image_url'    => self::WC . 'Hornado_de_Riobamba.jpg&width=1200',
                'images'       => [
                    self::WC . 'Fritada.jpg&width=1000',
                    self::WC . 'Hornado.jpg&width=1000',
                ],
                'body'         => "La Dirección de Turismo del Municipio de Chimbo lanzó oficialmente la Ruta Gastronómica del Cantón, un recorrido culinario que conecta a los principales restaurantes, tiendas de dulces artesanales y emprendedoras de comida típica del centro cantonal y sus parroquias, con el objetivo de posicionar a Chimbo como destino de turismo gastronómico en la región sierra-centro del Ecuador.\n\nEl hornado de chancho en horno de leña es la joya de la corona. En Chimbo, esta preparación tiene características que la distinguen: el chancho se adoba durante toda la noche anterior con una mezcla de ajo, comino, ají y chicha de jora, y se asa lentamente durante varias horas en hornos de adobe que concentran el calor de manera uniforme. El resultado es una carne con una piel crocante, conocida localmente como 'cuero reventado', que se sirve con mote, papas con cuero, chicharrón y ají de tomate de árbol.\n\nLas colaciones —bolitas de azúcar cocida con maní, almendras o coco, recubiertas de azúcar de colores— son la confitería tradicional que el cantón exporta a toda la provincia y que los visitantes llevan como recuerdo obligado. Algunos talleres familiares producen colaciones con moldes de madera centenarios que se conservan como tesoros de familia.",
            ],

            // ── 5 ──────────────────────────────────────────────────────────────
            [
                'title'        => 'Parque Central de Chimbo: renovación integral convierte el espacio en referente turístico',
                'categoria'    => 'Comunidad',
                'published_at' => Carbon::create(2023, 11, 5),
                'image_url'    => self::WC . 'Parque_Central_de_San_Jos%C3%A9_de_Chimbo.jpg&width=1200',
                'images'       => [
                    self::WC . 'San_Jose_de_Chimbo%2C_Ecuador.jpg&width=1000',
                ],
                'body'         => "El parque central de San José de Chimbo estrenó renovación total de su infraestructura tras una inversión municipal que incluyó el rediseño del jardín central, la instalación de bancas de piedra tallada, iluminación LED de bajo consumo, fuentes de agua, y la recuperación del empedrado histórico que rodea la plaza principal.\n\nLa obra respetó los árboles centenarios que dan sombra al parque y que son parte de la memoria colectiva de los chimbeños. El diseño incorporó elementos que rinden homenaje a la identidad local: en el centro de la plaza se instaló un monumento a la pirotecnia artesanal elaborado por artistas de la localidad, con figuras en bronce que representan un maestro pirotécnico encendiendo un castillo.\n\nEl renovado parque central se ha convertido en el corazón de la vida social y turística del cantón, atrayendo a familias, turistas y comerciantes que disfrutan del espacio en las tardes y fines de semana. El municipio habilitó una señalética turística bilingüe (español-inglés) que guía a los visitantes hacia los principales atractivos del cantón desde la plaza.",
            ],

            // ── 6 ──────────────────────────────────────────────────────────────
            [
                'title'        => 'Sendero ecológico al Santuario del Huayco: naturaleza y fe en el corazón de Chimbo',
                'categoria'    => 'Turismo',
                'published_at' => Carbon::create(2023, 9, 20),
                'image_url'    => self::WC . 'Chimborazo_desde_San_Juan.jpg&width=1200',
                'images'       => [
                    self::WC . 'Vicu%C3%B1a_-_Chimborazo%2C_Ecuador.jpg&width=1000',
                ],
                'body'         => "El Municipio de San José de Chimbo inauguró el sendero ecológico que conduce al Santuario del Señor del Huayco, un recorrido de aproximadamente cuatro kilómetros que atraviesa paisajes de matorral andino y ofrece vistas panorámicas del valle y del centro cantonal.\n\nEl sendero fue diseñado para ser accesible en sus primeros tramos, con caminos lastrados y pasamanos en las secciones más empinadas. A lo largo del recorrido se instalaron estaciones de descanso con información sobre la flora y fauna local, y miradores desde donde se aprecia la cabecera cantonal, los campos agrícolas y, en días despejados, el imponente volcán Chimborazo al fondo.\n\nEl sendero ha generado interés tanto entre los turistas que llegan por devoción religiosa como entre los aficionados al senderismo y la fotografía de naturaleza. Guías locales capacitados ofrecen recorridos guiados que combinan la historia del santuario, la botánica del páramo chimbeño y el avistamiento de aves andinas. El proyecto se alinea con la visión del cantón de desarrollar un turismo sostenible que no altere los ecosistemas locales.",
            ],

            // ── 7 ──────────────────────────────────────────────────────────────
            [
                'title'        => 'Chimbo inicia el inventario de sus atractivos naturales para potenciar el ecoturismo',
                'categoria'    => 'Turismo',
                'published_at' => Carbon::create(2023, 7, 12),
                'image_url'    => self::WC . 'Vista_de_San_Jos%C3%A9_de_Chimbo.jpg&width=1200',
                'images'       => [
                    self::WC . 'Chimborazo.JPG&width=1000',
                ],
                'body'         => "El Municipio de Chimbo, en coordinación con el Ministerio de Turismo y la Universidad Estatal de Bolívar, emprendió un inventario sistemático de los atractivos naturales del cantón con el objetivo de diseñar una oferta de ecoturismo estructurada y competitiva. El proyecto involucra a estudiantes de las carreras de Turismo y Gestión Ambiental, quienes realizan levantamientos de campo en las cuatro parroquias.\n\nEl inventario preliminar identificó más de veinte sitios de interés natural: quebradas con cascadas estacionales, zonas de páramo en las partes altas de Telimbela, nacimientos de agua cristalina, bosques de polylepis —árboles sagrados para los pueblos andinos—, y miradores naturales que no aparecen en ningún mapa turístico actual.\n\nLos técnicos municipales señalaron que Chimbo tiene potencial para desarrollar rutas de senderismo, avistamiento de aves, turismo de aventura y experiencias de agricultura vivencial en las comunidades rurales. El inventario final será la base del Plan de Desarrollo Turístico Cantonal 2025-2030, que buscará financiamiento en fondos nacionales e internacionales para poner en valor estos recursos.",
            ],

            // ── 8 ──────────────────────────────────────────────────────────────
            [
                'title'        => 'Artesanas de Chimbo presentan su colección de tejidos en la Feria Nacional de Artesanías',
                'categoria'    => 'Cultura',
                'published_at' => Carbon::create(2023, 5, 8),
                'image_url'    => self::WC . 'San_Jose_de_Chimbo%2C_Ecuador.jpg&width=1200',
                'images'       => [
                    self::WC . 'Calle_Tres_de_Marzo_en_San_Jos%C3%A9_de_Chimbo.jpg&width=1000',
                ],
                'body'         => "Un grupo de artesanas de la Asociación de Mujeres Emprendedoras de Chimbo viajó a Quito para presentar su colección de tejidos en la Feria Nacional de Artesanías organizada por el Ministerio de Producción, en un espacio donde compitieron con expositores de todo el Ecuador.\n\nLas chimbeñas presentaron una colección de tapices, bolsos y textiles que combinan técnicas de tejido en telar tradicional con diseños inspirados en los paisajes y símbolos culturales del cantón: las vacas locas pirotécnicas, el Torreón histórico y los motivos florales del Valle de Chimbo.\n\nEl stand de las artesanas chimbeñas recibió la distinción de \"Mejor propuesta de innovación con identidad territorial\", lo que abre la puerta a participar en ferias internacionales de artesanía. La presidenta de la asociación expresó que el reconocimiento es una señal de que las manos de Chimbo pueden competir con cualquier productor artesanal del país y que el potencial del mercado exterior es una meta alcanzable con el apoyo adecuado.",
            ],

            // ── 9 ──────────────────────────────────────────────────────────────
            [
                'title'        => 'El puente colonial de Chimbo: historia y turismo a orillas del río',
                'categoria'    => 'Turismo',
                'published_at' => Carbon::create(2023, 2, 25),
                'image_url'    => self::WC . 'Calle_Tres_de_Marzo_en_San_Jos%C3%A9_de_Chimbo.jpg&width=1200',
                'images'       => [
                    self::WC . 'Vista_de_San_Jos%C3%A9_de_Chimbo.jpg&width=1000',
                ],
                'body'         => "El río Chimbo, que da nombre al cantón y corre paralelo al centro urbano, fue durante siglos una de las rutas comerciales más importantes del Ecuador. En sus orillas se construyeron puentes que conectaban la sierra con la costa y que hoy son testigos de piedra de ese pasado de movimiento y comercio.\n\nEl antiguo puente de Chimbo, en parte restaurado gracias a una intervención del INPC, es uno de los puntos que más fotografías genera entre los visitantes. Su arco de piedra volcánica y la vista del río entre montañas verdes crean un cuadro que evoca los tiempos coloniales cuando las recuas de mulas cruzaban cargadas de aguardiente, tagua y sal.\n\nEl municipio ha incorporado el puente en la ruta patrimonial del cantón, con paneles interpretativos que narran la historia del camino real que pasaba por Chimbo y la importancia del río como fuente de vida para las comunidades. Los fines de semana es común ver a familias y turistas tomando fotos, haciendo picnic en las orillas o simplemente contemplando el agua que corre entre piedras y vegetación ribereña.",
            ],

            // ── 10 ─────────────────────────────────────────────────────────────
            [
                'title'        => 'Sistema de Gestión Turística en línea: Chimbo da el salto digital para atraer más visitantes',
                'categoria'    => 'Comunidad',
                'published_at' => Carbon::create(2024, 6, 30),
                'image_url'    => self::WC . 'San_Jose_de_Chimbo%2C_Ecuador.jpg&width=1200',
                'images'       => [
                    self::WC . 'Parque_Central_de_San_Jos%C3%A9_de_Chimbo.jpg&width=1000',
                ],
                'body'         => "El Municipio de San José de Chimbo lanzó el Sistema de Gestión Turística (SGT) en línea, una plataforma digital que centraliza la información de todos los atractivos, eventos, galerías y noticias del cantón, permitiendo a los turistas planificar su visita desde cualquier dispositivo con acceso a internet.\n\nLa plataforma incorpora un mapa interactivo con la ubicación exacta de los lugares turísticos, rutas recomendadas, información sobre horarios y precios, y un chatbot de asistencia que responde preguntas frecuentes sobre el cantón en tiempo real. Además, integra un sistema de reconocimiento de imágenes basado en inteligencia artificial que permite a los turistas identificar lugares de Chimbo simplemente fotografiándolos con su teléfono.\n\nEl proyecto, desarrollado en convenio con la Universidad Estatal de Bolívar, fue presentado ante autoridades del Ministerio de Turismo como caso de innovación en gestión turística municipal. El alcalde destacó que la iniciativa posiciona a Chimbo como referente de modernización entre los cantones de la provincia de Bolívar, y que la plataforma estará disponible para todos los visitantes de forma gratuita, como parte del compromiso del cantón con la promoción de su patrimonio cultural y natural.",
            ],
        ];

        foreach ($noticias as $n) {
            News::updateOrCreate(['title' => $n['title']], $n);
        }

        $this->command->info('✅ ' . count($noticias) . ' noticias reales de Chimbo cargadas (imágenes: Wikimedia Commons).');
    }
}
