<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TouristPlace;
use App\Models\PlaceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing as WorksheetDrawing;

/**
 * Controlador ADMIN de Lugares Turísticos (CRUD completo del panel Blade).
 * Gestiona la creación, edición y baja/reactivación de los puntos de interés
 * que luego se muestran en el mapa público y se indexan en el motor CLIP
 * para la búsqueda por imagen.
 */
class LugarController extends Controller
{
    /** Misma llave de caché que usa la API pública TouristPlaceController. */
    private const CACHE_KEY = 'catalogo_turismo_key';

    /**
     * Campos que el admin puede escribir vía formulario. Se usa como lista
     * blanca en create/update (en vez de $request->all()) para evitar
     * mass-assignment: aunque el modelo tenga más columnas fillable (p. ej.
     * "activo", que se gestiona solo desde destroy()), aquí nunca se tocan.
     */
    private const CAMPOS = [
        'nombre', 'categoria', 'descripcion', 'lat', 'lng', 'direccion',
        'telefono', 'horario', 'precio', 'imagen_url', 'destacado', 'galeria',
    ];

    /**
     * Lista todos los lugares (activos e inactivos) para la tabla del admin.
     * Se ordena primero por "activo" (los activos arriba) y luego por fecha,
     * así los lugares dados de baja quedan agrupados al final de la lista
     * en vez de mezclados entre los activos.
     */
    public function index()
    {
        $lugares = TouristPlace::orderBy('activo', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('admin.lugares.index', compact('lugares'));
    }

    /** Muestra el formulario para registrar un nuevo lugar turístico. */
    public function create()
    {
        $categorias = PlaceCategory::orderBy('nombre')->get();
        return view('admin.lugares.create', compact('categorias'));
    }

    /** Valida y guarda un lugar turístico nuevo, y refresca caché + índice CLIP. */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria' => 'required|string',
            'descripcion' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'direccion' => 'nullable|string',
            'telefono' => 'nullable|string',
            'horario' => 'nullable|string',
            'precio' => 'nullable|string',
            'imagen_url' => 'nullable|string'
        ]);

        // El checkbox solo llega cuando está marcado; lo normalizamos a booleano.
        // La galería de referencia se limpia de valores vacíos.
        $request->merge([
            'destacado' => $request->boolean('destacado'),
            'galeria'   => array_values(array_filter((array) $request->input('galeria', []))),
        ]);

        $lugar = TouristPlace::create($request->only(self::CAMPOS));

        // 1) Limpiar la caché para que la API/mapa muestren el nuevo lugar YA.
        Cache::forget(self::CACHE_KEY);
        // 2) Refrescar el CLIP (ahora sí leerá la lista actualizada).
        $this->refreshPythonService();

        return redirect()->route('admin.lugares.index')->with('success', 'Lugar turístico creado correctamente');
    }

    /**
     * 🟢 MUESTRA EL FORMULARIO DE EDICIÓN CON LA DATA DEL LUGAR
     */
    public function edit($id)
    {
        $lugar = TouristPlace::findOrFail($id);
        $categorias = PlaceCategory::orderBy('nombre')->get();
        return view('admin.lugares.edit', compact('lugar', 'categorias'));
    }

    /**
     * 🟢 PROCESA LA ACTUALIZACIÓN EN LA BD Y ACTUALIZA EL SERVICIO PYTHON
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria' => 'required|string',
            'descripcion' => 'required|string',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'direccion' => 'nullable|string',
            'telefono' => 'nullable|string',
            'horario' => 'nullable|string',
            'precio' => 'nullable|string',
            'imagen_url' => 'nullable|string'
        ]);

        $request->merge([
            'destacado' => $request->boolean('destacado'),
            'galeria'   => array_values(array_filter((array) $request->input('galeria', []))),
        ]);

        $lugar = TouristPlace::findOrFail($id);
        $lugar->update($request->only(self::CAMPOS));

        Cache::forget(self::CACHE_KEY);
        // REFRESCAR EL SERVICIO PYTHON TAMBIÉN AL ACTUALIZAR LA DATA
        $this->refreshPythonService();

        return redirect()->route('admin.lugares.index')->with('success', 'Lugar turístico actualizado correctamente');
    }

    /** Sube una imagen (portada o galería) al storage público y devuelve su URL. */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $file = $request->file('file');
        // Convierte la foto a WebP (más liviano) cuando es posible.
        $path = \App\Support\ImageOptimizer::storeWebp($file, 'lugares');

        // URL RELATIVA (no absoluta): antes usaba asset('storage/'.$path), que
        // "hornea" el host/puerto actual (APP_URL) dentro de la URL guardada en
        // BD. Si el panel se vuelve a abrir desde otro host/puerto (dev en otro
        // puerto, producción con otro dominio), esa URL vieja apunta a un host
        // que ya no responde y la miniatura se ve rota. Los demás controladores
        // de subida (Evento/Noticia/Galería/Home) ya devuelven '/storage/'.$path
        // por el mismo motivo; el navegador la resuelve siempre contra el host
        // ACTUAL. La vista que las muestra ya sabe anteponer el host si hace
        // falta (Str::startsWith($u,'http') ? $u : url('/').$u).
        return response()->json(['url' => '/storage/' . $path]);
    }

    /**
     * 📥 IMPORTA UNA "FICHA DE LEVANTAMIENTO Y JERARQUIZACIÓN DE ATRACTIVOS
     * TURÍSTICOS" (formato oficial MINTUR, hoja "Ficha_Jerarquia") y devuelve
     * SOLO los campos que el formulario de "Nuevo Lugar" necesita.
     *
     * No crea el lugar directamente: el admin revisa/ajusta los datos
     * precargados en el formulario y guarda con el botón normal "Agregar
     * Lugar". Esto evita crear registros basura si la ficha viene incompleta
     * o con un formato ligeramente distinto.
     *
     * Celdas leídas (posición FIJA dentro de la plantilla oficial):
     *   B6   Nombre del atractivo
     *   B8   Categoría oficial MINTUR (SITIOS_NATURALES, MANIFESTACIONES_CULTURALES, ...)
     *   B11  Provincia · I11 Cantón · P11 Parroquia
     *   B13  Barrio/sector · I13 Calle principal
     *   B15  Latitud · I15 Longitud
     *   F19  Teléfono del administrador
     *   E35/G35  Precio "desde/hasta"
     *   B300 Descripción del atractivo (sección "13. DESCRIPCION DEL ATRACTIVO")
     *   Fotos ancladas en la sección "14. ANEXOS · a. Archivo Fotográfico"
     *   Horario: tabla de checkboxes en filas 31-33 ("3.4 Ingreso al atractivo"),
     *   una fila por tipo de ingreso (Libre/Restringido/Pagado) con su propia
     *   hora de Ingreso (col. E) y Salida (col. G).
     */
    public function importarFicha(Request $request)
    {
        $request->validate([
            'ficha' => 'required|file|mimes:xlsx,xlsm,xls|max:20480',
        ]);

        $path = $request->file('ficha')->getRealPath();

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable $e) {
            // Se registra el error real en storage/logs/laravel.log para poder
            // diagnosticar la causa exacta (ej. si la librería PhpSpreadsheet
            // no está instalada, esto aparecerá como "Class not found").
            \Log::error('Error al leer ficha MINTUR: ' . $e->getMessage());

            return response()->json([
                'error' => 'No se pudo leer el archivo. Verifica que sea un Excel válido con el formato de la Ficha MINTUR.',
            ], 422);
        }

        if (!$spreadsheet->sheetNameExists('Ficha_Jerarquia')) {
            return response()->json([
                'error' => 'Este archivo no tiene el formato esperado (falta la hoja "Ficha_Jerarquia" de la Ficha MINTUR).',
            ], 422);
        }

        $hoja = $spreadsheet->getSheetByName('Ficha_Jerarquia');

        // Lee una celda y la devuelve como texto limpio (sin espacios extra)
        $leer = function (string $celda) use ($hoja): string {
            $valor = $hoja->getCell($celda)->getCalculatedValue();
            return trim((string) $valor);
        };

        // Lee una celda de HORA: Excel guarda las horas como un número
        // (fracción del día), así que hay que convertirlo a un HH:MM legible.
        $leerHora = function (string $celda) use ($hoja): ?string {
            $valor = $hoja->getCell($celda)->getCalculatedValue();
            if ($valor === null || $valor === '') {
                return null;
            }
            if (is_numeric($valor)) {
                try {
                    $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valor);
                    return $fecha->format('H:i');
                } catch (\Throwable $e) {
                    return null;
                }
            }
            // Algunas celdas vienen como texto ("  0:24:00") en vez de número.
            $texto = trim(str_replace(' ', '', (string) $valor));
            return preg_match('/^(\d{1,2}):(\d{2})/', $texto, $m) ? "{$m[1]}:{$m[2]}" : null;
        };

        $nombre        = $leer('B6');
        $categoriaMintur = $leer('B8');
        $provincia     = $leer('B11');
        $canton        = $leer('I11');
        $parroquia     = $leer('P11');
        $barrio        = $leer('B13');
        $callePrincipal = $leer('I13');
        $telefono      = $leer('F19');
        $descripcion   = $leer('B300');

        // Coordenadas: a veces vienen con espacios sueltos, ej. " -79.02716"
        $lat = str_replace(' ', '', $leer('B15'));
        $lng = str_replace(' ', '', $leer('I15'));
        $lat = is_numeric($lat) ? (float) $lat : null;
        $lng = is_numeric($lng) ? (float) $lng : null;

        // La categoría oficial de MINTUR usa mayúsculas y guiones bajos;
        // la traducimos a las categorías simples que usa el sitio público.
        $categoria = $this->traducirCategoriaMintur($categoriaMintur);

        // Dirección legible: barrio/sector + calle + parroquia/cantón (se omite
        // cualquier parte vacía o con el valor de plantilla "S/N"/"0"/"texto").
        $partes = array_filter([$barrio, $callePrincipal, $parroquia, $canton], function ($v) {
            return $v && !in_array(strtoupper($v), ['S/N', '0', 'TEXTO'], true);
        });
        $direccion = $partes ? implode(', ', array_unique($partes)) : null;

        // Precio: la ficha declara un rango "desde/hasta"; si ambos son 0
        // asumimos ingreso gratuito (lo más común en atractivos públicos).
        $desde = $leer('E35');
        $hasta = $leer('G35');
        $precio = null;
        if (is_numeric($desde) && is_numeric($hasta)) {
            $precio = ((float) $desde === 0.0 && (float) $hasta === 0.0)
                ? 'Gratis'
                : ('$' . $desde . ' - $' . $hasta);
        }

        // Horario: revisa las 3 filas de "3.4 Ingreso al atractivo" (Libre,
        // Restringido, Pagado) y usa la primera que tenga una hora de
        // Ingreso/Salida distinta de 00:00 (las filas sin llenar quedan en
        // 00:00 por defecto en la plantilla).
        $filasHorario = [31 => 'Libre', 32 => 'Restringido', 33 => 'Pagado'];
        $horario = null;
        foreach ($filasHorario as $fila => $tipo) {
            $ingreso = $leerHora("E{$fila}");
            $salida  = $leerHora("G{$fila}");
            if ($ingreso && $salida && !($ingreso === '00:00' && $salida === '00:00')) {
                $horario = "{$ingreso} - {$salida} ({$tipo})";
                break;
            }
        }

        return response()->json([
            'nombre'      => $nombre ?: null,
            'categoria'   => $categoria,
            'descripcion' => $descripcion ?: null,
            'lat'         => $lat,
            'lng'         => $lng,
            'direccion'   => $direccion,
            'telefono'    => ($telefono && strtoupper($telefono) !== 'TEXTO') ? $telefono : null,
            'precio'      => $precio,
            'horario'     => $horario,
            // Fotos reales incluidas en la ficha (sección 14, Archivo Fotográfico),
            // ya subidas al storage público — la primera se usa como portada.
            'imagenes'    => $this->extraerFotosFicha($hoja),
        ]);
    }

    /**
     * Traduce la categoría oficial de MINTUR (enum en mayúsculas) a las
     * categorías simples y amigables que usa el sitio público. Si no
     * reconoce el valor, lo deja tal cual capitalizado para que el admin
     * lo revise manualmente en el formulario.
     */
    private function traducirCategoriaMintur(string $valor): string
    {
        $mapa = [
            'SITIOS_NATURALES'            => 'Naturaleza',
            'MANIFESTACIONES_CULTURALES'  => 'Cultura',
            'ACONTECIMIENTOS_PROGRAMADOS' => 'Fiestas',
            'REALIZACIONES_TECNICO_CIENTIFICO_ARTISTICAS' => 'Cultura',
        ];

        $clave = strtoupper(str_replace([' ', '-'], '_', $valor));

        return $mapa[$clave] ?? (\Illuminate\Support\Str::title(strtolower(str_replace('_', ' ', $valor))) ?: 'Cultura');
    }

    /**
     * Extrae las fotos reales incrustadas en la sección "14. ANEXOS · a.
     * Archivo Fotográfico" de la ficha (filas ~300-311 de la plantilla
     * oficial) y las guarda en el storage público, igual que si el admin
     * las hubiera subido a mano desde el dropzone.
     *
     * @return string[] URLs (formato "/storage/lugares/xxx.png") listas para
     *                   usarse como imagen_url (portada) y galería adicional.
     */
    private function extraerFotosFicha($hoja): array
    {
        $urls = [];

        foreach ($hoja->getDrawingCollection() as $dibujo) {
            // Solo interesa el bloque "Archivo Fotográfico" (excluye el mapa
            // de ubicación y cualquier logo/firma que esté en otras filas).
            // Verificado con fichas reales: las fotos quedan ancladas ~305 y
            // el mapa de ubicación ~313, así que el rango 299-311 es correcto.
            $fila = (int) preg_replace('/\D/', '', $dibujo->getCoordinates());
            if ($fila < 299 || $fila > 311) {
                continue;
            }

            if (!$dibujo instanceof WorksheetDrawing) {
                continue; // Ignora imágenes generadas en memoria (no aplica en esta plantilla)
            }

            $origen = $dibujo->getPath();
            if (!$origen) {
                continue;
            }

            // OJO: getPath() NO devuelve una ruta de disco normal, sino algo
            // como "zip://ruta/al/archivo.xlsm#xl/media/imagenX.png" (la foto
            // vive DENTRO del zip del Excel). is_file() no reconoce bien ese
            // formato y descartaba todas las fotos, así que se lee el
            // contenido directamente con file_get_contents (que sí entiende
            // el wrapper "zip://") y se valida el resultado en vez de la ruta.
            $contenido = @file_get_contents($origen);
            if ($contenido === false || $contenido === '') {
                continue;
            }

            // La extensión puede venir "sucia" por el formato zip://...#...,
            // así que se limpia para quedarnos solo con letras/números.
            $ext = pathinfo($origen, PATHINFO_EXTENSION) ?: 'png';
            $ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext) ?: 'png';
            $nombreArchivo = 'ficha_' . time() . '_' . uniqid() . '.' . $ext;

            Storage::disk('public')->put('lugares/' . $nombreArchivo, $contenido);
            $urls[] = '/storage/lugares/' . $nombreArchivo;
        }

        return $urls;
    }

    /**
     * 🟠 DAR DE BAJA / REACTIVAR UN LUGAR
     *
     * Ya no se elimina el registro de la base de datos: se alterna el campo
     * "activo". Un lugar dado de baja deja de mostrarse al público (mapa,
     * home, búsqueda por IA) pero el admin conserva toda su información y
     * puede reactivarlo cuando quiera, sin volver a llenar el formulario.
     */
    public function destroy($id)
    {
        $lugar = TouristPlace::findOrFail($id);
        $lugar->activo = !$lugar->activo;
        $lugar->save();

        Cache::forget(self::CACHE_KEY);
        // REFRESCAR EL SERVICIO PYTHON PARA QUE DEJE DE RECONOCER (O VUELVA A RECONOCER) EL LUGAR
        $this->refreshPythonService();

        $mensaje = $lugar->activo
            ? 'Lugar reactivado correctamente'
            : 'Lugar dado de baja correctamente';

        return redirect()->route('admin.lugares.index')->with('success', $mensaje);
    }

    /**
     * 🗃️ REFRESCA EL SERVICIO CLIP EN SEGUNDO PLANO (fire-and-forget).
     *
     * Se dispara con timeout de 2 s para no bloquear el guardado del lugar.
     * El índice CLIP se actualizará unos segundos después sin que el admin espere.
     */
    private function refreshPythonService(): void
    {
        try {
            // timeout(2): máximo 2 segundos de espera; si CLIP tarda más, continúa igual.
            // El lugar ya está guardado en la BD — el mapa lo muestra de inmediato.
            Http::timeout(2)->post('http://127.0.0.1:5001/refresh');
        } catch (\Exception $e) {
            // Si CLIP no está corriendo o tarda, solo se registra — no interrumpe el flujo.
            \Log::warning('CLIP refresh omitido (no bloqueante): ' . $e->getMessage());
        }
    }
}