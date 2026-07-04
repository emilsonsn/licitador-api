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
        $columns = array_filter([
            Schema::hasColumn('users', 'cnpj') ? 'cnpj' : null,
            Schema::hasColumn('users', 'corporate_reason') ? 'corporate_reason' : null,
            Schema::hasColumn('users', 'fantasy_name') ? 'fantasy_name' : null,
            Schema::hasColumn('users', 'opening_date') ? 'opening_date' : null,
        ]);

        if (empty($columns)) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cnpj')->nullable()->after('state');
            $table->string('corporate_reason')->nullable()->after('cnpj');
            $table->string('fantasy_name')->nullable()->after('corporate_reason');
            $table->date('opening_date')->nullable()->after('fantasy_name');
        });
    }
};
