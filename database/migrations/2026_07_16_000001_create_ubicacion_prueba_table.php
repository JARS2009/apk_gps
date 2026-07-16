<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ubicacion_prueba', function (Blueprint $table) {
            $table->id();
            $table->string('imei', 20)->nullable()->comment('IMEI del dispositivo SinoTrack');
            $table->string('ubicacion')->comment('Formato: latitud,longitud');
            $table->decimal('latitud', 10, 7);
            $table->decimal('longitud', 10, 7);
            $table->decimal('velocidad', 6, 2)->nullable()->comment('km/h');
            $table->decimal('rumbo', 6, 2)->nullable()->comment('Grados');
            $table->string('evento', 50)->default('ubicacion')->comment('login|ubicacion|heartbeat|alarma');
            $table->text('trama_raw')->nullable()->comment('Trama hexadecimal cruda para debug');
            $table->timestamp('fecha_gps')->nullable()->comment('Fecha/hora reportada por el GPS');
            $table->timestamps();

            $table->index('imei');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicacion_prueba');
    }
};
