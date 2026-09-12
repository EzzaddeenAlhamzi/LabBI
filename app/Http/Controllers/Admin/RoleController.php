<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::withCount(['users', 'permissions'])->orderBy('id')->get(),
        ]);
    }

    public function show(Role $role): View
    {
        return view('admin.roles.show', [
            'role' => $role->load('permissions'),
        ]);
    }
}