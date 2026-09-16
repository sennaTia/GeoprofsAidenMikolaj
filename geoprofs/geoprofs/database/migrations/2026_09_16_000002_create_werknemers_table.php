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
        Schema::create('werknemers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('werkgever_id')->constrained('werkgevers')->cascadeOnDelete();
            $table->string('naam');
            $table->string('email')->unique();
            $table->string('wachtwoord');
            $table->string('functie')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('werknemers');
    }
};
