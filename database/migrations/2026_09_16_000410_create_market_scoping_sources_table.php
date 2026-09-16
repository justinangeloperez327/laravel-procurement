<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_scoping_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_scoping_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vendor_name')->nullable();
            $table->decimal('indicative_price', 18, 2)->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->string('source_type', 80)->nullable();
            $table->string('source_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['market_scoping_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_scoping_sources');
    }
};
