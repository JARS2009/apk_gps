<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ubicacion_prueba', function (Blueprint $table) {
            $table->timestamp('synced_at')->nullable()->after('fecha_gps')
                ->comment('Marca cuando el registro fue sincronizado a collar_locations');

            $table->index('synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('ubicacion_prueba', function (Blueprint $table) {
            $table->dropIndex(['synced_at']);
            $table->dropColumn('synced_at');
        });
    }
};
