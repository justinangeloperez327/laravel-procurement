<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('app_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('procurement_method_id')->constrained()->restrictOnDelete();
            $table->string('reference_no', 100);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('procurement_category', 50);
            $table->decimal('approved_budget', 18, 2);
            $table->string('funding_source')->nullable();
            $table->string('status', 40)->default('planned');
            $table->string('current_stage', 100)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('procurement_officer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('target_start_date')->nullable();
            $table->date('target_completion_date')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'reference_no']);
            $table->index(['fiscal_year_id', 'status']);
            $table->index(['procurement_method_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_projects');
    }
};
