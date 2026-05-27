<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
                $q->where('nombre',    'like', "%{$request->search}%")
                  ->orWhere('apellido',  'like', "%{$request->search}%")
                  ->orWhere('documento', 'like', "%{$request->search}%");
            });
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'          => 'required|string|max:100',
            'apellido'        => 'required|string|max:100',
            'documento'       => 'nullable|string|unique:personas',
            'tipo'            => 'required|in:empleado,visitante,residente,restringido',
            'notas'           => 'nullable|string',
            'fotos'           => 'required|array|min:5|max:20',
            'fotos.*'         => 'required|image|max:5120',
            'descripciones'   => 'nullable|array',
            'descripciones.*' => 'nullable|string|max:150',
        ]);

        $persona = Persona::create([
            'nombre'    => $data['nombre'],
            'apellido'  => $data['apellido'],
            'documento' => $data['documento'] ?? null,
            'tipo'      => $data['tipo'],
            'notas'     => $data['notas'] ?? null,
        ]);

        $fotosIA = $this->guardarFotos($request, $persona->id, $data['descripciones'] ?? []);

        $persona->update([
            'fotos_ia'        => $fotosIA,
            'foto_referencia' => $fotosIA[0]['ruta'] ?? null,
        ]);

        return response()->json($persona->fresh(), 201);
    }

    public function show(Persona $persona): JsonResponse
    {
        return response()->json($persona->load([
            'alertas'         => fn($q) => $q->latest()->limit(10),
            'registrosAcceso' => fn($q) => $q->latest()->limit(10),
        ]));
    }

    public function update(Request $request, Persona $persona): JsonResponse
    {
        $data = $request->validate([
            'nombre'          => 'sometimes|string|max:100',
            'apellido'        => 'sometimes|string|max:100',
            'documento'       => "sometimes|nullable|string|unique:personas,documento,{$persona->id}",
            'tipo'            => 'sometimes|in:empleado,visitante,residente,restringido',
            'activo'          => 'sometimes|boolean',
            'notas'           => 'nullable|string',
            'fotos'           => 'nullable|array|max:20',
            'fotos.*'         => 'image|max:5120',
            'descripciones'   => 'nullable|array',
            'descripciones.*' => 'nullable|string|max:150',
        ]);

        $updateData = collect($data)->except(['fotos', 'descripciones'])->toArray();

        if ($request->hasFile('fotos')) {
            $nuevas  = $this->guardarFotos($request, $persona->id, $data['descripciones'] ?? []);
            $fotosIA = array_merge($persona->fotos_ia ?? [], $nuevas);

            $updateData['fotos_ia']        = $fotosIA;
            $updateData['foto_referencia'] = $fotosIA[0]['ruta'] ?? $persona->foto_referencia;
        }

        $persona->update($updateData);

        return response()->json($persona->fresh());
    }

    public function destroyFoto(Persona $persona, int $index): JsonResponse
    {
        $fotos = $persona->fotos_ia ?? [];

        if (!isset($fotos[$index])) {
            return response()->json(['error' => 'Foto no encontrada'], 404);
        }

        Storage::disk('public')->delete($fotos[$index]['ruta']);
        array_splice($fotos, $index, 1);

        $persona->update([
            'fotos_ia'        => $fotos ?: null,
            'foto_referencia' => $fotos[0]['ruta'] ?? null,
        ]);

        // Re-sincronizar IA: borra todo y re-registra las restantes
        $this->eliminarDeIA($persona->id);
        foreach ($fotos as $f) {
            $this->registrarFotoEnIA($persona->id, $f['ruta']);
        }

        return response()->json(['fotos_ia' => $fotos]);
    }

    public function destroy(Persona $persona): JsonResponse
    {
        foreach ($persona->fotos_ia ?? [] as $f) {
            Storage::disk('public')->delete($f['ruta']);
        }
        if ($persona->foto_referencia) {
            Storage::disk('public')->delete($persona->foto_referencia);
        }

        $this->eliminarDeIA($persona->id);
        $persona->delete();

        return response()->json(null, 204);
    }

    // ── Privados ──────────────────────────────────────────────

    private function guardarFotos(Request $request, int $personaId, array $descripciones): array
    {
        $fotos = [];
        foreach ($request->file('fotos', []) as $i => $archivo) {
            $ruta  = $archivo->store('personas', 'public');
            $desc  = trim($descripciones[$i] ?? '') ?: 'Sin descripción';
            $fotos[] = ['ruta' => $ruta, 'descripcion' => $desc];
            $this->registrarFotoEnIA($personaId, $ruta);
        }
        return $fotos;
    }

    private function registrarFotoEnIA(int $personaId, string $ruta): void
    {
        try {
            $path = Storage::disk('public')->path($ruta);
            Http::timeout(15)
                ->attach('foto', file_get_contents($path), basename($path))
                ->post(env('FACIAL_API_URL', 'http://localhost:8001') . "/registrar-rostro/{$personaId}");
        } catch (\Exception $e) {
            Log::warning("IA registro fallido persona {$personaId}: {$e->getMessage()}");
        }
    }

    private function eliminarDeIA(int $personaId): void
    {
        try {
            Http::timeout(10)
                ->delete(env('FACIAL_API_URL', 'http://localhost:8001') . "/registrar-rostro/{$personaId}");
        } catch (\Exception $e) {
            Log::warning("IA eliminar fallido persona {$personaId}: {$e->getMessage()}");
        }
    }
}
