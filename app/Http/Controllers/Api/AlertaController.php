<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Alerta::with(['camara', 'persona'])->latest();

        if ($request->filled('nivel')) {
            $query->where('nivel', $request->nivel);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('revisada')) {
            $query->where('revisada', $request->boolean('revisada'));
        }
        if ($request->filled('camara_id')) {
            $query->where('camara_id', $request->camara_id);
        }

        return response()->json($query->paginate(30));
    }

    public function show(Alerta $alerta): JsonResponse
    {
        return response()->json($alerta->load(['camara', 'persona']));
    }

    public function marcarRevisada(Alerta $alerta): JsonResponse
    {
        $alerta->update([
            'revisada'    => true,
            'revisada_at' => now(),
        ]);
        return response()->json($alerta);
    }

    public function marcarTodasRevisadas(Request $request): JsonResponse
    {
        $query = Alerta::where('revisada', false);
        if ($request->filled('nivel')) {
            $query->where('nivel', $request->nivel);
        }
        $count = $query->update(['revisada' => true, 'revisada_at' => now()]);
        return response()->json(['actualizadas' => $count]);
    }

    public function destroy(Alerta $alerta): JsonResponse
    {
        $alerta->delete();
        return response()->json(null, 204);
    }

    // Método extra: resumen de alertas no revisadas
    public function pendientes(): JsonResponse
    {
        $pendientes = Alerta::where('revisada', false)
            ->selectRaw('nivel, count(*) as total')
            ->groupBy('nivel')
            ->get()
            ->keyBy('nivel');

        return response()->json([
            'total'      => Alerta::where('revisada', false)->count(),
            'critico'    => $pendientes->get('critico')?->total ?? 0,
            'advertencia'=> $pendientes->get('advertencia')?->total ?? 0,
            'info'       => $pendientes->get('info')?->total ?? 0,
        ]);
    }
}
