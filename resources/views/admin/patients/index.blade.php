@extends('layouts.app')
@section('heading', 'المرضى')
@section('content')
<div class="card">
<form method="get" class="actions">
<input name="q" value="{{ request('q') }}" placeholder="الرقم الطبي / الاسم / الهاتف" style="max-width:320px;margin:0">
<button class="btn" type="submit">بحث</button>
<a class="btn" href="{{ route('admin.patients.create') }}">مريض جديد</a>
</form>
</div>
<div class="card">
<table>
<thead><tr><th>الرقم الطبي</th><th>الاسم</th><th>الهاتف</th><th>الطبيب</th><th></th></tr></thead>
<tbody>
@foreach($patients as $patient)
<tr>
<td>{{ $patient->medical_file_number }}</td>
<td>{{ $patient->full_name }}</td>
<td>{{ $patient->phone ?: '—' }}</td>
<td>{{ $patient->primaryDoctor?->user?->name ?: '—' }}</td>
<td><a href="{{ route('admin.patients.show', $patient) }}">تفاصيل</a></td>
</tr>
@endforeach
</tbody>
</table>
{{ $patients->links() }}
</div>
@endsection