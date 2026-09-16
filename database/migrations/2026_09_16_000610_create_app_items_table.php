<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annual_procurement_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ppmp_item_id')->constrained()->restrictOnDelete();
            $table->string('app_item_no', 100);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('procurement_category', 50);
            $table->decimal('estimated_budget', 18, 2);
            $table->string('funding_source')->nullable();
            $table->string('procurement_method', 150)->nullable();
            $table->date('schedule_start')->nullable();
            $table->date('schedule_end')->nullable();
            $table->string('status', 30)->default('planned');
            $table->timestamps();
            $table->unique(['annual_procurement_plan_id', 'app_item_no'], 'app_item_number_unique');
            $table->index(['procurement_category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_items');
    }
};
