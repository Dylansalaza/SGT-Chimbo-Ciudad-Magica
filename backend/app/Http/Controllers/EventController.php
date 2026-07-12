<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

/**
 * API pública/REST de Eventos turísticos (rutas en routes/api.php).
 *
 * index/show son públicos (lectura libre); store/update/destroy están detrás
 * del grupo 'auth:sanctum' + 'admin', por eso aquí no se repite el chequeo de
 * rol. El panel Blade del administrador usa un controlador aparte
 * (Admin\EventoController); este es el consumido por el frontend React.
 */
class EventController extends Controller
{
    /** GET /events — lista pública paginada (10 por página), más recientes primero. */
    public function index()
    {
        return Event::orderByDesc('starts_at')->paginate(10);
    }

    /** GET /events/{event} — devuelve un evento (route-model binding implícito). */
    public function show(Event $event)
    {
        return $event;
    }

    /** POST /events — crea un evento. Solo admin (middleware en la ruta). */
    public function store(Request $request)
    {
        // La autorización de administrador la aplica el middleware 'admin' en las rutas.
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'location' => 'nullable|array',
        ]);

        $event = Event::create($validated);
        return response()->json($event, 201);
    }

    /** PUT /events/{event} — actualiza un evento. Solo admin. */
    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'location' => 'nullable|array',
        ]);

        $event->update($validated);
        return response()->json($event);
    }

    /** DELETE /events/{event} — elimina un evento. Solo admin. */
    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json(['message' => 'Evento eliminado']);
    }
}