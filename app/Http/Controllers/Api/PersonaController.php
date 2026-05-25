<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PersonaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Persona::query();

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre', 'like', "%{$request->search}%")
                  ->orWhere('apellido', 'like', "%{$request->search}%")
                  ->orWhere('documento', 'like', "%{$request->search}%");
            });
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'    => 'required|string|max:100',
            'apellido'  => 'required|string|max:100',
            'documento' => 'nullable|string|unique:personas',
            'tipo'      => 'required|in:empleado,visitante,residente,restringido',
            'notas'     => 'nullable|string',
            'foto'      => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('foto')) {
            $data['foto_referencia'] = $request->file('foto')->store('personas', 'public');
        }

        unset($data['foto']);
        $persona = Persona::create($data);

        return response()->json($persona, 201);
    }

    public function show(Persona $persona): JsonResponse
    {
        return response()->json($persona->load(['alertas' => fn($q) => $q->latest()->limit(10), 'registrosAcceso' => fn($q) => $q->latest()->limit(10)]));
    }

    public function update(Request $request, Persona $persona): JsonResponse
    {
        $data = $request->validate([
            'nombre'    => 'sometimes|string|max:100',
            'apellido'  => 'sometimes|string|max:100',
            'documento' => "sometimes|string|unique:personas,documento,{$persona->id}",
            'tipo'      => 'sometimes|in:empleado,visitante,residente,restringido',
            'activo'    => 'sometimes|boolean',
            'notas'     => 'nullable|string',
            'foto'      => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('foto')) {
            if ($persona->foto_referencia) {
                Storage::disk('public')->delete($persona->foto_referencia);
            }
            $data['foto_referencia'] = $request->file('foto')->store('personas', 'public');
        }

        unset($data['foto']);
        $persona->update($data);

        return response()->json($persona);
    }

    public function destroy(Persona $persona): JsonResponse
    {
        if ($persona->foto_referencia) {
            Storage::disk('public')->delete($persona->foto_referencia);
        }
        $persona->delete();

        return response()->json(null, 204);
    }
}
