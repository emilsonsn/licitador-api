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
        Schema::table('users', function (Blueprint $table) {
            $table->string('surname')->nullable()->after('name');
            $table->date('birthday')->nullable()->after('email');
            $table->string('postalcode')->nullable()->after('birthday');
            $table->string('address')->nullable()->after('postalcode');
            $table->string('city')->nullable()->after('address');
            $table->string('state')->nullable()->after('city');
            $table->string('cnpj')->nullable()->after('state');
            $table->string('corporate_reason')->nullable()->after('cnpj');
            $table->string('fantasy_name')->nullable()->after('corporate_reason');
            $table->date('opening_date')->nullable()->after('fantasy_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'surname',
                'birthday',
                'postalcode',
                'address',
                'city',
                'state',
                'cnpj',
                'corporate_reason',
                'fantasy_name',
                'opening_date',
            ]);
        });
    }
};
