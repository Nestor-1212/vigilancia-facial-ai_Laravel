<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camara_id')->constrained()->cascadeOnDelete();
            $table->foreignId('persona_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('tipo', ['rostro_detectado', 'persona_restringida', 'desconocido', 'multiples_rostros'])->default('rostro_detectado');
            $table->enum('nivel', ['info', 'advertencia', 'critico'])->default('info');
            $table->float('confianza')->default(0);
            $table->string('captura')->nullable();
            $table->boolean('revisada')->default(false);
            $table->timestamp('revisada_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
