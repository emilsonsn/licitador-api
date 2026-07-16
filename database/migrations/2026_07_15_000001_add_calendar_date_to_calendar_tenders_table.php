<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('calendar_tenders', 'calendar_date')) {
            return;
        }

        Schema::table('calendar_tenders', function (Blueprint $table) {
            $table->dateTime('calendar_date')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('calendar_tenders', 'calendar_date')) {
            return;
        }

        Schema::table('calendar_tenders', function (Blueprint $table) {
            $table->dropColumn('calendar_date');
        });
    }
};
