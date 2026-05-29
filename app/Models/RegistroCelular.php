<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroCelular extends Model
{
    protected $table = 'registros_celular';

    protected $fillable = [
        'camara_id', 'persona_id',
        'inicio', 'fin', 'duracion_segundos',
        'foto_rostro', 'confianza_facial', 'confianza_celular',
        'estado', 'metadata',
    ];

    protected $casts = [
        'inicio'             => 'datetime',
        'fin'                => 'datetime',
        'duracion_segundos'  => 'integer',
        'confianza_facial'   => 'float',
        'confianza_celular'  => 'float',
        'metadata'           => 'array',
    ];

    public function camara(): BelongsTo
    {
        return $this->belongsTo(Camara::class);
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function getDuracionFormateadaAttribute(): string
    {
        $seg = $this->duracion_segundos ?? 0;
        if ($seg < 60) {
            return "{$seg}s";
        }
        $min = intdiv($seg, 60);
        $resto = $seg % 60;
        return $resto > 0 ? "{$min}m {$resto}s" : "{$min}m";
    }
}
