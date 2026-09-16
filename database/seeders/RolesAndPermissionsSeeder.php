<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'market-scoping.view', 'market-scoping.create', 'market-scoping.edit', 'market-scoping.submit', 'market-scoping.approve',
            'ppmp.view', 'ppmp.create', 'ppmp.edit', 'ppmp.submit', 'ppmp.approve',
            'app.view', 'app.create', 'app.edit', 'app.approve',
            'supplier.view', 'supplier.create', 'supplier.edit', 'supplier.verify-documents',
            'procurement.view', 'procurement.create', 'procurement.edit', 'procurement.manage',
            'bid.open', 'bid.evaluate', 'post-qualification.manage',
            'bac.manage', 'bac.recommend', 'award.approve', 'award.issue',
            'contract.view', 'contract.manage', 'inspection.perform',
            'audit.view', 'reports.view', 'administration.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [
            'End User' => ['market-scoping.view', 'market-scoping.create', 'market-scoping.edit', 'market-scoping.submit', 'ppmp.view', 'ppmp.create', 'ppmp.edit', 'ppmp.submit', 'procurement.view'],
            'Procurement Officer' => ['market-scoping.view', 'ppmp.view', 'app.view', 'supplier.view', 'supplier.create', 'supplier.edit', 'supplier.verify-documents', 'procurement.view', 'procurement.create', 'procurement.edit', 'procurement.manage', 'reports.view'],
            'BAC Secretariat' => ['procurement.view', 'procurement.manage', 'bac.manage', 'supplier.view', 'bid.open', 'reports.view'],
            'BAC Member' => ['procurement.view', 'bid.evaluate', 'post-qualification.manage', 'bac.recommend'],
            'HoPE' => ['market-scoping.view', 'ppmp.view', 'app.view', 'app.approve', 'procurement.view', 'award.approve', 'reports.view'],
            'Auditor' => ['market-scoping.view', 'ppmp.view', 'app.view', 'supplier.view', 'procurement.view', 'contract.view', 'audit.view', 'reports.view'],
        ];

        foreach ($roles as $name => $rolePermissions) {
            Role::findOrCreate($name, 'web')->syncPermissions($rolePermissions);
        }
    }
}
