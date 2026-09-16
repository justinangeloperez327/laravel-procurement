<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_scopings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('organizational_unit_id')->constrained()->restrictOnDelete();
            $table->string('reference_no', 100);
            $table->string('title');
            $table->string('procurement_category', 50);
            $table->text('description')->nullable();
            $table->text('market_findings')->nullable();
            $table->text('recommended_strategy')->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'reference_no']);
            $table->index(['fiscal_year_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_scopings');
    }
};
