<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 50);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'user_id', 'role'], 'procurement_role_assignment_unique');
            $table->index(['organization_id', 'role', 'is_active'], 'procurement_role_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_role_assignments');
    }
};
