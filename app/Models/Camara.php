<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Camara extends Model
{
    protected $fillable = [
        'nombre', 'ubicacion', 'rtsp_url', 'estado', 'ip', 'grabacion_activa',
    ];

    protected $casts = [
        'grabacion_activa' => 'boolean',
    ];

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class);
    }

    public function registrosAcceso(): HasMany
    {
        return $this->hasMany(RegistroAcceso::class);
    }
}
