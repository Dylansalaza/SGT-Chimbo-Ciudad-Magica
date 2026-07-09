<?php

namespace App\Http\Controllers;

use App\Models\ChatFaq;
use App\Models\Event;
use App\Models\News;
use App\Models\TouristPlace;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    // Obtener todas las FAQs
    public function getFaqs()
    {
        return response()->json(ChatFaq::all());
    }

    /**
     * Responde una pregunta libre del visitante usando IA (Groq, capa
     * gratuita, modelo Llama). El modelo solo recibe datos reales de la BD
     * como contexto para evitar que invente lugares, horarios o precios.
     */
    public function askAi(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $apiKey = config('services.groq.key');
        if (! $apiKey) {
            return response()->json([
                'error' => 'El asistente con IA no está configurado todavía (falta GROQ_API_KEY en el servidor).',
            ], 503);
        }

        $contexto = $this->construirContexto();
        $modelo = config('services.groq.model', 'llama-3.1-8b-instant');

        $systemInstruction = <<<TXT
Eres el asistente virtual turístico oficial de San José de Chimbo, Ecuador. No tienes
nombre propio: si te preguntan cómo te llamas, responde simplemente que eres el
"asistente virtual" del portal.
Responde SIEMPRE en español, de forma breve (máximo 4-5 líneas), cálida y concreta.
Usa ÚNICAMENTE la información de contexto de abajo para hablar de lugares, horarios,
precios, eventos o noticias. Si te preguntan algo que no está en el contexto, dilo
honestamente y sugiere revisar la sección correspondiente del portal en vez de inventar
datos. Si preguntan algo totalmente ajeno al turismo de Chimbo, redirige amablemente
la conversación hacia el turismo del cantón.

CONTEXTO ACTUAL DEL PORTAL:
{$contexto}
TXT;

        try {
            $response = Http::timeout(20)
                ->withToken($apiKey)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $modelo,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemInstruction],
                        ['role' => 'user', 'content' => $request->message],
                    ],
                    'temperature' => 0.4,
                    'max_tokens'  => 300,
                ]);

            if (! $response->successful()) {
                Log::warning('Groq API error', ['status' => $response->status(), 'body' => $response->body()]);

                return response()->json([
                    'error' => $response->status() === 429
                        ? 'El asistente recibió demasiadas preguntas por ahora. Intenta de nuevo en un momento.'
                        : 'No se pudo contactar al asistente con IA en este momento.',
                ], 502);
            }

            $texto = data_get($response->json(), 'choices.0.message.content');

            if (! $texto) {
                return response()->json(['error' => 'El asistente no pudo generar una respuesta.'], 502);
            }

            return response()->json(['answer' => trim($texto)]);
        } catch (\Throwable $e) {
            Log::error('Groq API exception', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Error de conexión con el asistente de IA.'], 500);
        }
    }

    /** Arma un resumen compacto de lugares, eventos y noticias reales para dárselo a la IA como contexto. */
    private function construirContexto(): string
    {
        $lugares = TouristPlace::orderByDesc('destacado')->limit(20)->get()
            ->map(function ($p) {
                $partes = ["- {$p->nombre} ({$p->categoria})"];
                if ($p->horario) $partes[] = "horario: {$p->horario}";
                if ($p->precio)  $partes[] = "precio: {$p->precio}";
                if ($p->direccion) $partes[] = "dirección: {$p->direccion}";
                return implode(', ', $partes);
            })->implode("\n");

        $eventos = Event::orderBy('starts_at')->limit(6)->get()
            ->map(fn ($e) => "- {$e->title}" . ($e->starts_at ? " ({$e->starts_at->format('d/m/Y')})" : ''))
            ->implode("\n");

        $noticias = News::orderByDesc('published_at')->limit(6)->get()
            ->map(fn ($n) => "- {$n->title}")
            ->implode("\n");

        $faqs = ChatFaq::all()
            ->map(fn ($f) => "- {$f->keyword}: {$f->answer}")
            ->implode("\n");

        return trim(<<<TXT
LUGARES TURÍSTICOS:
{$lugares}

PRÓXIMOS EVENTOS:
{$eventos}

NOTICIAS RECIENTES:
{$noticias}

PREGUNTAS FRECUENTES CONFIGURADAS:
{$faqs}
TXT);
    }

    /**
     * Registra una visita anónima (analítica).
     * La ruta POST /registro-visita apuntaba a este método, que no existía
     * y provocaba un error 500 cada vez que el frontend lo llamaba.
     */
    public function registrarVisita(Request $request)
    {
        $visit = Visit::create([
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'url_visited' => $request->input('url', $request->fullUrl()),
        ]);

        return response()->json(['success' => true, 'id' => $visit->id], 201);
    }

    /**
     * Elimina una FAQ. La ruta DELETE /chat-faqs/{id} la invocaba sin que existiera.
     */
    public function destroyFaq($id)
    {
        $faq = ChatFaq::findOrFail($id);
        $faq->delete();

        return response()->json(['message' => 'FAQ eliminada']);
    }

    // (Opcional) Actualizar una FAQ
    public function updateFaq(Request $request, $id)
    {
        $faq = ChatFaq::findOrFail($id);
        $faq->update($request->only(['keyword', 'answer']));
        return response()->json($faq);
    }

    // (Opcional) Crear una nueva FAQ
    public function storeFaq(Request $request)
    {
        $faq = ChatFaq::create($request->validate([
            'keyword' => 'required|string|max:255',
            'answer' => 'required|string'
        ]));
        return response()->json($faq, 201);
    }
}