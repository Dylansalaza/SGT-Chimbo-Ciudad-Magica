<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\TouristPlaceController;
use App\Http\Controllers\ImageSearchController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StatsController;

// ============================================================
// AUTENTICACIÓN
// ============================================================
Route::post('/login',  [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// ============================================================
// RUTAS PÚBLICAS (lectura libre)
// Los parámetros se llaman {event}, {news}, {gallery} para que funcione el
// Route-Model Binding implícito (antes se llamaban {id} y el binding fallaba).
// ============================================================
Route::get('/events',          [EventController::class, 'index']);
Route::get('/events/{event}',  [EventController::class, 'show']);

Route::get('/news',            [NewsController::class, 'index']);
Route::get('/news/{news}',     [NewsController::class, 'show']);

Route::get('/galleries',           [GalleryController::class, 'index']);
Route::get('/galleries/{gallery}', [GalleryController::class, 'show']);

Route::get('/tourist-places',      [TouristPlaceController::class, 'index']);
Route::get('/clip-catalog',        [TouristPlaceController::class, 'clipCatalog']); // catálogo multi-imagen para el motor CLIP
Route::get('/tourist-places/{id}', [TouristPlaceController::class, 'show']);

// Contenido editable del Home (carrusel, bienvenida, destacados)
Route::get('/home', [HomeController::class, 'show']);

// Estadísticas públicas (visitas, contenidos). Cacheadas 5 min en el controller;
// el throttle es una segunda barrera contra ráfagas automatizadas.
Route::get('/stats', [StatsController::class, 'index'])->middleware('throttle:30,1');

// Chatbot — lectura pública
Route::get('/chat-faqs', [ChatbotController::class, 'getFaqs']);

// Chatbot con IA (Google Gemini) — limitado a 15 preguntas/minuto por IP
// para no agotar la cuota gratuita diaria si alguien abusa del formulario.
Route::post('/chat-ai', [ChatbotController::class, 'askAi'])->middleware('throttle:15,1');

// Registro de visita (analítica anónima) — limitado para evitar inflar las
// estadísticas o saturar la tabla `visits` con peticiones automatizadas.
Route::post('/registro-visita', [ChatbotController::class, 'registrarVisita'])->middleware('throttle:30,1');

// ============================================================
// BÚSQUEDA POR IMAGEN (IA — flujo asíncrono)
//   1. React sube la imagen  → POST /image-search
//   2. React consulta estado → GET  /image-search/status/{id}
// ============================================================
// La subida de imágenes es costosa (almacena archivo + encola trabajo del
// worker CLIP), así que la limitamos para evitar llenar el disco / la cola.
Route::post('/image-search',            [ImageSearchController::class, 'search'])->middleware('throttle:20,1');
Route::get('/image-search/status/{id}', [ImageSearchController::class, 'checkStatus']);

// ============================================================
// RUTAS PROTEGIDAS (token Sanctum + rol administrador)
// El middleware 'admin' centraliza la autorización (DRY / SRP):
// los controllers ya no repiten el chequeo de isAdmin().
// ============================================================
Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    // — Eventos —
    Route::post('/events',            [EventController::class, 'store']);
    Route::put('/events/{event}',     [EventController::class, 'update']);
    Route::delete('/events/{event}',  [EventController::class, 'destroy']);

    // — Noticias —
    Route::post('/news',          [NewsController::class, 'store']);
    Route::put('/news/{news}',    [NewsController::class, 'update']);
    Route::delete('/news/{news}', [NewsController::class, 'destroy']);

    // — Galerías —
    Route::post('/galleries',             [GalleryController::class, 'store']);
    Route::put('/galleries/{gallery}',    [GalleryController::class, 'update']);
    Route::delete('/galleries/{gallery}', [GalleryController::class, 'destroy']);

    // — Lugares turísticos —
    Route::post('/tourist-places',        [TouristPlaceController::class, 'store']);
    Route::put('/tourist-places/{id}',    [TouristPlaceController::class, 'update']);
    Route::delete('/tourist-places/{id}', [TouristPlaceController::class, 'destroy']);

    // — Chatbot FAQs —
    Route::post('/chat-faqs',        [ChatbotController::class, 'storeFaq']);
    Route::put('/chat-faqs/{id}',    [ChatbotController::class, 'updateFaq']);
    Route::delete('/chat-faqs/{id}', [ChatbotController::class, 'destroyFaq']);

    // — IA: gestión del índice CLIP (solo admins) —
    Route::post('/image-search/refresh', [ImageSearchController::class, 'refresh']);
    Route::get('/image-search/health',   [ImageSearchController::class, 'health']);
});
