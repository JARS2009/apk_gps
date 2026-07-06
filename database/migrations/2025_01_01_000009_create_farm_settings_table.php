<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('farm_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('granja_id')->unique()->constrained('farms')->cascadeOnDelete();
            $table->string('telefono_policia')->nullable();
            $table->string('telefono_emergencia')->nullable();
            $table->text('mensaje_alerta')->nullable();
            $table->boolean('alertas_activas')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_settings');
    }
};
