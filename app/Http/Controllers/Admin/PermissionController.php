<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        return view('admin.permissions.index', [
            'permissions' => Permission::with('roles')->orderBy('code')->get(),
        ]);
    }
}