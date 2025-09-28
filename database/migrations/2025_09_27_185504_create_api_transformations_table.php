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
        Schema::create('api_transformations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_id')->constrained()->onDelete('cascade');
            $table->json('rules'); // store transformation rules in JSON
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_transformations');
    }
};
