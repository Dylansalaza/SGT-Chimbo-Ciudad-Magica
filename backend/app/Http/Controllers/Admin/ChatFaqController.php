<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatFaq;
use Illuminate\Http\Request;

/**
 * Administra las palabras clave y respuestas que usa el asistente virtual
 * para responder preguntas escritas libremente por el visitante.
 */
class ChatFaqController extends Controller
{
    public function index()
    {
        $faqs = ChatFaq::orderBy('keyword')->get();
        return view('admin.faqs.index', compact('faqs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|max:255',
            'answer'  => 'required|string',
        ]);

        ChatFaq::create([
            'keyword' => trim($request->keyword),
            'answer'  => trim($request->answer),
        ]);

        return redirect()->route('admin.faqs.index')
            ->with('success', 'Palabra clave "' . $request->keyword . '" creada correctamente.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'keyword' => 'required|string|max:255',
            'answer'  => 'required|string',
        ]);

        $faq = ChatFaq::findOrFail($id);
        $faq->update([
            'keyword' => trim($request->keyword),
            'answer'  => trim($request->answer),
        ]);

        return redirect()->route('admin.faqs.index')
            ->with('success', 'Palabra clave actualizada correctamente.');
    }

    public function destroy($id)
    {
        $faq = ChatFaq::findOrFail($id);
        $keyword = $faq->keyword;
        $faq->delete();

        return redirect()->route('admin.faqs.index')
            ->with('success', 'Palabra clave "' . $keyword . '" eliminada.');
    }
}
