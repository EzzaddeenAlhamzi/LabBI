<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabTest;
use App\Models\TestReferenceRange;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RangeController extends Controller
{
    public function index(): View
    {
        return view('admin.ranges.index', [
            'ranges' => TestReferenceRange::with('test')->latest()->paginate(20),
            'tests' => LabTest::where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'test_id' => ['required', 'exists:tests,id'],
            'normal_low' => ['nullable', 'numeric'],
            'normal_high' => ['nullable', 'numeric'],
            'critical_low' => ['nullable', 'numeric'],
            'critical_high' => ['nullable', 'numeric'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $range = TestReferenceRange::create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
        ]);
        $audit->log('ranges.create', $range);

        return back()->with('success', 'تم حفظ المدى المرجعي/الحرج. (محرك القيم الحرجة غير مفعّل في المرحلة 1)');
    }
}