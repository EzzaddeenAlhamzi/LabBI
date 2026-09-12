<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\LabTest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestController extends Controller
{
    public function index(): View
    {
        return view('admin.tests.index', [
            'tests' => LabTest::with('department')->orderBy('code')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.tests.create', [
            'departments' => Department::where('is_active', true)->orderBy('name_ar')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:tests,code'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $test = LabTest::create([
            ...$data,
            'code' => strtoupper($data['code']),
            'is_active' => $request->boolean('is_active', true),
        ]);
        $audit->log('catalog.create', $test);

        return redirect()->route('admin.tests.show', $test)->with('success', 'تم إضافة الفحص.');
    }

    public function show(LabTest $test): View
    {
        $test->load(['department', 'referenceRanges']);

        return view('admin.tests.show', compact('test'));
    }
}