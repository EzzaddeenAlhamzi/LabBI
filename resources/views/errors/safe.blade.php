<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'خطأ' }} — LabBI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Cairo:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body style="display:grid;place-items:center;padding:2rem">
<div class="card" style="max-width:460px;width:100%;text-align:center;padding:2.5rem 2rem">
    <div class="brand-logo" style="width:56px;height:56px;margin:0 auto 1.1rem;border-radius:14px">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:30px;height:30px">
            <path d="M9 3v7.2a3 3 0 0 0 6 0V3"/><path d="M12 13.2V21"/><path d="M7.5 21h9"/>
        </svg>
    </div>
    <p class="muted small" style="letter-spacing:.08em;text-transform:uppercase">LabBI · {{ $code ?? '' }}</p>
    <h1 style="font-size:1.5rem;margin:.4rem 0 .6rem">{{ $title ?? 'حدث خطأ' }}</h1>
    <p class="muted" style="margin-bottom:1.4rem">{{ $message ?? 'تعذر إكمال الطلب. حاول لاحقاً أو راجع المسؤول.' }}</p>
    <a class="btn" href="{{ url('/') }}">العودة للرئيسية</a>
</div>
</body>
</html>
