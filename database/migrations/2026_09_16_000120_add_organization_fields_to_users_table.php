<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('organizational_unit_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
            $table->string('employee_no', 100)->nullable()->after('name');
            $table->string('position_title')->nullable()->after('employee_no');
            $table->boolean('is_active')->default(true)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropConstrainedForeignId('organizational_unit_id');
            $table->dropColumn(['employee_no', 'position_title', 'is_active']);
        });
    }
};
