<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GenerarTokenServicio extends Command
{
    protected $signature   = 'facial:token';
    protected $description = 'Genera un token Sanctum para el microservicio FastAPI de reconocimiento facial';

    public function handle(): void
    {
        $admin = User::where('rol', 'admin')->first();

        if (! $admin) {
            $this->error('No hay usuarios admin. Ejecuta primero: php artisan migrate --seed');
            return;
        }

        // Revocar tokens anteriores del servicio facial
        $admin->tokens()->where('name', 'facial-service')->delete();

        $token = $admin->createToken('facial-service')->plainTextToken;

        $this->newLine();
        $this->info('✅ Token generado para el microservicio FastAPI:');
        $this->newLine();
        $this->line("  <fg=yellow>{$token}</>");
        $this->newLine();
        $this->info('Copia este token en el archivo:');
        $this->line('  <fg=cyan>C:\\Users\\Nestor\\Desktop\\vigilancia-facial-ai\\.env</>');
        $this->line('  <fg=cyan>LARAVEL_SERVICE_TOKEN=</><fg=yellow>' . $token . '</>');
        $this->newLine();
    }
}
