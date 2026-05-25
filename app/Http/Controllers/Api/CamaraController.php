<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Camara;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CamaraController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Camara::withCount(['alertas', 'registrosAcceso'])->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'           => 'required|string|max:100',
            'ubicacion'        => 'required|string|max:200',
            'rtsp_url'         => 'required|string',
            'ip'               => 'nullable|ip',
            'grabacion_activa' => 'sometimes|boolean',
        ]);

        $camara = Camara::create($data);
        return response()->json($camara, 201);
    }

    public function show(Camara $camara): JsonResponse
    {
        return response()->json($camara->load([
            'alertas'         => fn($q) => $q->latest()->limit(20),
            'registrosAcceso' => fn($q) => $q->latest()->limit(20),
        ]));
    }

    public function update(Request $request, Camara $camara): JsonResponse
    {
        $data = $request->validate([
            'nombre'           => 'sometimes|string|max:100',
            'ubicacion'        => 'sometimes|string|max:200',
            'rtsp_url'         => 'sometimes|string',
            'estado'           => 'sometimes|in:activa,inactiva,error',
            'ip'               => 'nullable|ip',
            'grabacion_activa' => 'sometimes|boolean',
        ]);

        $camara->update($data);
        return response()->json($camara);
    }

    public function destroy(Camara $camara): JsonResponse
    {
        $camara->delete();
        return response()->json(null, 204);
    }

    public function toggleEstado(Camara $camara): JsonResponse
    {
        $camara->update([
            'estado' => $camara->estado === 'activa' ? 'inactiva' : 'activa',
        ]);
        return response()->json($camara);
    }
}
