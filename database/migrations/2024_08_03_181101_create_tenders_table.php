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
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->decimal('value', 15, 2)->nullable();
            $table->string('modality')->nullable(); // modalidadeNome
            $table->integer('modality_id')->nullable(); // modalidadeId
            $table->string('status')->nullable(); // modoDisputaNome
            $table->integer('year_purchase')->nullable(); //anoCompra
            $table->string('number_purchase')->nullable(); //numeroCompra
            $table->string('organ_cnpj')->nullable(); //orgaoEntidade.cnpj
            $table->string('organ_name')->nullable(); //orgaoEntidade.razaoSocial            
            $table->string('uf')->nullable(); // unidadeOrgao.ufSigla
            $table->string('city')->nullable(); // unidadeOrgao.municipioNome
            $table->string('city_code')->nullable(); // unidadeOrgao.codigoIbge
            $table->text('description')->nullable(); // amparoLegal.descricao
            $table->text('object')->nullable(); //objetoCompra
            $table->string('instrument_name')->nullable(); // tipoInstrumentoConvocatorioNome
            $table->text('observations')->nullable(); //informacaoComplementar          
            $table->string('origin_url')->nullable(); // linkSistemaOrigem
            $table->string('process')->nullable(); // processo
            $table->date('bid_opening_date')->nullable(); // dataAberturaProposta
            $table->date('proposal_closing_date')->nullable(); // dataEncerramentoProposta    
            $table->date('publication_date')->nullable(); // dataPublicacaoPncp
            $table->date('update_date')->nullable(); // dataAtualizacao
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenders');
    }
};
