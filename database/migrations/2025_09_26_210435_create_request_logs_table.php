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
    Schema::create('request_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('api_id')->constrained('apis')->onDelete('cascade');
        $table->json('request_payload')->nullable();
        $table->json('response_payload')->nullable();
        $table->integer('status_code');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('request_logs');
}

};
