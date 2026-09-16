<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates the procurement planning foundation schema', function () {
    expect(Schema::hasTable('organizations'))->toBeTrue()
        ->and(Schema::hasTable('organizational_units'))->toBeTrue()
        ->and(Schema::hasTable('fiscal_years'))->toBeTrue()
        ->and(Schema::hasTable('suppliers'))->toBeTrue()
        ->and(Schema::hasTable('supplier_documents'))->toBeTrue()
        ->and(Schema::hasTable('procurement_methods'))->toBeTrue()
        ->and(Schema::hasTable('market_scopings'))->toBeTrue()
        ->and(Schema::hasTable('market_scoping_sources'))->toBeTrue()
        ->and(Schema::hasTable('ppmps'))->toBeTrue()
        ->and(Schema::hasTable('ppmp_items'))->toBeTrue()
        ->and(Schema::hasTable('annual_procurement_plans'))->toBeTrue()
        ->and(Schema::hasTable('app_items'))->toBeTrue()
        ->and(Schema::hasTable('procurement_projects'))->toBeTrue();
});

it('extends users with procurement organization context', function () {
    expect(Schema::hasColumns('users', [
        'organization_id',
        'organizational_unit_id',
        'employee_no',
        'position_title',
        'is_active',
    ]))->toBeTrue();
});
