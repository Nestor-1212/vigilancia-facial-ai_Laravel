<?php

use App\Http\Controllers\Api\AlertaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CamaraController;
use App\Http\Controllers\Api\CelularController;
use App\Http\Controllers\Api\ConfiguracionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FacialController;
use App\Http\Controllers\Api\PersonaController;
use Illuminate\Support\Facades\Route;

// Auth pública
Route::post('/auth/login', [AuthController::class, 'login']);

// Webhooks del microservicio Python (IP restringida en producción)
Route::post('/facial/procesar', [FacialController::class, 'procesar']);
Route::post('/celular/procesar', [CelularController::class, 'procesar']);

// Rutas autenticadas
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    Route::apiResource('personas', PersonaController::class);
    Route::delete('/personas/{persona}/fotos/{index}', [PersonaController::class, 'destroyFoto']);
    Route::apiResource('camaras', CamaraController::class);
    Route::patch('/camaras/{camara}/toggle', [CamaraController::class, 'toggleEstado']);

    Route::get('/alertas/pendientes', [AlertaController::class, 'pendientes']);
    Route::post('/alertas/revisar-todas', [AlertaController::class, 'marcarTodasRevisadas']);
    Route::delete('/alertas', [AlertaController::class, 'destroyAll']);
    Route::apiResource('alertas', AlertaController::class)->only(['index', 'show', 'destroy']);
    Route::patch('/alertas/{alerta}/revisar', [AlertaController::class, 'marcarRevisada']);

    Route::post('/facial/analizar', [FacialController::class, 'analizar']);

    Route::get('/configuracion', [ConfiguracionController::class, 'index']);
    Route::post('/configuracion', [ConfiguracionController::class, 'update']);

    Route::get('/celular/reportes', [CelularController::class, 'reportes']);
    Route::delete('/celular/registros', [CelularController::class, 'destroyAll']);
    Route::delete('/celular/registros/{id}', [CelularController::class, 'destroy']);
});
