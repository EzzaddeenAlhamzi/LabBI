@extends('layouts.app')
@section('heading', 'لوحة تحكم الإدارة')
@section('subheading', 'مؤشرات تشغيلية من قاعدة البيانات — بدون قيم ثابتة')
@section('content')
<div class="stats" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr))">
    <div class="stat"><span class="muted">المرضى</span><strong>{{ $cards['patients'] }}</strong></div>
    <div class="stat"><span class="muted">الأطباء</span><strong>{{ $cards['doctors'] }}</strong></div>
    <div class="stat"><span class="muted">دليل الفحوصات</span><strong>{{ $cards['tests_catalog'] }}</strong></div>
    <div class="stat"><span class="muted">طلبات اليوم</span><strong>{{ $cards['orders_today'] }}</strong></div>
    <div class="stat"><span class="muted">عينات بانتظار</span><strong>{{ $cards['samples_awaiting'] }}</strong></div>
    <div class="stat"><span class="muted">قيد التحليل</span><strong>{{ $cards['in_analysis'] }}</strong></div>
    <div class="stat"><span class="muted">بانتظار المراجعة</span><strong>{{ $cards['pending_review'] }}</strong></div>
    <div class="stat"><span class="muted">جاهز / مكتمل</span><strong>{{ $cards['ready_completed'] }}</strong></div>
    <div class="stat"><span class="muted">مؤجل</span><strong>{{ $cards['deferred'] }}</strong></div>
    <div class="stat"><span class="muted">مرفوض</span><strong>{{ $cards['rejected'] }}</strong></div>
    <div class="stat"><span class="muted">حالات حرجة</span><strong>{{ $cards['critical_cases'] }}</strong></div>
</div>
<div class="card">
    <h3>أداء مختصر</h3>
    <p class="muted">متوسط زمن الإنجاز: <strong>{{ $performance['avg_order_tat_hours'] ?? 'غير متاح' }}</strong>
        — الحالات الحرجة في الفترة: <strong>{{ $performance['critical_in_period'] }}</strong></p>
    <div class="actions">
        <a class="btn" href="{{ route('admin.bi.index') }}">لوحة ذكاء الأعمال</a>
        <a class="btn btn-secondary" href="{{ route('admin.reports.operational') }}">التقارير التشغيلية</a>
        <a class="btn btn-secondary" href="{{ route('admin.critical-cases.index') }}">لوحة الحالات الحرجة</a>
    </div>
</div>
@endsection
