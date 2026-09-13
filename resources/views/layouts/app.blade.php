<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LabBI') — نظام إدارة وتداول الفحوصات المخبرية</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Cairo:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f3f6f4;
            --bg-accent: #e5efe9;
            --ink: #14201a;
            --muted: #4d6358;
            --brand: #0f6b4c;
            --brand-dark: #0a4a35;
            --line: #c9d8cf;
            --surface: #ffffff;
            --danger: #9b2c2c;
            --warn: #9a6700;
            --ok: #0f6b4c;
            --shadow: 0 10px 30px rgba(20, 32, 26, 0.06);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "IBM Plex Sans Arabic", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(15, 107, 76, 0.10), transparent 40%),
                linear-gradient(180deg, #f7faf8 0%, var(--bg) 45%, #eef4f0 100%);
            min-height: 100vh;
        }
        a { color: var(--brand); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .shell { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        .sidebar {
            background: linear-gradient(180deg, #0f6b4c 0%, #0a4a35 100%);
            color: #f4faf7;
            padding: 1.5rem 1rem;
        }
        .brand {
            font-family: Cairo, sans-serif;
            font-weight: 800;
            font-size: 1.8rem;
            letter-spacing: -0.02em;
            margin: 0 0 0.25rem;
        }
        .brand-sub { opacity: 0.8; font-size: 0.85rem; margin-bottom: 1.5rem; }
        .nav a {
            display: block;
            color: #e8f5ef;
            padding: 0.65rem 0.85rem;
            border-radius: 0.55rem;
            margin-bottom: 0.25rem;
            text-decoration: none;
        }
        .nav a:hover, .nav a.active { background: rgba(255,255,255,0.12); }
        .nav .section {
            margin: 1rem 0.5rem 0.4rem;
            font-size: 0.75rem;
            opacity: 0.7;
            text-transform: uppercase;
        }
        .main { padding: 1.5rem 2rem 3rem; }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 0.9rem;
            padding: 1.25rem;
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
        }
        h1, h2, h3 { font-family: Cairo, sans-serif; margin-top: 0; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: var(--brand);
            color: white !important;
            border: none;
            border-radius: 0.55rem;
            padding: 0.6rem 1rem;
            cursor: pointer;
            font: inherit;
            text-decoration: none !important;
        }
        .btn:hover { background: var(--brand-dark); }
        .btn-secondary { background: #234636; }
        .btn-danger { background: var(--danger); }
        .btn-ghost {
            background: transparent;
            color: var(--brand) !important;
            border: 1px solid var(--line);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem 0.5rem; border-bottom: 1px solid var(--line); text-align: right; }
        th { color: var(--muted); font-weight: 600; font-size: 0.9rem; }
        label { display: block; margin-bottom: 0.35rem; color: var(--muted); font-size: 0.92rem; }
        input, select, textarea {
            width: 100%;
            padding: 0.65rem 0.75rem;
            border: 1px solid var(--line);
            border-radius: 0.55rem;
            font: inherit;
            background: #fff;
            margin-bottom: 0.9rem;
        }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1rem; }
        .stat {
            background: linear-gradient(160deg, #ffffff, #eef6f1);
            border: 1px solid var(--line);
            border-radius: 0.9rem;
            padding: 1rem;
        }
        .stat strong { display: block; font-size: 1.6rem; font-family: Cairo, sans-serif; }
        .flash {
            background: #e8f6ee;
            border: 1px solid #b7dfc8;
            color: var(--ok);
            padding: 0.85rem 1rem;
            border-radius: 0.7rem;
            margin-bottom: 1rem;
        }
        .errors {
            background: #fdecec;
            border: 1px solid #f0b4b4;
            color: var(--danger);
            padding: 0.85rem 1rem;
            border-radius: 0.7rem;
            margin-bottom: 1rem;
        }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            background: var(--bg-accent);
            color: var(--brand-dark);
            font-size: 0.8rem;
        }
        .auth-wrap {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem;
            background:
                linear-gradient(135deg, rgba(15,107,76,0.92), rgba(10,74,53,0.88)),
                url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.06'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .auth-card {
            width: min(420px, 100%);
            background: rgba(255,255,255,0.97);
            border-radius: 1.1rem;
            padding: 2rem;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        }
        .auth-card .brand { color: var(--brand-dark); }
        .muted { color: var(--muted); }
        .actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        @media (max-width: 960px) {
            .shell { grid-template-columns: 1fr; }
            .sidebar { position: sticky; top: 0; z-index: 5; }
            .stats, .grid-2 { grid-template-columns: 1fr; }
            .main { padding: 1rem; }
        }
    </style>
    @stack('styles')
</head>
<body>
@auth
<div class="shell">
    <aside class="sidebar">
        <p class="brand">LabBI</p>
        <p class="brand-sub">نظام إدارة وتداول الفحوصات المخبرية</p>
        <nav class="nav">
            @php($user = auth()->user()->loadMissing('roles'))
            @if($user->hasRole('admin'))
                <div class="section">الإدارة</div>
                <a href="{{ route('admin.dashboard') }}">لوحة التحكم</a>
                <a href="{{ route('admin.users.index') }}">المستخدمون</a>
                <a href="{{ route('admin.roles.index') }}">الأدوار</a>
                <a href="{{ route('admin.permissions.index') }}">الصلاحيات</a>
                <a href="{{ route('admin.patients.index') }}">المرضى</a>
                <a href="{{ route('admin.doctors.index') }}">الأطباء</a>
                <a href="{{ route('admin.lab-staff.index') }}">فنيو المختبر</a>
                <a href="{{ route('admin.departments.index') }}">الأقسام</a>
                <a href="{{ route('admin.tests.index') }}">دليل الفحوصات</a>
                <a href="{{ route('admin.ranges.index') }}">المديات المرجعية والحرجة</a>
                <a href="{{ route('admin.orders.index') }}">الطلبات</a>
                <a href="{{ route('admin.deliveries.index') }}">التوصيل والتقارير</a>
                <a href="{{ route('admin.bi.index') }}">لوحة ذكاء الأعمال</a>
                <a href="{{ route('admin.reports.operational') }}">التقارير التشغيلية</a>
                <a href="{{ route('admin.critical-cases.index') }}">لوحة الحالات الحرجة</a>
                <a href="{{ route('admin.audit.index') }}">سجل العمليات</a>
                <a href="{{ route('admin.backups.index') }}">النسخ الاحتياطي</a>
                <a href="{{ route('admin.settings.index') }}">إعدادات النظام</a>
            @endif
            @if($user->hasRole('lab_technician') || $user->hasRole('admin'))
                <div class="section">المختبر</div>
                <a href="{{ route('lab.dashboard') }}">لوحة المختبر</a>
                <a href="{{ route('lab.orders.index', ['status'=>'new']) }}">طلبات جديدة</a>
                <a href="{{ route('lab.orders.index') }}">تفاصيل/كل الطلبات</a>
                <a href="{{ route('lab.samples.reception') }}">استلام العينات</a>
                <a href="{{ route('lab.samples.index') }}">العينات</a>
                <a href="{{ route('lab.results.entry.index') }}">إدخال النتائج</a>
                <a href="{{ route('lab.results.review.index') }}">مراجعة النتائج</a>
                <a href="{{ route('lab.critical.index') }}">التنبيهات الحرجة</a>
                <a href="{{ route('lab.attachments.index') }}">المرفقات</a>
                <a href="{{ route('lab.completed.index') }}">الفحوصات المكتملة</a>
                <a href="{{ route('lab.patients.search') }}">بحث المرضى</a>
            @endif
            @if($user->hasRole('doctor'))
                <div class="section">الطبيب</div>
                <a href="{{ route('doctor.dashboard') }}">لوحة الطبيب</a>
                <a href="{{ route('doctor.patients.index') }}">مرضاي</a>
                <a href="{{ route('doctor.orders.create') }}">إنشاء طلب فحص</a>
                <a href="{{ route('doctor.orders.index') }}">طلباتي</a>
                <a href="{{ route('doctor.results.index') }}">النتائج المعتمدة</a>
                <a href="{{ route('doctor.comparison.index') }}">المقارنة التاريخية</a>
                <a href="{{ route('doctor.critical.index') }}">النتائج الحرجة</a>
                <a href="{{ route('doctor.reports.index') }}">التقارير PDF</a>
                <a href="{{ route('doctor.requests.index') }}">طلبات المرضى</a>
                <a href="{{ route('notifications.index') }}">الإشعارات</a>
            @endif
            @if($user->hasRole('patient'))
                <div class="section">المريض</div>
                <a href="{{ route('patient.dashboard') }}">لوحتي</a>
                <a href="{{ route('patient.tests.index') }}">فحوصاتي / نتائجي</a>
                <a href="{{ route('patient.requests.index') }}">طلباتي</a>
                <a href="{{ route('notifications.index') }}">الإشعارات</a>
                <a href="{{ route('patient.profile') }}">ملفي الطبي</a>
            @endif
            <div class="section">الحساب</div>
            <a href="{{ route('notifications.index') }}">مركز التنبيهات</a>
            <a href="{{ route('profile') }}">الملف الشخصي</a>
        </nav>
    </aside>
    <main class="main">
        <div class="topbar">
            <div>
                <h1 style="margin:0;font-size:1.45rem">@yield('heading', 'LabBI')</h1>
                <p class="muted" style="margin:0.25rem 0 0">@yield('subheading')</p>
            </div>
            <div class="actions">
                @php($unreadCount = app(\App\Services\NotificationOrchestrator::class)->unreadCount($user))
                <a class="badge" href="{{ route('notifications.index') }}" style="text-decoration:none">تنبيهات {{ $unreadCount > 0 ? '(' . $unreadCount . ')' : '' }}</a>
                <span class="badge">{{ $user->name }}</span>
                <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost" type="submit">تسجيل الخروج</button></form>
            </div>
        </div>
        @if(session('success'))
            <div class="flash">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="errors">
                <ul style="margin:0;padding-right:1.1rem">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
</div>
@else
    @yield('content')
@endauth
@stack('scripts')
</body>
</html>
