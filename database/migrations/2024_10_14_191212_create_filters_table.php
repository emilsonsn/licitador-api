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
        Schema::create('filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('object')->nullable(); // Objeto da licitação
            $table->string('uf', 2)->nullable(); // Estado (Unidade Federativa)
            $table->string('city')->nullable(); // Cidade
            $table->text('modality_ids')->nullable(); // Modalidades (armazenar múltiplos valores)
            $table->date('update_date_start')->nullable(); // Data de início do prazo
            $table->date('update_date_end')->nullable(); // Data de término do prazo
            $table->string('organ_cnpj', 18)->nullable(); // CNPJ do órgão
            $table->string('organ_name')->nullable(); // Nome do órgão
            $table->string('process')->nullable(); // Nº do processo
            $table->text('observations')->nullable(); // Observações
            $table->timestamps(); // created_at e updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filters');
    }
};
