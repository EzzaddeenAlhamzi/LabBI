<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        return view('admin.departments.index', [
            'departments' => Department::orderBy('name_ar')->paginate(20),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:departments,code'],
            'name_ar' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $dept = Department::create([
            'code' => strtoupper($data['code']),
            'name_ar' => $data['name_ar'],
            'is_active' => $request->boolean('is_active', true),
        ]);
        $audit->log('departments.create', $dept);

        return back()->with('success', 'تم إضافة القسم.');
    }
}