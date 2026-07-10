<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Controlador ADMIN de Eventos (CRUD del panel Blade). Los eventos alimentan
 * la sección "Próximos eventos" del Home y la página pública /eventos, donde
 * se muestran con un badge automático de "actual"/"pasado" según sus fechas.
 */
class EventoController extends Controller
{
    /** Lista todos los eventos para la tabla del admin. */
    public function index()
    {
        $eventos = Event::orderBy('created_at', 'desc')->get();
        return view('admin.eventos.index', compact('eventos'));
    }

    /** Muestra el formulario para registrar un nuevo evento. */
    public function create()
    {
        return view('admin.eventos.create');
    }

    /** Valida y guarda un evento nuevo, combinando fecha+hora en un solo timestamp. */
    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'categoria'    => 'nullable|string|max:255',
            'description'  => 'nullable|string',
            'starts_date'  => 'nullable|date',
            'starts_time'  => 'nullable|string',
            'ends_date'    => 'nullable|date',
            'ends_time'    => 'nullable|string',
            'image_url'    => 'nullable|string',
            'images'       => 'nullable|array',
            'images.*'     => 'string',
        ]);

        // Combinamos fecha y hora si existen. Si no, asignamos null.
        $starts_at = null;
        if ($request->starts_date) {
            $hora = $request->starts_time ?? '00:00';
            $starts_at = $request->starts_date . ' ' . $hora . ':00';
        }

        $ends_at = null;
        if ($request->ends_date) {
            $hora = $request->ends_time ?? '00:00';
            $ends_at = $request->ends_date . ' ' . $hora . ':00';
        }

        $evento = Event::create([
            'title'       => $request->title,
            'categoria'   => $request->categoria,
            'description' => $request->description,
            'starts_at'   => $starts_at,
            'ends_at'     => $ends_at,
            'image_url'   => $request->image_url,
            'images'      => array_values(array_filter($request->input('images', []))),
        ]);

        $this->sincronizarGaleria($evento);

        return redirect()->route('admin.eventos.index')
                         ->with('success', 'Evento creado correctamente');
    }

    /**
     * 🟢 MUESTRA EL FORMULARIO DE EDICIÓN CON LOS DATOS ACTUALES
     */
    public function edit($id)
    {
        $evento = Event::findOrFail($id);
        return view('admin.eventos.edit', compact('evento'));
    }

    /**
     * 👁️ VISTA DE SOLO LECTURA — para revisar el evento sin entrar a editarlo.
     */
    public function show($id)
    {
        $evento = Event::findOrFail($id);
        return view('admin.eventos.show', compact('evento'));
    }

    /**
     * 🟢 PROCESA LA ACTUALIZACIÓN EN LA BASE DE DATOS
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'categoria'   => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'starts_at'   => 'nullable|date',
            'ends_at'     => 'nullable|date',
            'image_url'   => 'nullable|string',
            'images'      => 'nullable|array',
            'images.*'    => 'string',
        ]);

        $evento = Event::findOrFail($id);

        $evento->update([
            'title'       => $request->title,
            'categoria'   => $request->categoria,
            'description' => $request->description,
            'starts_at'   => $request->starts_at,
            'ends_at'     => $request->ends_at,
            'image_url'   => $request->image_url,
            'images'      => array_values(array_filter($request->input('images', []))),
        ]);

        $this->sincronizarGaleria($evento);

        return redirect()->route('admin.eventos.index')
                         ->with('success', 'Evento actualizado correctamente');
    }

    /** Sube la portada o un archivo de galería (foto/GIF/video) de un evento. */
    public function upload(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No se recibió ningún archivo'], 400);
        }

        // Admite fotos, GIFs y videos cortos (el video pesa más, por eso el límite es mayor).
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,webp,mp4,mov,webm|max:25600',
        ]);

        $file = $request->file('file');
        // Las fotos se convierten a WebP; los videos se guardan tal cual.
        $path = \App\Support\ImageOptimizer::storeWebp($file, 'eventos');

        return response()->json([
            'url'  => '/storage/' . $path,
            'type' => str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image',
        ]);
    }

    /**
     * Mantiene sincronizada la galería pública del evento con sus fotos/videos
     * (portada + galería adicional), para que cada archivo subido aparezca
     * automáticamente en la sección de Galerías.
     */
    private function sincronizarGaleria(Event $evento): void
    {
        $medios = array_values(array_unique(array_filter(array_merge(
            $evento->image_url ? [$evento->image_url] : [],
            $evento->images ?? []
        ))));

        if (empty($medios)) {
            Gallery::where('event_id', $evento->id)->delete();
            return;
        }

        Gallery::updateOrCreate(
            ['event_id' => $evento->id],
            [
                'title'    => $evento->title,
                'category' => $evento->categoria ?: 'Eventos',
                'images'   => $medios,
            ]
        );
    }

    /** Elimina definitivamente un evento y su imagen de portada del storage. */
    public function destroy($id)
    {
        $evento = Event::findOrFail($id);

        if ($evento->image_url) {
            $path = str_replace('/storage/', '', $evento->image_url);
            Storage::disk('public')->delete($path);
        }

        $evento->delete();

        return redirect()->route('admin.eventos.index')
                         ->with('success', 'Evento eliminado correctamente');
    }
}