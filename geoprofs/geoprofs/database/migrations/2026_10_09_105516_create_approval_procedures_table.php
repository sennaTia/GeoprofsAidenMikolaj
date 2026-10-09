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
        Schema::create('approval_procedures', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['manager', 'office-manager'])->default('manager');
            $table->unsignedTinyInteger('order')->default(1);
            $table->text('description')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_procedures');
    }
};
