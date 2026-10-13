<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bid_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_round_id')->constrained()->restrictOnDelete();
            $table->foreignId('bidder_participation_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('received');
            $table->string('receipt_reference', 100)->nullable();
            $table->string('package_reference', 100)->nullable();
            $table->timestamp('received_at');
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->foreignId('opened_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->decimal('bid_amount', 18, 2)->nullable();
            $table->char('currency', 3)->default('PHP');
            $table->boolean('eligibility_documents_present')->nullable();
            $table->boolean('technical_documents_present')->nullable();
            $table->boolean('financial_documents_present')->nullable();
            $table->boolean('bid_security_present')->nullable();
            $table->text('opening_remarks')->nullable();
            $table->timestamps();

            $table->unique('bidder_participation_id');
            $table->unique(['procurement_round_id', 'receipt_reference']);
            $table->index(['procurement_round_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bid_submissions');
    }
};
