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
        if (Schema::hasColumn('calendar_tenders', 'status')) {
            return;
        }

        Schema::table('calendar_tenders', function (Blueprint $table) {
            $table->string('status')->default('participating')->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('calendar_tenders', 'status')) {
            return;
        }

        Schema::table('calendar_tenders', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
