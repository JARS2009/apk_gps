<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->string('tipo_documento', 50)->comment('factura, guia, boleta, nota_credito, etc.');
            $table->string('serie_documento', 20);
            $table->string('correlativo_documento', 20);
            $table->date('fecha_documento')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['tipo_documento', 'serie_documento', 'correlativo_documento'], 'purchase_docs_tipo_serie_correlativo_unique');
            $table->index(['serie_documento', 'correlativo_documento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_documents');
    }
};
