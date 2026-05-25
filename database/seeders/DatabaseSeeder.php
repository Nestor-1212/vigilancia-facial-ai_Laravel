<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Administrador',
            'email'    => 'admin@vigilancia.com',
            'password' => Hash::make('admin123'),
            'rol'      => 'admin',
        ]);

        User::create([
            'name'     => 'Operador',
            'email'    => 'operador@vigilancia.com',
            'password' => Hash::make('op123'),
            'rol'      => 'operador',
        ]);

        User::create([
            'name'     => 'Visualizador',
            'email'    => 'viewer@vigilancia.com',
            'password' => Hash::make('viewer123'),
            'rol'      => 'viewer',
        ]);

        $this->call([
            CamaraSeeder::class,
            PersonaSeeder::class,
        ]);
    }
}
