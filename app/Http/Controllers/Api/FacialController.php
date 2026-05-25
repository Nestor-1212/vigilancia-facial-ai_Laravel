<?php

namespace App\Http\Controllers\Api;

use App\Events\AlertaDetectada;
use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Camara;
use App\Models\Persona;
use App\Models\RegistroAcceso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FacialController extends Controller
{
    /**
     * Recibe el resultado del análisis del microservicio Python y lo procesa.
     * Llamado internamente desde el worker o desde el propio microservicio.
     */
    public function procesar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'camara_id'      => 'required|exists:camaras,id',
            'confianza'      => 'required|numeric|min:0|max:1',
            'identificado'   => 'required|boolean',
            'persona_id'     => 'nullable|exists:personas,id',
            'captura'        => 'nullable|string',
            'embedding'      => 'nullable|array',
            'tipo_deteccion' => 'nullable|in:reconocimiento_facial,tapaboca,casco',
            'sin_tapaboca'   => 'nullable|integer|min:0',
            'sin_casco'      => 'nullable|integer|min:0',
            'total_rostros'  => 'nullable|integer|min:0',
        ]);

        $tipo = $data['tipo_deteccion'] ?? 'reconocimiento_facial';

        if ($tipo === 'tapaboca') {
            return $this->procesarEPP($data, 'tapaboca', 'sin_tapaboca', 'sin_tapaboca');
        }
        if ($tipo === 'casco') {
            return $this->procesarEPP($data, 'casco', 'sin_casco', 'sin_casco');
        }

        $camara  = Camara::find($data['camara_id']);
        $persona = $data['persona_id'] ? Persona::find($data['persona_id']) : null;

        $registro = RegistroAcceso::create([
            'camara_id'  => $camara->id,
            'persona_id' => $persona?->id,
            'resultado'  => $this->determinarResultado($persona, $data['identificado']),
            'confianza'  => $data['confianza'],
            'captura'    => $data['captura'],
            'embedding'  => $data['embedding'],
        ]);

        $alerta = $this->crearAlertaSiCorresponde($camara, $persona, $data);

        if ($alerta) {
            AlertaDetectada::dispatch($alerta);
        }

        return response()->json([
            'registro_id' => $registro->id,
            'resultado'   => $registro->resultado,
            'alerta_id'   => $alerta?->id,
        ]);
    }

    private function procesarEPP(array $data, string $tipo, string $campoConteo, string $resultadoNombre): JsonResponse
    {
        $camara       = Camara::find($data['camara_id']);
        $sinEPP       = (int) ($data[$campoConteo] ?? 0);
        $totalRostros = (int) ($data['total_rostros'] ?? 0);

        if ($sinEPP === 0) {
            return response()->json(['resultado' => 'con_' . $tipo, 'alerta_id' => null]);
        }

        $alerta = Alerta::create([
            'camara_id'  => $camara->id,
            'persona_id' => null,
            'tipo'       => 'sin_' . $tipo,
            'nivel'      => 'advertencia',
            'confianza'  => $data['confianza'],
            'captura'    => $data['captura'] ?? null,
            'metadata'   => [
                'sin_' . $tipo  => $sinEPP,
                'total_rostros' => $totalRostros,
            ],
        ]);

        AlertaDetectada::dispatch($alerta);

        return response()->json([
            'resultado'         => $resultadoNombre,
            'alerta_id'         => $alerta->id,
            'sin_' . $tipo      => $sinEPP,
            'total_rostros'     => $totalRostros,
        ]);
    }

    /**
     * Envía una imagen al microservicio Python para análisis facial bajo demanda.
     */
    public function analizar(Request $request): JsonResponse
    {
        $request->validate([
            'imagen'     => 'required|image|max:10240',
            'camara_id'  => 'required|exists:camaras,id',
        ]);

        $imagenPath = $request->file('imagen')->store('capturas/temp', 'public');
        $imagenUrl  = Storage::disk('public')->url($imagenPath);

        try {
            $response = Http::timeout(15)->post(config('services.facial_api.url') . '/analizar', [
                'imagen_url' => $imagenUrl,
                'camara_id'  => $request->camara_id,
            ]);

            if ($response->failed()) {
                return response()->json(['error' => 'Error en microservicio IA'], 502);
            }

            return response()->json($response->json());
        } catch (\Exception $e) {
            Log::error('Error contactando microservicio facial: ' . $e->getMessage());
            return response()->json(['error' => 'Microservicio no disponible'], 503);
        }
    }

    private function determinarResultado(?Persona $persona, bool $identificado): string
    {
        if (! $identificado || ! $persona) {
            return 'desconocido';
        }
        return $persona->tipo === 'restringido' ? 'denegado' : 'permitido';
    }

    private function crearAlertaSiCorresponde(Camara $camara, ?Persona $persona, array $data): ?Alerta
    {
        if (! $data['identificado']) {
            return Alerta::create([
                'camara_id' => $camara->id,
                'persona_id'=> null,
                'tipo'      => 'desconocido',
                'nivel'     => 'advertencia',
                'confianza' => $data['confianza'],
                'captura'   => $data['captura'],
            ]);
        }

        if ($persona && $persona->tipo === 'restringido') {
            return Alerta::create([
                'camara_id' => $camara->id,
                'persona_id'=> $persona->id,
                'tipo'      => 'persona_restringida',
                'nivel'     => 'critico',
                'confianza' => $data['confianza'],
                'captura'   => $data['captura'],
            ]);
        }

        return null;
    }
}
