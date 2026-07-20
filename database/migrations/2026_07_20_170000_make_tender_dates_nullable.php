<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dateTime('bid_opening_date')->nullable()->change();
            $table->dateTime('proposal_closing_date')->nullable()->change();
            $table->dateTime('publication_date')->nullable()->change();
            $table->dateTime('update_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tenders', function (Blueprint $table) {
            $table->dateTime('bid_opening_date')->nullable(false)->change();
            $table->dateTime('proposal_closing_date')->nullable(false)->change();
            $table->dateTime('publication_date')->nullable(false)->change();
            $table->dateTime('update_date')->nullable()->change();
        });
    }
};
