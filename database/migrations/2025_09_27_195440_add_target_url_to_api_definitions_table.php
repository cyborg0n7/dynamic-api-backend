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
        Schema::table('api_definitions', function (Blueprint $table) {
            $table->string('target_url')->after('method');
        });
    }

    public function down(): void
    {
        Schema::table('api_definitions', function (Blueprint $table) {
            $table->dropColumn('target_url');
        });
    }
};
