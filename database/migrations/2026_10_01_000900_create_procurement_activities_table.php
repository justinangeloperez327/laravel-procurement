<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_round_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sequence_no');
            $table->string('activity_type', 75);
            $table->string('status', 30)->default('scheduled');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('actual_at')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('minutes')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['procurement_round_id', 'sequence_no']);
            $table->index(['procurement_round_id', 'status']);
            $table->index(['procurement_round_id', 'activity_type']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_activities');
    }
};
