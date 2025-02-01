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
        Schema::table('tenders', function (Blueprint $table) {
            $table->dateTime('bid_opening_date')->change();
            $table->dateTime('proposal_closing_date')->change();
            $table->dateTime('publication_date')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->date('bid_opening_date')->change();
            $table->date('proposal_closing_date')->change();
            $table->date('publication_date')->change();
        });
    }
};
