<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedInteger('min_staffing')->default(1);
        });

        Schema::create('staffing_plans', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('min_staffing');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['department_id', 'date']);
        });

        Schema::table('leave_approvals', function (Blueprint $table) {
            $table->unsignedInteger('lowest_staffing')->nullable();
            $table->unsignedInteger('min_staffing')->nullable();
            $table->text('override_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staffing_plans');
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('min_staffing');
        });

        Schema::table('leave_approvals', function (Blueprint $table) {
            $table->dropColumn(['lowest_staffing', 'min_staffing', 'override_reason']);
        });
    }
};
