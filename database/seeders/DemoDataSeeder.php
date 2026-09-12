<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\LaboratoryStaff;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Role;
use App\Models\TestReferenceRange;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $dept = Department::query()->firstOrCreate(
            ['code' => 'HEMATOLOGY'],
            ['name_ar' => 'أمراض الدم', 'is_active' => true]
        );

        Department::query()->firstOrCreate(
            ['code' => 'CHEMISTRY'],
            ['name_ar' => 'الكيمياء الحيوية', 'is_active' => true]
        );

        $doctorRole = Role::query()->where('code', 'doctor')->firstOrFail();
        $labRole = Role::query()->where('code', 'lab_technician')->firstOrFail();
        $patientRole = Role::query()->where('code', 'patient')->firstOrFail();

        $doctorUser = User::query()->firstOrCreate(
            ['email' => 'doctor@labbi.local'],
            [
                'name' => 'د. أحمد الطبيب',
                'password' => Hash::make('password'),
                'is_active' => true,
                'phone' => '0500000002',
            ]
        );
        $doctorUser->roles()->syncWithoutDetaching([$doctorRole->id]);
        $doctor = Doctor::query()->firstOrCreate(
            ['user_id' => $doctorUser->id],
            [
                'license_number' => 'DOC-1001',
                'specialty' => 'طب باطني',
                'department_id' => $dept->id,
            ]
        );

        $labUser = User::query()->firstOrCreate(
            ['email' => 'lab@labbi.local'],
            [
                'name' => 'فني المختبر',
                'password' => Hash::make('password'),
                'is_active' => true,
                'phone' => '0500000003',
            ]
        );
        $labUser->roles()->syncWithoutDetaching([$labRole->id]);
        LaboratoryStaff::query()->firstOrCreate(
            ['user_id' => $labUser->id],
            ['department_id' => $dept->id, 'job_title' => 'فني مختبر']
        );

        $patientUser = User::query()->firstOrCreate(
            ['email' => 'patient@labbi.local'],
            [
                'name' => 'مريض تجريبي',
                'password' => Hash::make('password'),
                'is_active' => true,
                'phone' => '0500000004',
            ]
        );
        $patientUser->roles()->syncWithoutDetaching([$patientRole->id]);

        Patient::query()->firstOrCreate(
            ['medical_file_number' => 'MF-000001'],
            [
                'user_id' => $patientUser->id,
                'full_name' => 'مريض تجريبي',
                'date_of_birth' => '1990-01-15',
                'sex' => 'male',
                'phone' => '0500000004',
                'primary_doctor_id' => $doctor->id,
            ]
        );

        $hb = LabTest::query()->firstOrCreate(
            ['code' => 'HB'],
            [
                'name_ar' => 'الهيموغلوبين',
                'name_en' => 'Hemoglobin',
                'unit' => 'g/dL',
                'department_id' => $dept->id,
                'is_active' => true,
            ]
        );

        TestReferenceRange::query()->firstOrCreate(
            ['test_id' => $hb->id, 'is_active' => true],
            [
                'normal_low' => 12,
                'normal_high' => 16,
                'critical_low' => 7,
                'critical_high' => 20,
            ]
        );

        LabTest::query()->firstOrCreate(
            ['code' => 'GLU'],
            [
                'name_ar' => 'السكر في الدم',
                'name_en' => 'Glucose',
                'unit' => 'mg/dL',
                'department_id' => $dept->id,
                'is_active' => true,
            ]
        );
    }
}
