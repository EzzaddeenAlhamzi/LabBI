@extends('layouts.admin')
@section('heading', 'ملفي الطبي')
@section('subheading', 'البيانات الشخصية والطبية الأساسية')
@section('content')
<div class="card">
    <div class="card-title"><x-icon name="user" /> البيانات الشخصية</div>
    <div class="dl">
        <div class="item"><div class="k">الاسم</div><div class="v">{{ $patient->full_name }}</div></div>
        <div class="item"><div class="k">الرقم الطبي</div><div class="v" dir="ltr">{{ $patient->medical_file_number }}</div></div>
        <div class="item"><div class="k">الهاتف</div><div class="v" dir="ltr">{{ $patient->phone ?: '—' }}</div></div>
        <div class="item"><div class="k">تاريخ الميلاد</div><div class="v" dir="ltr">{{ optional($patient->date_of_birth)->format('Y-m-d') ?: '—' }}</div></div>
        <div class="item"><div class="k">الطبيب المعالج</div><div class="v">{{ $patient->primaryDoctor?->user?->name ?? '—' }}</div></div>
    </div>
</div>
@endsection
