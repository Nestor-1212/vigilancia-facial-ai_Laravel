<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE alertas MODIFY COLUMN tipo ENUM(
            'rostro_detectado',
            'persona_restringida',
            'desconocido',
            'multiples_rostros',
            'sin_tapaboca',
            'sin_casco',
            'celular_en_mano'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE alertas MODIFY COLUMN tipo ENUM(
            'rostro_detectado',
            'persona_restringida',
            'desconocido',
            'multiples_rostros',
            'sin_tapaboca',
            'sin_casco'
        ) NOT NULL");
    }
};
