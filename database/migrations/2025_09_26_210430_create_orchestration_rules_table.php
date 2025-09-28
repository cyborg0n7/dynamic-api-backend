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
    Schema::create('orchestration_rules', function (Blueprint $table) {
        $table->id();
        $table->foreignId('api_id')->constrained('apis')->onDelete('cascade');
        $table->string('rule_name');
        $table->json('condition');
        $table->json('action');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('orchestration_rules');
}

};
