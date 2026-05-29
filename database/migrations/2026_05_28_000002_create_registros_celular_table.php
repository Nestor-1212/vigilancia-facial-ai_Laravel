<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_celular', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camara_id')->constrained()->cascadeOnDelete();
            $table->foreignId('persona_id')->nullable()->constrained()->nullOnDelete();
            $table->datetime('inicio');
            $table->datetime('fin')->nullable();
            $table->unsignedInteger('duracion_segundos')->nullable();
            $table->string('foto_rostro')->nullable();
            $table->float('confianza_facial')->default(0);
            $table->float('confianza_celular')->default(0);
            $table->enum('estado', ['activo', 'finalizado'])->default('activo');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_celular');
    }
};
