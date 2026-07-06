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
        Schema::create('collars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->nullable()->unique()->constrained('animals')->nullOnDelete();
            $table->string('serie')->unique();
            $table->string('modelo');
            $table->string('estado')->default('disponible');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collars');
    }
};
