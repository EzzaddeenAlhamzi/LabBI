@php($isAdmin = auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('lab_technician') || auth()->user()?->hasRole('doctor') || auth()->user()?->hasRole('patient'))
@extends($isAdmin ? 'layouts.admin' : 'layouts.app')
@section('title', 'الملف الشخصي')
@section('heading', 'الملف الشخصي')
@section('content')
@if($isAdmin)
@php($user = auth()->user())
@php($initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn($w) => mb_substr($w, 0, 1))->implode(''))
<div class="card" style="max-width:720px">
    <div class="flex" style="gap:1rem;align-items:center;margin-bottom:1.25rem">
        <span class="avatar" style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--blue),var(--purple));color:#fff;display:grid;place-items:center;font-weight:700;font-size:1.2rem">{{ $initials }}</span>
        <div>
            <h2 style="font-size:1.15rem">{{ $user->name }}</h2>
            <span class="muted small">{{ $user->roles->pluck('name_ar')->join('، ') }}</span>
        </div>
    </div>
    <div class="dl">
        <div class="item"><div class="k">الاسم</div><div class="v">{{ $user->name }}</div></div>
        <div class="item"><div class="k">البريد الإلكتروني</div><div class="v" dir="ltr" style="text-align:start">{{ $user->email }}</div></div>
        <div class="item"><div class="k">الهاتف</div><div class="v" dir="ltr" style="text-align:start">{{ $user->phone ?: '—' }}</div></div>
        <div class="item"><div class="k">آخر دخول</div><div class="v">{{ optional($user->last_login_at)->format('Y-m-d H:i') ?: '—' }}</div></div>
        <div class="item"><div class="k">الأدوار</div><div class="v">{{ $user->roles->pluck('name_ar')->join('، ') ?: '—' }}</div></div>
    </div>
</div>
@else
<div class="card">
    <p><strong>الاسم:</strong> {{ auth()->user()->name }}</p>
    <p><strong>البريد:</strong> {{ auth()->user()->email }}</p>
    <p><strong>الهاتف:</strong> {{ auth()->user()->phone ?: '—' }}</p>
    <p><strong>آخر دخول:</strong> {{ optional(auth()->user()->last_login_at)->format('Y-m-d H:i') ?: '—' }}</p>
    <p><strong>الأدوار:</strong> {{ auth()->user()->roles->pluck('name_ar')->join('، ') }}</p>
</div>
@endif
@endsection
