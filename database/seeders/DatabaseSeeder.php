<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds four V1 roles only (NO receptionist — D-009 / GAP-05).
 * results.approve / results.return assigned ONLY to Admin (D-008 / REV-05).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'users.view', 'users.create', 'users.update', 'users.activate',
            'roles.manage', 'permissions.manage',
            'patients.view_all', 'patients.view_assigned', 'patients.view_self',
            'patients.create', 'patients.update',
            'doctors.manage', 'lab_staff.manage', 'departments.manage',
            'catalog.view', 'catalog.manage', 'ranges.manage',
            'orders.view_all', 'orders.view_own_doctor', 'orders.view_lab', 'orders.view_self',
            'orders.create', 'orders.submit',
            'samples.receive', 'samples.reject', 'samples.start_analysis',
            'results.enter', 'results.submit', 'results.approve', 'results.return',
            'results.view_all', 'results.view_own_doctor', 'results.view_self',
            'attachments.upload', 'attachments.view',
            'critical.view_all', 'critical.view_own',
            'notifications.view_self',
            'pdf.generate', 'pdf.download_own', 'pdf.download_clinical',
            'comparison.use', 'requests.create', 'requests.view_own', 'requests.manage_doctor',
            'bi.view', 'reports.operational', 'audit.view', 'backups.manage', 'settings.manage',
        ];

        $permissionModels = [];
        foreach ($permissions as $code) {
            $permissionModels[$code] = Permission::query()->firstOrCreate(
                ['code' => $code],
                ['description' => $code]
            );
        }

        $roles = [
            'admin' => ['name_ar' => 'الإدارة', 'name_en' => 'Admin'],
            'lab_technician' => ['name_ar' => 'فني المختبر', 'name_en' => 'Laboratory Technician'],
            'doctor' => ['name_ar' => 'الطبيب', 'name_en' => 'Doctor'],
            'patient' => ['name_ar' => 'المريض', 'name_en' => 'Patient'],
        ];

        $roleModels = [];
        foreach ($roles as $code => $names) {
            $roleModels[$code] = Role::query()->firstOrCreate(
                ['code' => $code],
                $names
            );
        }

        // Explicitly ensure receptionist is never seeded
        Role::query()->where('code', 'receptionist')->delete();

        $adminPerms = array_keys($permissionModels);
        $labPerms = [
            'patients.view_all', 'patients.create', 'patients.update',
            'catalog.view', 'ranges.manage',
            'orders.view_lab', 'orders.view_all',
            'samples.receive', 'samples.reject', 'samples.start_analysis',
            'results.enter', 'results.submit', 'results.view_all',
            'attachments.upload', 'attachments.view',
            'critical.view_all', 'pdf.generate', 'pdf.download_clinical',
            'notifications.view_self',
        ];
        $doctorPerms = [
            'patients.view_assigned', 'patients.create',
            'catalog.view',
            'orders.create', 'orders.submit', 'orders.view_own_doctor',
            'results.view_own_doctor',
            'critical.view_own', 'pdf.download_clinical',
            'comparison.use', 'requests.manage_doctor',
            'notifications.view_self', 'attachments.view',
        ];
        $patientPerms = [
            'patients.view_self',
            'orders.view_self',
            'results.view_self',
            'pdf.download_own',
            'notifications.view_self',
            'requests.create', 'requests.view_own',
        ];

        $roleModels['admin']->permissions()->sync(
            collect($adminPerms)->map(fn ($c) => $permissionModels[$c]->id)->all()
        );
        $roleModels['lab_technician']->permissions()->sync(
            collect($labPerms)->map(fn ($c) => $permissionModels[$c]->id)->all()
        );
        $roleModels['doctor']->permissions()->sync(
            collect($doctorPerms)->map(fn ($c) => $permissionModels[$c]->id)->all()
        );
        $roleModels['patient']->permissions()->sync(
            collect($patientPerms)->map(fn ($c) => $permissionModels[$c]->id)->all()
        );

        // Guard: approval permissions only on admin
        foreach (['results.approve', 'results.return'] as $approveCode) {
            $perm = $permissionModels[$approveCode];
            $perm->roles()->sync([$roleModels['admin']->id]);
        }

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@labbi.local'],
            [
                'name' => 'مدير النظام',
                'password' => Hash::make('password'),
                'is_active' => true,
                'phone' => '0500000001',
            ]
        );
        $admin->roles()->syncWithoutDetaching([$roleModels['admin']->id]);

        $this->call([
            DemoDataSeeder::class,
        ]);
    }
}
