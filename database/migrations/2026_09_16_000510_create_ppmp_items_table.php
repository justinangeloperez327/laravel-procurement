<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppmp_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ppmp_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_scoping_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_no', 100);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('procurement_category', 50);
            $table->decimal('quantity', 18, 3)->default(1);
            $table->string('unit', 50)->nullable();
            $table->decimal('estimated_unit_cost', 18, 2)->nullable();
            $table->decimal('estimated_budget', 18, 2);
            $table->string('funding_source')->nullable();
            $table->unsignedTinyInteger('target_quarter')->nullable();
            $table->string('recommended_procurement_method', 150)->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['ppmp_id', 'item_no']);
            $table->index(['procurement_category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppmp_items');
    }
};
