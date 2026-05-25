<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_acceso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('camara_id')->constrained()->cascadeOnDelete();
            $table->enum('resultado', ['permitido', 'denegado', 'desconocido'])->default('desconocido');
            $table->float('confianza')->default(0);
            $table->string('captura')->nullable();
            $table->json('embedding')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_acceso');
    }
};
