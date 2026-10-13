<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bid_eligibility_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_round_id')->constrained()->restrictOnDelete();
            $table->foreignId('bid_submission_id')->unique()->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('pending');
            $table->timestamp('evaluated_at')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('decision_remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['procurement_round_id', 'status']);
        });

        Schema::create('bid_eligibility_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bid_eligibility_evaluation_id')
                ->constrained('bid_eligibility_evaluations')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence_no');
            $table->string('criterion', 500);
            $table->string('result', 30)->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(
                ['bid_eligibility_evaluation_id', 'sequence_no'],
                'bid_eligibility_items_sequence_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bid_eligibility_items');
        Schema::dropIfExists('bid_eligibility_evaluations');
    }
};
