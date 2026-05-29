<?php

namespace App\Http\Controllers\Api;

use App\Events\AlertaDetectada;
use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Camara;
use App\Models\Persona;
use App\Models\RegistroCelular;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CelularController extends Controller
{
    /**
     * Recibe eventos del microservicio Python (inicio o fin de sesión).
     * Llamado internamente — sin middleware auth.
     */
    public function procesar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'camara_id'         => 'required|exists:camaras,id',
            'mensaje'           => 'required|string',
            'tipo_deteccion'    => 'required|string',
            'persona_id'        => 'nullable|exists:personas,id',
            'confianza'         => 'required|numeric|min:0|max:1',
            'confianza_facial'  => 'nullable|numeric|min:0|max:1',
            'foto_rostro'       => 'nullable|string',
            'inicio'            => 'nullable|string',
            'fin'               => 'nullable|string',
            'duracion_segundos' => 'nullable|integer|min:0',
            'session_id'        => 'nullable|integer',
            'es_nuevo'          => 'nullable|boolean',
            'finalizado'        => 'nullable|boolean',
        ]);

        $mensaje = $data['mensaje'];

        if ($mensaje === 'celular_detectado_inicio') {
            return $this->iniciarSesion($data);
        }

        if ($mensaje === 'celular_sesion_finalizada') {
            return $this->finalizarSesion($data);
        }

        return response()->json(['resultado' => 'ignorado']);
    }

    private function iniciarSesion(array $data): JsonResponse
    {
        $registro = RegistroCelular::create([
            'camara_id'        => $data['camara_id'],
            'persona_id'       => $data['persona_id'] ?? null,
            'inicio'           => isset($data['inicio']) ? Carbon::parse($data['inicio']) : now(),
            'foto_rostro'      => $data['foto_rostro'] ?? null,
            'confianza_facial' => $data['confianza_facial'] ?? 0,
            'confianza_celular'=> $data['confianza'],
            'estado'           => 'activo',
        ]);

        $alerta = Alerta::create([
            'camara_id'  => $data['camara_id'],
            'persona_id' => $data['persona_id'] ?? null,
            'tipo'       => 'celular_en_mano',
            'nivel'      => 'advertencia',
            'confianza'  => $data['confianza'],
            'captura'    => $data['foto_rostro'] ?? null,
            'metadata'   => ['registro_celular_id' => $registro->id],
        ]);

        try {
            AlertaDetectada::dispatch($alerta->load(['camara', 'persona']));
        } catch (\Exception $e) {
            Log::warning("[Celular] No se pudo emitir alerta WebSocket: " . $e->getMessage());
        }

        Log::info("[Celular] Sesión iniciada #{$registro->id} cam={$data['camara_id']}");

        return response()->json([
            'session_id' => $registro->id,
            'alerta_id'  => $alerta->id,
        ], 201);
    }

    private function finalizarSesion(array $data): JsonResponse
    {
        $sessionId = $data['session_id'] ?? null;

        if ($sessionId) {
            $registro = RegistroCelular::find($sessionId);
            if ($registro && $registro->estado === 'activo') {
                $registro->update([
                    'persona_id'        => $data['persona_id'] ?? $registro->persona_id,
                    'fin'               => isset($data['fin']) ? Carbon::parse($data['fin']) : now(),
                    'duracion_segundos' => $data['duracion_segundos'] ?? 0,
                    'foto_rostro'       => $data['foto_rostro'] ?? $registro->foto_rostro,
                    'confianza_facial'  => $data['confianza_facial'] ?? $registro->confianza_facial,
                    'confianza_celular' => $data['confianza'],
                    'estado'            => 'finalizado',
                ]);
                Log::info("[Celular] Sesión finalizada #{$registro->id} duración={$registro->duracion_segundos}s");
                return response()->json(['updated' => true, 'session_id' => $registro->id]);
            }
        }

        // Sin session_id o ya finalizado → crear registro completo
        $registro = RegistroCelular::create([
            'camara_id'        => $data['camara_id'],
            'persona_id'       => $data['persona_id'] ?? null,
            'inicio'           => isset($data['inicio']) ? Carbon::parse($data['inicio']) : now()->subSeconds($data['duracion_segundos'] ?? 0),
            'fin'              => isset($data['fin']) ? Carbon::parse($data['fin']) : now(),
            'duracion_segundos'=> $data['duracion_segundos'] ?? 0,
            'foto_rostro'      => $data['foto_rostro'] ?? null,
            'confianza_facial' => $data['confianza_facial'] ?? 0,
            'confianza_celular'=> $data['confianza'],
            'estado'           => 'finalizado',
        ]);

        return response()->json(['created' => true, 'session_id' => $registro->id], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $registro = RegistroCelular::findOrFail($id);
        $registro->delete();
        return response()->json(['deleted' => true]);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $query = RegistroCelular::query();
        if ($request->filled('desde'))  $query->whereDate('inicio', '>=', $request->desde);
        if ($request->filled('hasta'))  $query->whereDate('inicio', '<=', $request->hasta);
        if ($request->filled('estado')) $query->where('estado', $request->estado);
        $count = $query->count();
        $query->delete();
        return response()->json(['deleted' => $count]);
    }

    /**
     * Reportes de uso de celular — autenticado.
     */
    public function reportes(Request $request): JsonResponse
    {
        $query = RegistroCelular::with(['persona', 'camara'])
            ->orderByDesc('inicio');

        if ($request->filled('desde')) {
            $query->whereDate('inicio', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('inicio', '<=', $request->hasta);
        }
        if ($request->filled('persona_id')) {
            $query->where('persona_id', $request->persona_id);
        }
        if ($request->filled('camara_id')) {
            $query->where('camara_id', $request->camara_id);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $registros = $query->paginate(20);

        // Stats del período filtrado
        $statsQuery = RegistroCelular::query();
        if ($request->filled('desde')) {
            $statsQuery->whereDate('inicio', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $statsQuery->whereDate('inicio', '<=', $request->hasta);
        }

        $stats = [
            'total_sesiones'      => (clone $statsQuery)->count(),
            'sesiones_activas'    => (clone $statsQuery)->where('estado', 'activo')->count(),
            'duracion_promedio'   => (int) (clone $statsQuery)->where('estado', 'finalizado')->avg('duracion_segundos'),
            'duracion_maxima'     => (int) (clone $statsQuery)->where('estado', 'finalizado')->max('duracion_segundos'),
            'personas_distintas'  => (clone $statsQuery)->whereNotNull('persona_id')->distinct('persona_id')->count('persona_id'),
        ];

        return response()->json([
            'data'     => $registros->items(),
            'meta'     => [
                'current_page' => $registros->currentPage(),
                'last_page'    => $registros->lastPage(),
                'total'        => $registros->total(),
            ],
            'stats'    => $stats,
        ]);
    }
}
