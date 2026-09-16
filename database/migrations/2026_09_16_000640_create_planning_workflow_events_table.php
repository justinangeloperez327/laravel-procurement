<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planning_workflow_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('subject_type', 80);
            $table->unsignedBigInteger('subject_id');
            $table->string('action', 40);
            $table->string('from_status', 30);
            $table->string('to_status', 30);
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id', 'created_at'], 'planning_workflow_subject_index');
            $table->index(['organization_id', 'action', 'created_at'], 'planning_workflow_org_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_workflow_events');
    }
};
