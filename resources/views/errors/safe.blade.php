<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'خطأ' }} — LabBI</title>
    <style>
        body { font-family: "IBM Plex Sans Arabic", Tahoma, sans-serif; background:#f3f6f4; color:#14201a; margin:0; min-height:100vh; display:grid; place-items:center; padding:2rem; }
        .box { background:#fff; border:1px solid #c9d8cf; border-radius:1rem; padding:2rem; max-width:480px; width:100%; box-shadow:0 10px 30px rgba(20,32,26,.06); }
        h1 { font-family: Cairo, sans-serif; margin-top:0; color:#0f6b4c; }
        a { color:#0f6b4c; }
        .code { color:#4d6358; font-size:.9rem; }
    </style>
</head>
<body>
<div class="box">
    <p class="code">LabBI · {{ $code ?? '' }}</p>
    <h1>{{ $title ?? 'حدث خطأ' }}</h1>
    <p>{{ $message ?? 'تعذر إكمال الطلب. حاول لاحقاً أو راجع المسؤول.' }}</p>
    <p><a href="{{ url('/') }}">العودة للرئيسية</a></p>
</div>
</body>
</html>
