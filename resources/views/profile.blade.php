@extends('layouts.app')
@section('heading', 'الملف الشخصي')
@section('content')
<div class="card">
    <p><strong>الاسم:</strong> {{ auth()->user()->name }}</p>
    <p><strong>البريد:</strong> {{ auth()->user()->email }}</p>
    <p><strong>الهاتف:</strong> {{ auth()->user()->phone ?: '—' }}</p>
    <p><strong>آخر دخول:</strong> {{ optional(auth()->user()->last_login_at)->format('Y-m-d H:i') ?: '—' }}</p>
    <p><strong>الأدوار:</strong> {{ auth()->user()->roles->pluck('name_ar')->join('، ') }}</p>
</div>
@endsection