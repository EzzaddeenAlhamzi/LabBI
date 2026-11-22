<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>تسجيل الدخول — LabBI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Cairo:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body style="min-height:100vh;display:grid;place-items:center;padding:2rem;background:linear-gradient(135deg,#142B45 0%,#1B3A5C 55%,#0F766E 130%)">
<div style="width:min(420px,100%)">
    <div style="text-align:center;margin-bottom:1.75rem">
        <div class="brand-logo" style="width:60px;height:60px;margin:0 auto 1rem;border-radius:16px">
            <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:32px;height:32px">
                <path d="M9 3v7.2a3 3 0 0 0 6 0V3"/><path d="M12 13.2V21"/><path d="M7.5 21h9"/>
            </svg>
        </div>
        <h1 style="color:#fff;font-size:1.9rem;margin:0">LabBI</h1>
        <p style="color:#9DB4CC;margin:.35rem 0 0;font-size:.85rem">Laboratory Information &amp; Business Intelligence</p>
    </div>

    <div class="card" style="padding:2rem">
        <h2 style="font-size:1.15rem;margin-bottom:.25rem">تسجيل الدخول</h2>
        <p class="muted small mb-2">أدخل بيانات حسابك للوصول إلى النظام</p>

        @if($errors->any())
            <div class="alert alert-error" role="alert"><x-icon name="alert" /><div><ul style="margin:0;padding-inline-start:1.1rem">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div></div>
        @endif

        <form method="post" action="{{ route('login') }}">
            @csrf
            <div class="field">
                <label for="email">البريد الإلكتروني</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus dir="ltr" style="text-align:start">
            </div>
            <div class="field">
                <label for="password">كلمة المرور</label>
                <input id="password" type="password" name="password" required>
            </div>
            <div class="field" style="margin-bottom:1.2rem">
                <label class="check-line"><input type="checkbox" name="remember" value="1"> تذكرني</label>
            </div>
            <button class="btn btn-lg btn-block" type="submit"><x-icon name="logout" style="transform:scaleX(-1)" /> دخول</button>
        </form>
    </div>
    <p class="muted small" style="text-align:center;margin-top:1.25rem;color:#8FA8C4">نظام إدارة وتداول الفحوصات المخبرية المدعوم بذكاء الأعمال</p>
</div>
</body>
</html>
