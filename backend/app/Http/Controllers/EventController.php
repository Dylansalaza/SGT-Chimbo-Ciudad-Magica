<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        return Event::orderByDesc('starts_at')->paginate(10);
    }

    public function show(Event $event)
    {
        return $event;
    }

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

    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json(['message' => 'Evento eliminado']);
    }
}