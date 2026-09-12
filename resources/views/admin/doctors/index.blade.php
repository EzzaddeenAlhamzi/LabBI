@extends('layouts.app')
@section('heading', 'الأطباء')
@section('content')
<div class="actions" style="margin-bottom:1rem"><a class="btn" href="{{ route('admin.doctors.create') }}">إضافة طبيب</a></div>
<div class="card"><table>
<thead><tr><th>الاسم</th><th>التخصص</th><th>الترخيص</th><th>القسم</th></tr></thead>
<tbody>@foreach($doctors as $doctor)
<tr><td>{{ $doctor->user->name }}</td><td>{{ $doctor->specialty ?: '—' }}</td><td>{{ $doctor->license_number ?: '—' }}</td><td>{{ $doctor->department?->name_ar ?: '—' }}</td></tr>
@endforeach</tbody></table>{{ $doctors->links() }}</div>
@endsection