<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('granja_id')->constrained('farms')->cascadeOnDelete();
            $table->string('nombre', 150);
            $table->string('prefijo_codigo', 20);
            $table->boolean('estado')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['granja_id', 'prefijo_codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_types');
    }
};
