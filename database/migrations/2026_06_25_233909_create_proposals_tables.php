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
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('organ_name')->nullable();
            $table->string('organ_state')->nullable();
            $table->string('purchase_number')->nullable();
            $table->string('process_number')->nullable();
            $table->date('receipt_date')->nullable();
            $table->date('opening_date')->nullable();
            $table->longText('declarations')->nullable();
            $table->string('city')->nullable();
            $table->date('proposal_date')->nullable();
            $table->string('responsible_name')->nullable();
            $table->string('responsible_rg')->nullable();
            $table->string('responsible_cpf')->nullable();
            $table->decimal('total_value', 15, 2)->nullable();
            $table->string('status')->default('draft');
            $table->json('company_snapshot')->nullable();
            $table->json('tender_snapshot')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('proposal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->string('item')->nullable();
            $table->decimal('quantity', 15, 4)->nullable();
            $table->string('unit')->nullable();
            $table->text('specification')->nullable();
            $table->string('brand')->nullable();
            $table->decimal('unit_price', 15, 4)->nullable();
            $table->decimal('total_value', 15, 2)->nullable();
            $table->json('source_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_items');
        Schema::dropIfExists('proposals');
    }
};
