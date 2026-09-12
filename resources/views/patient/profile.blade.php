@extends('layouts.app')
@section('heading', 'ملفي الطبي')
@section('content')
<div class="card">
<p>الاسم: {{ $patient->full_name }}</p>
<p>الرقم الطبي: {{ $patient->medical_file_number }}</p>
<p>الهاتف: {{ $patient->phone ?: '—' }}</p>
<p>تاريخ الميلاد: {{ optional($patient->date_of_birth)->format('Y-m-d') ?: '—' }}</p>
</div>
@endsection