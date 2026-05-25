<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Persona extends Model
{
    protected $fillable = [
        'nombre', 'apellido', 'documento', 'tipo', 'foto_referencia', 'activo', 'notas',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class);
    }

    public function registrosAcceso(): HasMany
    {
        return $this->hasMany(RegistroAcceso::class);
    }
}
