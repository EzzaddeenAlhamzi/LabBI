@extends('layouts.app')
@section('heading', 'بحث المرضى')
@section('content')
<div class="card">
<form method="get"><input name="q" value="{{ $search ?? '' }}" placeholder="الرقم الطبي / الاسم / الهاتف"><button class="btn" type="submit">بحث</button></form>
</div>
@if(($search ?? '') !== '')
<div class="card"><table>
<thead><tr><th>الرقم الطبي</th><th>الاسم</th><th>الهاتف</th></tr></thead>
<tbody>@forelse($patients as $patient)
<tr><td>{{ $patient->medical_file_number }}</td><td>{{ $patient->full_name }}</td><td>{{ $patient->phone }}</td></tr>
@empty<tr><td colspan="3">لا نتائج</td></tr>@endforelse
</tbody></table></div>
@endif
@endsection