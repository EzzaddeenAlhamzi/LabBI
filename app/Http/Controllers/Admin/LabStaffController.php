<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\LaboratoryStaff;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LabStaffController extends Controller
{
    public function index(): View
    {
        return view('admin.lab-staff.index', [
            'staff' => LaboratoryStaff::with(['user', 'department'])->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.lab-staff.create', [
            'departments' => Department::where('is_active', true)->orderBy('name_ar')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $staff = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);
            $user->roles()->sync([Role::where('code', 'lab_technician')->value('id')]);

            return LaboratoryStaff::create([
                'user_id' => $user->id,
                'job_title' => $data['job_title'] ?? null,
                'department_id' => $data['department_id'] ?? null,
            ]);
        });

        $audit->log('lab_staff.create', $staff);

        return redirect()->route('admin.lab-staff.index')->with('success', 'تم إضافة فني المختبر.');
    }
}