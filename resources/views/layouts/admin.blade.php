<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LabBI') — Laboratory Information &amp; Business Intelligence</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%230D9488'/%3E%3Cpath d='M10 7v10.5a3 3 0 0 0 6 0V7' stroke='white' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3Cpath d='M16 7v10.5a3 3 0 0 0 6 0V7' stroke='white' stroke-width='2' fill='none' stroke-linecap='round' opacity='.7'/%3E%3Cpath d='M8 25h16' stroke='white' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Cairo:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('styles')
</head>
<body>
@auth
@php($user = auth()->user()->loadMissing('roles'))
@php($initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn($w) => mb_substr($w, 0, 1))->implode(''))
<div class="app" id="appShell">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <span class="brand-logo">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 3v7.2a3 3 0 0 0 6 0V3"/><path d="M12 13.2V21"/><path d="M7.5 21h9"/>
                </svg>
            </span>
            <span>
                <span class="brand-name">LabBI</span>
                <span class="brand-sub">Laboratory Information &amp; BI</span>
            </span>
        </div>

        <nav class="nav" aria-label="التنقل الرئيسي">
            @if($user->hasRole('admin'))
            <div class="nav-group">
                <p class="nav-group-title">الرئيسية</p>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <x-icon name="dashboard" /><span>لوحة التحكم</span>
                </a>
            </div>
            <div class="nav-group">
                <p class="nav-group-title">العمليات المخبرية</p>
                <a href="{{ route('admin.patients.index') }}" class="{{ request()->routeIs('admin.patients.*') ? 'active' : '' }}">
                    <x-icon name="patients" /><span>المرضى</span>
                </a>
                <a href="{{ route('admin.doctors.index') }}" class="{{ request()->routeIs('admin.doctors.*') ? 'active' : '' }}">
                    <x-icon name="doctor" /><span>الأطباء</span>
                </a>
                <a href="{{ route('admin.lab-staff.index') }}" class="{{ request()->routeIs('admin.lab-staff.*') ? 'active' : '' }}">
                    <x-icon name="microscope" /><span>فنيو المختبر</span>
                </a>
                <a href="{{ route('admin.departments.index') }}" class="{{ request()->routeIs('admin.departments.*') ? 'active' : '' }}">
                    <x-icon name="building" /><span>الأقسام</span>
                </a>
                <a href="{{ route('admin.tests.index') }}" class="{{ request()->routeIs('admin.tests.*') ? 'active' : '' }}">
                    <x-icon name="test-tubes" /><span>دليل الفحوصات</span>
                </a>
                <a href="{{ route('admin.ranges.index') }}" class="{{ request()->routeIs('admin.ranges.*') ? 'active' : '' }}">
                    <x-icon name="ruler" /><span>المديات المرجعية والحرجة</span>
                </a>
                <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <x-icon name="clipboard" /><span>الطلبات</span>
                </a>
                <a href="{{ route('admin.deliveries.index') }}" class="{{ request()->routeIs('admin.deliveries.*') ? 'active' : '' }}">
                    <x-icon name="truck" /><span>التوصيل والتقارير</span>
                </a>
            </div>
            <div class="nav-group">
                <p class="nav-group-title">التحليلات والتقارير</p>
                <a href="{{ route('admin.bi.index') }}" class="{{ request()->routeIs('admin.bi.index') ? 'active' : '' }}">
                    <x-icon name="chart" /><span>لوحة ذكاء الأعمال</span>
                </a>
                <a href="{{ route('admin.reports.operational') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <x-icon name="file-text" /><span>التقارير التشغيلية</span>
                </a>
                <a href="{{ route('admin.critical-cases.index') }}" class="{{ request()->routeIs('admin.critical-cases.*') ? 'active' : '' }}">
                    <x-icon name="alert-octagon" /><span>لوحة الحالات الحرجة</span>
                </a>
            </div>
            <div class="nav-group">
                <p class="nav-group-title">الحوكمة والإدارة</p>
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <x-icon name="users" /><span>المستخدمون</span>
                </a>
                <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                    <x-icon name="shield" /><span>الأدوار</span>
                </a>
                <a href="{{ route('admin.permissions.index') }}" class="{{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                    <x-icon name="key" /><span>الصلاحيات</span>
                </a>
                <a href="{{ route('admin.audit.index') }}" class="{{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                    <x-icon name="scroll" /><span>سجل العمليات</span>
                </a>
                <a href="{{ route('admin.backups.index') }}" class="{{ request()->routeIs('admin.backups.*') ? 'active' : '' }}">
                    <x-icon name="database" /><span>النسخ الاحتياطي</span>
                </a>
                <a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <x-icon name="settings" /><span>إعدادات النظام</span>
                </a>
            </div>
            @endif
            @if($user->hasRole('lab_technician') || $user->hasRole('admin'))
            <div class="nav-group">
                <p class="nav-group-title">المختبر</p>
                <a href="{{ route('lab.dashboard') }}" class="{{ request()->routeIs('lab.dashboard') ? 'active' : '' }}">
                    <x-icon name="flask" /><span>لوحة المختبر</span>
                </a>
                <a href="{{ route('lab.orders.index', ['status'=>'new']) }}" class="{{ request()->routeIs('lab.orders.*') && request('status') === 'new' ? 'active' : '' }}">
                    <x-icon name="clipboard" /><span>طلبات جديدة</span>
                </a>
                <a href="{{ route('lab.orders.index') }}" class="{{ request()->routeIs('lab.orders.*') && request('status') !== 'new' ? 'active' : '' }}">
                    <x-icon name="clipboard" /><span>تفاصيل/كل الطلبات</span>
                </a>
                <a href="{{ route('lab.samples.reception') }}" class="{{ request()->routeIs('lab.samples.reception') ? 'active' : '' }}">
                    <x-icon name="test-tube" /><span>استلام العينات</span>
                </a>
                <a href="{{ route('lab.samples.index') }}" class="{{ request()->routeIs('lab.samples.index') ? 'active' : '' }}">
                    <x-icon name="test-tube" /><span>العينات</span>
                </a>
                <a href="{{ route('lab.results.entry.index') }}" class="{{ request()->routeIs('lab.results.entry*') ? 'active' : '' }}">
                    <x-icon name="flask-conical" /><span>إدخال النتائج</span>
                </a>
                <a href="{{ route('lab.results.review.index') }}" class="{{ request()->routeIs('lab.results.review*') ? 'active' : '' }}">
                    <x-icon name="check-circle" /><span>مراجعة النتائج</span>
                </a>
                <a href="{{ route('lab.critical.index') }}" class="{{ request()->routeIs('lab.critical.*') ? 'active' : '' }}">
                    <x-icon name="alert" /><span>التنبيهات الحرجة</span>
                </a>
                <a href="{{ route('lab.attachments.index') }}" class="{{ request()->routeIs('lab.attachments.*') ? 'active' : '' }}">
                    <x-icon name="paperclip" /><span>المرفقات</span>
                </a>
                <a href="{{ route('lab.completed.index') }}" class="{{ request()->routeIs('lab.completed.*') ? 'active' : '' }}">
                    <x-icon name="check-circle" /><span>الفحوصات المكتملة</span>
                </a>
                <a href="{{ route('lab.patients.search') }}" class="{{ request()->routeIs('lab.patients.*') ? 'active' : '' }}">
                    <x-icon name="search" /><span>بحث المرضى</span>
                </a>
            </div>
            @endif
            @if($user->hasRole('doctor'))
            <div class="nav-group">
                <p class="nav-group-title">الطبيب</p>
                <a href="{{ route('doctor.dashboard') }}" class="{{ request()->routeIs('doctor.dashboard') ? 'active' : '' }}">
                    <x-icon name="doctor" /><span>لوحة الطبيب</span>
                </a>
                <a href="{{ route('doctor.patients.index') }}" class="{{ request()->routeIs('doctor.patients.*') ? 'active' : '' }}">
                    <x-icon name="patients" /><span>مرضاي</span>
                </a>
                <a href="{{ route('doctor.orders.create') }}" class="{{ request()->routeIs('doctor.orders.create') ? 'active' : '' }}">
                    <x-icon name="plus" /><span>إنشاء طلب فحص</span>
                </a>
                <a href="{{ route('doctor.orders.index') }}" class="{{ request()->routeIs('doctor.orders.index') || request()->routeIs('doctor.orders.show') ? 'active' : '' }}">
                    <x-icon name="clipboard" /><span>طلباتي</span>
                </a>
                <a href="{{ route('doctor.results.index') }}" class="{{ request()->routeIs('doctor.results.*') ? 'active' : '' }}">
                    <x-icon name="flask-conical" /><span>النتائج المعتمدة</span>
                </a>
                <a href="{{ route('doctor.comparison.index') }}" class="{{ request()->routeIs('doctor.comparison.*') ? 'active' : '' }}">
                    <x-icon name="chart" /><span>المقارنة التاريخية</span>
                </a>
                <a href="{{ route('doctor.critical.index') }}" class="{{ request()->routeIs('doctor.critical.*') ? 'active' : '' }}">
                    <x-icon name="alert-octagon" /><span>النتائج الحرجة</span>
                </a>
                <a href="{{ route('doctor.reports.index') }}" class="{{ request()->routeIs('doctor.reports.*') ? 'active' : '' }}">
                    <x-icon name="file-text" /><span>التقارير PDF</span>
                </a>
                <a href="{{ route('doctor.requests.index') }}" class="{{ request()->routeIs('doctor.requests.*') ? 'active' : '' }}">
                    <x-icon name="mail" /><span>طلبات المرضى</span>
                </a>
            </div>
            @endif
            @if($user->hasRole('patient'))
            <div class="nav-group">
                <p class="nav-group-title">بوابتي</p>
                <a href="{{ route('patient.dashboard') }}" class="{{ request()->routeIs('patient.dashboard') ? 'active' : '' }}">
                    <x-icon name="home" /><span>لوحتي</span>
                </a>
                <a href="{{ route('patient.tests.index') }}" class="{{ request()->routeIs('patient.tests.*') || request()->routeIs('patient.results.*') ? 'active' : '' }}">
                    <x-icon name="flask-conical" /><span>فحوصاتي ونتائجي</span>
                </a>
                <a href="{{ route('patient.requests.index') }}" class="{{ request()->routeIs('patient.requests.*') ? 'active' : '' }}">
                    <x-icon name="mail" /><span>طلباتي للطبيب</span>
                </a>
                <a href="{{ route('patient.profile') }}" class="{{ request()->routeIs('patient.profile') || request()->routeIs('patient.records.*') ? 'active' : '' }}">
                    <x-icon name="user" /><span>ملفي الطبي</span>
                </a>
            </div>
            @endif
            <div class="nav-group">
                <p class="nav-group-title">الحساب</p>
                <a href="{{ route('notifications.index') }}" class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    <x-icon name="bell" /><span>مركز التنبيهات</span>
                </a>
                <a href="{{ route('profile') }}" class="{{ request()->routeIs('profile') ? 'active' : '' }}">
                    <x-icon name="user" /><span>الملف الشخصي</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <span class="avatar">{{ $initials }}</span>
            <span class="who"><b>{{ $user->name }}</b><small>{{ $user->roles->pluck('name_ar')->join('، ') }}</small></span>
        </div>
    </aside>

    <div class="main-col">
        <header class="topbar">
            <button type="button" class="icon-btn sidebar-toggle" id="sidebarToggle" aria-label="القائمة">
                <x-icon name="menu" />
            </button>
            <div class="spacer"></div>
            @php($unreadCount = app(\App\Services\NotificationOrchestrator::class)->unreadCount($user))
            <a href="{{ route('notifications.index') }}" class="icon-btn" aria-label="التنبيهات" title="التنبيهات">
                <x-icon name="bell" />
                @if($unreadCount > 0)<span class="dot">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>@endif
            </a>
            <div class="dropdown" id="userDropdown">
                <button type="button" class="icon-btn" id="userMenuBtn" aria-label="قائمة الحساب" aria-haspopup="true" aria-expanded="false" style="width:auto;padding:0 10px;display:flex;align-items:center;gap:.55rem">
                    <span class="avatar" style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--blue),var(--purple));color:#fff;display:grid;place-items:center;font-weight:700;font-size:.8rem">{{ $initials }}</span>
                    <x-icon name="chevron-down" />
                </button>
                <div class="dropdown-menu">
                    <div style="padding:.5rem .7rem .6rem;border-bottom:1px solid var(--line);margin-bottom:.3rem">
                        <b style="display:block;font-size:.88rem">{{ $user->name }}</b>
                        <small class="muted">{{ $user->email }}</small>
                    </div>
                    <a href="{{ route('profile') }}"><x-icon name="user" /> الملف الشخصي</a>
                    <a href="{{ route('notifications.index') }}"><x-icon name="bell" /> مركز التنبيهات</a>
                    <div class="sep"></div>
                    <form method="post" action="{{ route('logout') }}">@csrf
                        <button type="submit"><x-icon name="logout" /> تسجيل الخروج</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="content">
            <div class="page-head">
                <div>
                    <h1>@yield('heading', 'LabBI')</h1>
                    @hasSection('subheading')<p class="sub">@yield('subheading')</p>@endif
                </div>
                @hasSection('page_actions')<div class="page-actions">@yield('page_actions')</div>@endif
            </div>

            @if(session('success'))
                <div class="alert alert-success"><x-icon name="check-circle" /><div>{{ session('success') }}</div></div>
            @endif
            @if(session('error'))
                <div class="alert alert-error"><x-icon name="alert" /><div>{{ session('error') }}</div></div>
            @endif
            @if($errors->any())
                <div class="alert alert-error" role="alert">
                    <x-icon name="alert" />
                    <div>
                        <strong>يرجى تصحيح الأخطاء التالية:</strong>
                        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@else
    @yield('content')
@endauth
<script src="{{ asset('js/admin.js') }}" defer></script>
<script src="{{ asset('js/admin-charts.js') }}" defer></script>
@stack('scripts')
</body>
</html>
