<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_code', 50);
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('tin', 50)->nullable();
            $table->string('philgeps_registration_no', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address_line')->nullable();
            $table->string('city_municipality', 120)->nullable();
            $table->string('province', 120)->nullable();
            $table->char('country_code', 2)->default('PH');
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->unique(['organization_id', 'supplier_code']);
            $table->unique(['organization_id', 'tin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
