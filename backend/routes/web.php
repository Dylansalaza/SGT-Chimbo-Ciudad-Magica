<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventoController;
use App\Http\Controllers\Admin\NoticiaController;
use App\Http\Controllers\Admin\GaleriaController;
use App\Http\Controllers\Admin\LugarController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\ReporteController;
use App\Http\Controllers\Admin\CategoriaLugarController;
use App\Http\Controllers\Admin\ChatFaqController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// NOTA: la búsqueda visual real vive en routes/api.php (POST /api/image-search y
// GET /api/image-search/status/{id}). Las antiguas rutas web /search apuntaban a
// métodos inexistentes del controlador (daban error 500) y nadie las usaba, por
// eso se eliminaron.

// =========================================================
// 🛡️ RUTAS DEL PANEL DE ADMINISTRADOR (PRIVADAS)
// =========================================================
Route::middleware(['auth:sanctum', 'admin', 'presencia'])->prefix('admin')->name('admin.')->group(function () {

    // ── Dashboard (ambos roles) ────────────────────────────────────────────
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Latido de presencia (ambos roles) ──────────────────────────────────
    // El layout del panel llama a esta ruta cada ~15 s mientras la pestaña vive.
    // El middleware 'presencia' refresca la marca de presencia; aquí solo hay
    // que responder algo mínimo. Al cerrar la pestaña dejan de llegar latidos y
    // el siguiente acceso al panel cierra la sesión (ver EnforcePanelHeartbeat).
    Route::post('latido', fn () => response()->noContent())->name('latido');

    // ── Sección exclusiva del ADMINISTRADOR (gestión de usuarios) ─────────
    Route::middleware('rol:administrador')->group(function () {
        Route::get('usuarios',                       [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('usuarios/create',                [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('usuarios',                      [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::patch('usuarios/{id}/toggle-admin',   [UsuarioController::class, 'toggleAdmin'])->name('usuarios.toggleAdmin');
        Route::patch('usuarios/{id}/recovery',       [UsuarioController::class, 'updateRecovery'])->name('usuarios.recovery');
        Route::delete('usuarios/{id}',               [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
    });

    // ── Sección exclusiva del ADMIN DE TURISMO (contenido turístico) ──────
    Route::middleware('rol:admin_turismo')->group(function () {

        // 🎭 Eventos
        // Route::resource genera index/create/store/edit/update/destroy automáticamente
        // (se excluye el 'show' por defecto de Laravel porque se define aparte abajo,
        // como una vista de SOLO LECTURA distinta del formulario de edición).
        Route::resource('eventos', EventoController::class)->except(['show']);
        Route::post('eventos/upload', [EventoController::class, 'upload'])->name('eventos.upload');
        Route::get('eventos/{evento}', [EventoController::class, 'show'])->name('eventos.show');

        // 📰 Noticias (mismo patrón que Eventos)
        Route::resource('noticias', NoticiaController::class)->except(['show']);
        Route::post('noticias/upload', [NoticiaController::class, 'upload'])->name('noticias.upload');
        Route::get('noticias/{noticia}', [NoticiaController::class, 'show'])->name('noticias.show');

        // 🖼️ Galerías
        Route::get('galerias',                   [GaleriaController::class, 'index'])->name('galerias.index');
        Route::get('galerias/create',            [GaleriaController::class, 'create'])->name('galerias.create');
        Route::post('galerias',                  [GaleriaController::class, 'store'])->name('galerias.store');
        Route::delete('galerias/{galeria}',      [GaleriaController::class, 'destroy'])->name('galerias.destroy');
        Route::post('galerias/upload',           [GaleriaController::class, 'upload'])->name('galerias.upload');
        Route::get('galerias/{galeria}/edit',    [GaleriaController::class, 'edit'])->name('galerias.edit');
        Route::put('galerias/{galeria}',         [GaleriaController::class, 'update'])->name('galerias.update');

        // 📍 Lugares Turísticos
        // NOTA: la ruta DELETE ('lugares.destroy') ya NO borra el registro:
        // LugarController::destroy() alterna el campo "activo" (dar de baja /
        // reactivar). Se conserva el nombre de ruta estándar de Laravel para
        // no tener que tocar las vistas/formularios existentes.
        Route::resource('lugares', LugarController::class)->except(['show']);
        Route::post('lugares/upload', [LugarController::class, 'upload'])->name('lugares.upload');
        // 📥 Importa una "Ficha de Levantamiento y Jerarquización de Atractivos
        // Turísticos" (formato oficial MINTUR, .xlsx/.xlsm) y devuelve solo los
        // campos necesarios para precargar el formulario de "Nuevo Lugar".
        Route::post('lugares/importar-ficha', [LugarController::class, 'importarFicha'])->name('lugares.importarFicha');

        // 🏷️ Categorías de lugares
        Route::get('categorias',         [CategoriaLugarController::class, 'index'])->name('categorias.index');
        Route::post('categorias',        [CategoriaLugarController::class, 'store'])->name('categorias.store');
        Route::delete('categorias/{id}', [CategoriaLugarController::class, 'destroy'])->name('categorias.destroy');

        // 💬 Palabras clave del asistente virtual
        Route::get('faqs',         [ChatFaqController::class, 'index'])->name('faqs.index');
        Route::post('faqs',        [ChatFaqController::class, 'store'])->name('faqs.store');
        Route::put('faqs/{id}',    [ChatFaqController::class, 'update'])->name('faqs.update');
        Route::delete('faqs/{id}', [ChatFaqController::class, 'destroy'])->name('faqs.destroy');

        // 📊 Reportes (hub + vista en pantalla + PDF institucional por cada uno)
        Route::get('reportes',                [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('reportes/visitas',        [ReporteController::class, 'visitas'])->name('reportes.visitas');
        Route::get('reportes/visitas/pdf',    [ReporteController::class, 'visitasPdf'])->name('reportes.visitas.pdf');
        Route::get('reportes/visitas/csv',    [ReporteController::class, 'visitasCsv'])->name('reportes.visitas.csv');
        Route::get('reportes/eventos',        [ReporteController::class, 'eventos'])->name('reportes.eventos');
        Route::get('reportes/eventos/pdf',    [ReporteController::class, 'eventosPdf'])->name('reportes.eventos.pdf');
        Route::get('reportes/noticias',       [ReporteController::class, 'noticias'])->name('reportes.noticias');
        Route::get('reportes/noticias/pdf',   [ReporteController::class, 'noticiasPdf'])->name('reportes.noticias.pdf');
        Route::get('reportes/lugares',        [ReporteController::class, 'lugares'])->name('reportes.lugares');
        Route::get('reportes/lugares/pdf',    [ReporteController::class, 'lugaresPdf'])->name('reportes.lugares.pdf');

        // 🏠 Editor del Home
        Route::get('home',                [AdminHomeController::class, 'edit'])->name('home.edit');
        Route::put('home',                [AdminHomeController::class, 'update'])->name('home.update');
        Route::post('home/upload',        [AdminHomeController::class, 'upload'])->name('home.upload');
    });
});

// =========================================================
// 🔐 RUTAS DE AUTENTICACIÓN
// =========================================================
Route::get('/login', function (Request $request) {
    // SEGURIDAD: abrir el login SIEMPRE cierra cualquier sesión web activa antes
    // de mostrar el formulario. Así, entrar al panel desde el botón del sitio
    // público ("Dirigirse al Panel de Administrador") exige credenciales aunque
    // el navegador conservara una sesión iniciada antes (p. ej. si se cerró la
    // ventana del panel sin pulsar "Cerrar sesión"). Solo actúa si hay sesión;
    // para un visitante ya deslogueado es un no-op (no regenera nada de más).
    if (Auth::guard('web')->check()) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
    return view('auth.login');
})->name('login');

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// =========================================================
// 🔑 RECUPERACIÓN DE CONTRASEÑA POR CORREO
// =========================================================
Route::get('/forgot-password',  [PasswordResetController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('throttle:6,1')->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:6,1')->name('password.update');

// =========================================================
// 🖼️ SERVIDOR DE IMÁGENES (RESPALDO)
// Si el enlace simbólico public/storage no existe o está roto (muy común en
// Windows sin permisos de administrador), las imágenes daban 404. Esta ruta
// sirve directamente los archivos desde storage/app/public, así las imágenes
// SIEMPRE se ven aunque falte el symlink.
// =========================================================
Route::get('/storage/{ruta}', function (string $ruta) {
    // 🛡️ Blindaje contra path traversal (../../.env, /etc/passwd, código fuente…):
    // resolvemos la ruta real y exigimos que quede DENTRO de storage/app/public.
    $base     = realpath(storage_path('app/public'));
    $absoluta = realpath($base . DIRECTORY_SEPARATOR . $ruta);

    abort_unless(
        $base !== false
            && $absoluta !== false
            && is_file($absoluta)
            && str_starts_with($absoluta, $base . DIRECTORY_SEPARATOR),
        404
    );

    return response()->file($absoluta);
})->where('ruta', '.*')->name('storage.fallback');