<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('granja_id')->constrained('farms')->cascadeOnDelete();
            $table->string('serie', 20);
            $table->string('correlativo', 20);
            $table->string('proveedor', 255)->nullable();
            $table->date('fecha');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['serie', 'correlativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
