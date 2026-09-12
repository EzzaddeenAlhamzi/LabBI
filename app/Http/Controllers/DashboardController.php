<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user()->load('roles');

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->hasRole('lab_technician')) {
            return redirect()->route('lab.dashboard');
        }
        if ($user->hasRole('doctor')) {
            return redirect()->route('doctor.dashboard');
        }
        if ($user->hasRole('patient')) {
            return redirect()->route('patient.dashboard');
        }

        abort(403, 'لا يوجد دور مرتبط بالحساب');
    }
}
