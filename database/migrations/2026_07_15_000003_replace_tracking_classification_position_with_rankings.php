<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL may leave the table behind when adding a constraint fails during
        // Schema::create, even though Laravel does not record the migration.
        Schema::dropIfExists('proposal_tracking_item_rankings');

        Schema::create('proposal_tracking_item_rankings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proposal_tracking_item_id');
            $table->unsignedTinyInteger('position');
            $table->string('company');
            $table->string('brand')->nullable();
            $table->decimal('price', 15, 4);
            $table->timestamps();

            $table->foreign('proposal_tracking_item_id', 'tracking_ranking_item_fk')
                ->references('id')
                ->on('proposal_tracking_items')
                ->cascadeOnDelete();
            $table->unique(['proposal_tracking_item_id', 'position'], 'tracking_item_position_unique');
        });

        Schema::table('proposal_tracking_items', function (Blueprint $table) {
            $table->dropColumn('classification_position');
        });
    }

    public function down(): void
    {
        Schema::table('proposal_tracking_items', function (Blueprint $table) {
            $table->unsignedInteger('classification_position')->nullable()->after('minimum_unit_price');
        });

        Schema::dropIfExists('proposal_tracking_item_rankings');
    }
};
