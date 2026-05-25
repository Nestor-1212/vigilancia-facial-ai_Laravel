<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alerta extends Model
{
    protected $fillable = [
        'camara_id', 'persona_id', 'tipo', 'nivel', 'confianza', 'captura', 'revisada', 'revisada_at', 'metadata',
    ];

    protected $casts = [
        'revisada'    => 'boolean',
        'revisada_at' => 'datetime',
        'metadata'    => 'array',
        'confianza'   => 'float',
    ];

    public function camara(): BelongsTo
    {
        return $this->belongsTo(Camara::class);
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }
}
