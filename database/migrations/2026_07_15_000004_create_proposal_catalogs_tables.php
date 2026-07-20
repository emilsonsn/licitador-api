<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_catalogs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proposal_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('title')->default('Catálogo de Produtos');
            $table->string('subtitle')->nullable();
            $table->longText('general_notes')->nullable();
            $table->string('organ_name')->nullable();
            $table->string('organ_state')->nullable();
            $table->string('purchase_number')->nullable();
            $table->string('process_number')->nullable();
            $table->date('receipt_date')->nullable();
            $table->date('opening_date')->nullable();
            $table->json('company_snapshot')->nullable();
            $table->unsignedBigInteger('last_updated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->foreign('proposal_id', 'catalog_proposal_fk')
                ->references('id')->on('proposals')->cascadeOnDelete();
            $table->foreign('user_id', 'catalog_user_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('company_id', 'catalog_company_fk')
                ->references('id')->on('companies')->nullOnDelete();
            $table->foreign('last_updated_by', 'catalog_updated_by_fk')
                ->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('proposal_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proposal_catalog_id');
            $table->unsignedBigInteger('proposal_item_id')->nullable();
            $table->string('title')->nullable();
            $table->longText('specification')->nullable();
            $table->decimal('quantity', 15, 4)->nullable();
            $table->string('unit')->nullable();
            $table->string('brand')->nullable();
            $table->unsignedInteger('position');
            $table->string('image_path')->nullable();
            $table->string('image_original_name')->nullable();
            $table->string('image_mime', 100)->nullable();
            $table->timestamps();

            $table->foreign('proposal_catalog_id', 'catalog_item_catalog_fk')
                ->references('id')->on('proposal_catalogs')->cascadeOnDelete();
            $table->foreign('proposal_item_id', 'catalog_item_proposal_item_fk')
                ->references('id')->on('proposal_items')->nullOnDelete();
            $table->unique(['proposal_catalog_id', 'position'], 'catalog_item_position_unique');
            $table->unique(['proposal_catalog_id', 'proposal_item_id'], 'catalog_proposal_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_catalog_items');
        Schema::dropIfExists('proposal_catalogs');
    }
};
