<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfiguracionController extends Controller
{
    private array $claves = [
        'facial_api_url',
        'confianza_minima',
        'tiempo_inactividad',
        'alertas_desconocidos',
        'alertas_multiples',
        'retencion_dias',
        'guardar_capturas_permitidas',
        'guardar_capturas_desconocidos',
        'deteccion_tapaboca',
        'deteccion_casco',
    ];

    public function index(): JsonResponse
    {
        $config = [];
        foreach ($this->claves as $clave) {
            $config[$clave] = Configuracion::get($clave);
        }
        return response()->json($config);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'facial_api_url'               => 'nullable|string',
            'confianza_minima'             => 'nullable|integer|min:0|max:100',
            'tiempo_inactividad'           => 'nullable|integer|min:1',
            'alertas_desconocidos'         => 'nullable|boolean',
            'alertas_multiples'            => 'nullable|boolean',
            'retencion_dias'               => 'nullable|integer|min:1',
            'guardar_capturas_permitidas'  => 'nullable|boolean',
            'guardar_capturas_desconocidos'=> 'nullable|boolean',
            'deteccion_tapaboca'           => 'nullable|boolean',
            'deteccion_casco'              => 'nullable|boolean',
        ]);

        foreach ($data as $clave => $valor) {
            if (!is_null($valor)) {
                Configuracion::set($clave, is_bool($valor) ? ($valor ? '1' : '0') : (string) $valor);
            }
        }

        return response()->json(['mensaje' => 'Configuración guardada']);
    }
}
