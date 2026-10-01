<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annual_procurement_plans', function (Blueprint $table) {
            $table->string('app_type', 20)->default('indicative');
            $table->foreignId('recommended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recommended_at')->nullable();

            $table->index(['app_type', 'status']);
        });

        Schema::table('app_items', function (Blueprint $table) {
            $table->boolean('is_early_procurement_activity')->default(false);
            $table->string('bid_evaluation_criteria', 30)->nullable();
            $table->json('procurement_strategy_tools')->nullable();
            $table->text('remarks')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('app_items', function (Blueprint $table) {
            $table->dropColumn([
                'is_early_procurement_activity',
                'bid_evaluation_criteria',
                'procurement_strategy_tools',
                'remarks',
            ]);
        });

        Schema::table('annual_procurement_plans', function (Blueprint $table) {
            $table->dropIndex(['app_type', 'status']);
            $table->dropConstrainedForeignId('recommended_by');
            $table->dropColumn([
                'app_type',
                'recommended_at',
            ]);
        });
    }
};
