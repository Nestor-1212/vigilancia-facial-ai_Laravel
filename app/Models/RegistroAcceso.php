<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroAcceso extends Model
{
    protected $table = 'registros_acceso';

    protected $fillable = [
        'persona_id', 'camara_id', 'resultado', 'confianza', 'captura', 'embedding',
    ];

    protected $casts = [
        'embedding' => 'array',
        'confianza' => 'float',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function camara(): BelongsTo
    {
        return $this->belongsTo(Camara::class);
    }
}
