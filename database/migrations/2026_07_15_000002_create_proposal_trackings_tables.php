<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('discount_percentage', 7, 4)->nullable();
            $table->string('status')->default('open');
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('proposal_tracking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_tracking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposal_item_id')->constrained()->cascadeOnDelete();
            $table->string('result')->default('pending');
            $table->decimal('minimum_unit_price', 15, 4)->nullable();
            $table->unsignedInteger('classification_position')->nullable();
            $table->timestamp('classified_at')->nullable();
            $table->foreignId('classified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['proposal_tracking_id', 'proposal_item_id'], 'tracking_proposal_item_unique');
            $table->index(['proposal_tracking_id', 'result']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_tracking_items');
        Schema::dropIfExists('proposal_trackings');
    }
};
