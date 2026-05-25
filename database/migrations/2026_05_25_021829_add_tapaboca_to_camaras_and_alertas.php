<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('camaras', function (Blueprint $table) {
            $table->enum('modo_deteccion', ['reconocimiento_facial', 'tapaboca', 'ambos'])
                  ->default('reconocimiento_facial')
                  ->after('grabacion_activa');
        });

        DB::statement("ALTER TABLE alertas MODIFY COLUMN tipo ENUM(
            'rostro_detectado',
            'persona_restringida',
            'desconocido',
            'multiples_rostros',
            'sin_tapaboca'
        ) NOT NULL");
    }

    public function down(): void
    {
        Schema::table('camaras', function (Blueprint $table) {
            $table->dropColumn('modo_deteccion');
        });

        DB::statement("ALTER TABLE alertas MODIFY COLUMN tipo ENUM(
            'rostro_detectado',
            'persona_restringida',
            'desconocido',
            'multiples_rostros'
        ) NOT NULL");
    }
};
