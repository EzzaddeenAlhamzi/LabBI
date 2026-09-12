<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $patients = collect();
        if ($search = $request->string('q')->toString()) {
            $patients = Patient::query()
                ->where('medical_file_number', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->limit(25)
                ->get();
        }

        return view('lab.patients.search', compact('patients', 'search'));
    }
}