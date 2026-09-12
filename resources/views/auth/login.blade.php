@extends('layouts.app')
@section('title', 'تسجيل الدخول')
@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <p class="brand">LabBI</p>
        <p class="muted">نظام إدارة وتداول الفحوصات المخبرية المدعوم بذكاء الأعمال</p>
        <h2>تسجيل الدخول</h2>
        @if($errors->any())
            <div class="errors"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        <form method="post" action="{{ route('login') }}">
            @csrf
            <label>البريد الإلكتروني</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            <label>كلمة المرور</label>
            <input type="password" name="password" required>
            <label style="display:flex;align-items:center;gap:0.5rem">
                <input type="checkbox" name="remember" value="1" style="width:auto;margin:0"> تذكرني
            </label>
            <button class="btn" type="submit" style="width:100%;justify-content:center">دخول</button>
        </form>
        <p class="muted" style="margin-top:1rem;font-size:0.85rem">حسابات تجريبية: admin@labbi.local / doctor@labbi.local / lab@labbi.local / patient@labbi.local — كلمة المرور: password</p>
    </div>
</div>
@endsection