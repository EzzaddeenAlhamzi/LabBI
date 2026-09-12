@extends('layouts.app')
@section('heading', 'لوحة تحكم الإدارة')
@section('subheading', 'نظرة عامة على النظام')
@section('content')
<div class="stats">
    <div class="stat"><span class="muted">المستخدمون</span><strong>{{ $stats['users'] }}</strong></div>
    <div class="stat"><span class="muted">المرضى</span><strong>{{ $stats['patients'] }}</strong></div>
    <div class="stat"><span class="muted">الطلبات</span><strong>{{ $stats['orders'] }}</strong></div>
    <div class="stat"><span class="muted">العينات</span><strong>{{ $stats['samples'] }}</strong></div>
</div>
<div class="card">
    <h3>اختصارات</h3>
    <div class="actions">
        <a class="btn" href="{{ route('admin.users.create') }}">مستخدم جديد</a>
        <a class="btn" href="{{ route('admin.patients.create') }}">مريض جديد</a>
        <a class="btn" href="{{ route('admin.orders.create') }}">طلب فحص</a>
        <a class="btn btn-secondary" href="{{ route('admin.ranges.index') }}">المديات</a>
    </div>
</div>
@endsection