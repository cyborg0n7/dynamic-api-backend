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
        Schema::create('apis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->string('name');
        $table->text('endpoint');
        $table->string('api_key')->unique();
        $table->enum('method', ['GET','POST','PUT','DELETE']);
        $table->enum('auth_type', ['API_KEY','OAUTH','JWT']);
        $table->json('transformation_rules')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apis');
    }
};
