<?php

namespace App\Events;

use App\Models\Alerta;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertaDetectada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Alerta $alerta)
    {
        $this->alerta->load(['camara', 'persona']);
    }

    public function broadcastOn(): array
    {
        return [new Channel('alertas')];
    }

    public function broadcastAs(): string
    {
        return 'alerta.nueva';
    }

    public function broadcastWith(): array
    {
        return [
            'id'        => $this->alerta->id,
            'tipo'      => $this->alerta->tipo,
            'nivel'     => $this->alerta->nivel,
            'confianza' => $this->alerta->confianza,
            'captura'   => $this->alerta->captura,
            'metadata'  => $this->alerta->metadata,
            'camara'    => [
                'id'        => $this->alerta->camara->id,
                'nombre'    => $this->alerta->camara->nombre,
                'ubicacion' => $this->alerta->camara->ubicacion,
            ],
            'persona'   => $this->alerta->persona ? [
                'id'     => $this->alerta->persona->id,
                'nombre' => $this->alerta->persona->nombre_completo,
                'tipo'   => $this->alerta->persona->tipo,
            ] : null,
            'timestamp' => $this->alerta->created_at->toISOString(),
        ];
    }
}
