<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collars', function (Blueprint $table) {
            $table->string('imei', 20)->nullable()->unique()->after('animal_id')
                ->comment('IMEI del dispositivo GPS físico (SinoTrack)');
        });
    }

    public function down(): void
    {
        Schema::table('collars', function (Blueprint $table) {
            $table->dropColumn('imei');
        });
    }
};
