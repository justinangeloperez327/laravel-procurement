<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bidder_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_round_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('registered');
            $table->string('supplier_code_snapshot', 50);
            $table->string('supplier_name_snapshot');
            $table->string('philgeps_registration_no_snapshot', 100)->nullable();
            $table->string('representative_name')->nullable();
            $table->string('representative_email')->nullable();
            $table->string('representative_phone', 50)->nullable();
            $table->timestamp('registered_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['procurement_round_id', 'supplier_id']);
            $table->index(['procurement_round_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bidder_participations');
    }
};
