<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $query = Patient::query()->with('primaryDoctor.user');

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('medical_file_number', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return view('admin.patients.index', [
            'patients' => $query->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.patients.create', [
            'doctors' => Doctor::with('user')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'medical_file_number' => ['nullable', 'string', 'max:50', 'unique:patients,medical_file_number'],
            'date_of_birth' => ['nullable', 'date'],
            'sex' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'primary_doctor_id' => ['nullable', 'exists:doctors,id'],
            'create_login' => ['sometimes', 'boolean'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $patient = DB::transaction(function () use ($data, $request) {
            $userId = null;
            if ($request->boolean('create_login') && ! empty($data['email'])) {
                $user = User::create([
                    'name' => $data['full_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'password' => Hash::make($data['password'] ?? 'password'),
                    'is_active' => true,
                ]);
                $user->roles()->sync([Role::where('code', 'patient')->value('id')]);
                $userId = $user->id;
            }

            $medicalId = $data['medical_file_number'] ?? $this->nextMedicalId();

            return Patient::create([
                'user_id' => $userId,
                'medical_file_number' => $medicalId,
                'full_name' => $data['full_name'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'sex' => $data['sex'] ?? null,
                'phone' => $data['phone'] ?? null,
                'national_id' => $data['national_id'] ?? null,
                'primary_doctor_id' => $data['primary_doctor_id'] ?? null,
            ]);
        });

        $audit->log('patients.create', $patient, null, $patient->toArray());

        return redirect()->route('admin.patients.show', $patient)->with('success', 'تم إنشاء ملف المريض.');
    }

    public function show(Patient $patient): View
    {
        $patient->load(['primaryDoctor.user', 'orders.items.test', 'user']);

        return view('admin.patients.show', compact('patient'));
    }

    public function edit(Patient $patient): View
    {
        return view('admin.patients.edit', [
            'patient' => $patient,
            'doctors' => Doctor::with('user')->orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, Patient $patient, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'sex' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'primary_doctor_id' => ['nullable', 'exists:doctors,id'],
        ]);

        $old = $patient->toArray();
        $patient->update($data);
        $audit->log('patients.update', $patient, $old, $patient->toArray());

        return redirect()->route('admin.patients.show', $patient)->with('success', 'تم تحديث بيانات المريض.');
    }

    private function nextMedicalId(): string
    {
        $last = Patient::withTrashed()->orderByDesc('id')->value('medical_file_number');
        $n = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $n = ((int) $m[1]) + 1;
        }

        return 'MF-'.str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }
}