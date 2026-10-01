<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_project_id')->constrained()->restrictOnDelete();
            $table->foreignId('procurement_method_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('round_no');
            $table->string('reference_no')->nullable();
            $table->string('status', 50)->default('draft');
            $table->text('failure_reason')->nullable();
            $table->timestamp('pre_procurement_at')->nullable();
            $table->timestamp('posting_started_at')->nullable();
            $table->timestamp('bid_opening_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['procurement_project_id', 'round_no']);
            $table->index(['procurement_project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_rounds');
    }
};
