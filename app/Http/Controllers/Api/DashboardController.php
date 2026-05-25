<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use App\Models\Camara;
use App\Models\Persona;
use App\Models\RegistroAcceso;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $hoy = now()->startOfDay();

        return response()->json([
            'camaras' => [
                'total'    => Camara::count(),
                'activas'  => Camara::where('estado', 'activa')->count(),
                'error'    => Camara::where('estado', 'error')->count(),
            ],
            'personas' => [
                'total'       => Persona::count(),
                'activas'     => Persona::where('activo', true)->count(),
                'restringidas'=> Persona::where('tipo', 'restringido')->count(),
            ],
            'alertas' => [
                'hoy'         => Alerta::where('created_at', '>=', $hoy)->count(),
                'pendientes'  => Alerta::where('revisada', false)->count(),
                'criticas_hoy'=> Alerta::where('created_at', '>=', $hoy)->where('nivel', 'critico')->count(),
            ],
            'accesos_hoy' => [
                'total'     => RegistroAcceso::where('created_at', '>=', $hoy)->count(),
                'permitidos'=> RegistroAcceso::where('created_at', '>=', $hoy)->where('resultado', 'permitido')->count(),
                'denegados' => RegistroAcceso::where('created_at', '>=', $hoy)->where('resultado', 'denegado')->count(),
            ],
            'alertas_semana' => Alerta::where('created_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(created_at) as fecha, nivel, count(*) as total')
                ->groupBy('fecha', 'nivel')
                ->orderBy('fecha')
                ->get(),
            'ultimas_alertas' => Alerta::with(['camara', 'persona'])
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
