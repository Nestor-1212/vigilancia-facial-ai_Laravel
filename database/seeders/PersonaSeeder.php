<?php

namespace Database\Seeders;

use App\Models\Persona;
use Illuminate\Database\Seeder;

class PersonaSeeder extends Seeder
{
    public function run(): void
    {
        $personas = [
            ['nombre' => 'Carlos',   'apellido' => 'Mendoza',   'documento' => '8-123-456',  'tipo' => 'empleado',    'activo' => true],
            ['nombre' => 'Ana',      'apellido' => 'Rivas',     'documento' => '4-567-890',  'tipo' => 'empleado',    'activo' => true],
            ['nombre' => 'Luis',     'apellido' => 'Torres',    'documento' => '2-111-222',  'tipo' => 'empleado',    'activo' => true],
            ['nombre' => 'María',    'apellido' => 'Castillo',  'documento' => '6-333-444',  'tipo' => 'residente',   'activo' => true],
            ['nombre' => 'Pedro',    'apellido' => 'Núñez',     'documento' => '3-555-666',  'tipo' => 'residente',   'activo' => true],
            ['nombre' => 'Juan',     'apellido' => 'Herrera',   'documento' => '1-777-888',  'tipo' => 'visitante',   'activo' => true],
            ['nombre' => 'Roberto',  'apellido' => 'Díaz',      'documento' => '9-999-000',  'tipo' => 'restringido', 'activo' => true, 'notas' => 'Acceso denegado por seguridad'],
            ['nombre' => 'Sofía',    'apellido' => 'Vargas',    'documento' => '7-100-200',  'tipo' => 'empleado',    'activo' => true],
            ['nombre' => 'Miguel',   'apellido' => 'Guerrero',  'documento' => '5-300-400',  'tipo' => 'visitante',   'activo' => false],
            ['nombre' => 'Isabel',   'apellido' => 'Morales',   'documento' => '2-500-600',  'tipo' => 'restringido', 'activo' => true, 'notas' => 'Lista negra'],
        ];

        foreach ($personas as $persona) {
            Persona::create($persona);
        }
    }
}
