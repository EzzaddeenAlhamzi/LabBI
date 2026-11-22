@extends('layouts.admin')
@section('title', 'المستخدمون')
@section('heading', 'إدارة المستخدمين')
@section('subheading', 'حسابات النظام والأدوار والصلاحيات')
@section('page_actions')
    <a class="btn" href="{{ route('admin.users.create') }}"><x-icon name="user-plus" /> إنشاء مستخدم</a>
@endsection
@section('content')
<div class="card">
    <form method="get" class="form-grid" style="grid-template-columns: 2fr 1fr auto; align-items:end">
        <div class="field" style="margin-bottom:0">
            <label for="q">بحث</label>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="الاسم أو البريد الإلكتروني">
        </div>
        <div class="field" style="margin-bottom:0">
            <label for="role">الدور</label>
            <select id="role" name="role">
                <option value="">كل الأدوار</option>
                @foreach($roles as $role)
                    <option value="{{ $role->code }}" @selected(request('role')===$role->code)>{{ $role->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex" style="margin-bottom:0">
            <button class="btn" type="submit"><x-icon name="search" /> تصفية</button>
            @if(request()->hasAny(['q','role']))<a class="btn btn-ghost" href="{{ route('admin.users.index') }}">إعادة تعيين</a>@endif
        </div>
    </form>
</div>

<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>المستخدم</th><th>البريد الإلكتروني</th><th>الدور</th><th>الحالة</th><th>آخر دخول</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($users as $user)
    <tr>
        <td>
            <div class="flex" style="gap:.6rem">
                <span class="avatar" style="width:34px;height:34px;border-radius:50%;background:var(--teal-light);color:var(--teal-hover);display:grid;place-items:center;font-weight:700;font-size:.78rem;flex:none">{{ collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn($w)=>mb_substr($w,0,1))->implode('') }}</span>
                <div><b>{{ $user->name }}</b>@if($user->phone)<div class="muted-cell small">{{ $user->phone }}</div>@endif</div>
            </div>
        </td>
        <td class="muted-cell" dir="ltr" style="text-align:start">{{ $user->email }}</td>
        <td>{{ $user->roles->pluck('name_ar')->join('، ') ?: '—' }}</td>
        <td><x-status-badge :status="$user->is_active ? 'active' : 'inactive'" :label="$user->is_active ? 'نشط' : 'معطّل'" /></td>
        <td class="muted-cell">{{ optional($user->last_login_at)->format('Y-m-d H:i') ?: '—' }}</td>
        <td>
            <div class="row-actions">
                <a class="btn btn-ghost btn-sm" href="{{ route('admin.users.edit', $user) }}"><x-icon name="pencil" /> تعديل</a>
                <form method="post" action="{{ route('admin.users.toggle', $user) }}">@csrf
                    <button class="btn {{ $user->is_active ? 'btn-outline-danger' : 'btn-secondary' }} btn-sm" type="submit">
                        <x-icon :name="$user->is_active ? 'ban' : 'check'" /> {{ $user->is_active ? 'تعطيل' : 'تفعيل' }}
                    </button>
                </form>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="6"><div class="empty"><div class="empty-icon"><x-icon name="users" /></div><h3>لا يوجد مستخدمون</h3><p>لا توجد حسابات مطابقة لمعايير البحث الحالية.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($users->hasPages())<div class="pager">{{ $users->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
