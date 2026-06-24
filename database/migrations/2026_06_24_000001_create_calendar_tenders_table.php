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
        Schema::create('calendar_tenders', function (Blueprint $table) {
            $table->unsignedBigInteger('tender_id');
            $table->unsignedBigInteger('user_id');

            $table->unique(['tender_id', 'user_id']);
            $table->foreign('tender_id')->references('id')->on('tenders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_tenders');
    }
};
