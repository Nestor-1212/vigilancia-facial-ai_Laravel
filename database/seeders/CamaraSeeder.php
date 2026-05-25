<?php

namespace Database\Seeders;

use App\Models\Camara;
use Illuminate\Database\Seeder;

class CamaraSeeder extends Seeder
{
    public function run(): void
    {
        $camaras = [
            ['nombre' => 'Entrada Principal',   'ubicacion' => 'Lobby - Puerta A',    'rtsp_url' => 'rtsp://192.168.1.101:554/stream', 'ip' => '192.168.1.101', 'estado' => 'activa'],
            ['nombre' => 'Estacionamiento 1',   'ubicacion' => 'Planta baja - Este',  'rtsp_url' => 'rtsp://192.168.1.102:554/stream', 'ip' => '192.168.1.102', 'estado' => 'activa'],
            ['nombre' => 'Pasillo Piso 1',      'ubicacion' => 'Piso 1 - Corredor',   'rtsp_url' => 'rtsp://192.168.1.103:554/stream', 'ip' => '192.168.1.103', 'estado' => 'activa'],
            ['nombre' => 'Sala de Servidores',  'ubicacion' => 'Sótano - Área IT',    'rtsp_url' => 'rtsp://192.168.1.104:554/stream', 'ip' => '192.168.1.104', 'estado' => 'inactiva'],
            ['nombre' => 'Salida de Emergencia','ubicacion' => 'Planta baja - Oeste', 'rtsp_url' => 'rtsp://192.168.1.105:554/stream', 'ip' => '192.168.1.105', 'estado' => 'activa'],
        ];

        foreach ($camaras as $camara) {
            Camara::create($camara);
        }
    }
}
