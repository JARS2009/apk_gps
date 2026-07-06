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
            $table->foreignId('granja_id')->constrained('farms')->cascadeOnDelete();
            $table->foreignId('collar_id')->constrained('collars')->cascadeOnDelete();
            $table->foreignId('animal_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->foreignId('terreno_id')->nullable()->constrained('lands')->nullOnDelete();
            $table->string('tipo')->default('fuera_de_rango'); // fuera_de_rango, sin_señal
            $table->decimal('latitud', 10, 7);
            $table->decimal('longitud', 10, 7);
            $table->string('mensaje')->nullable();
            $table->boolean('leida')->default(false);
            $table->timestamps();

            $table->index(['granja_id', 'created_at']);
            $table->index(['collar_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas');
    }
};
