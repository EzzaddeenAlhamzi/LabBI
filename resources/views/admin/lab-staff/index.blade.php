@extends('layouts.app')
@section('heading', 'فنيو المختبر')
@section('content')
<div class="actions" style="margin-bottom:1rem"><a class="btn" href="{{ route('admin.lab-staff.create') }}">إضافة فني</a></div>
<div class="card"><table>
<thead><tr><th>الاسم</th><th>المسمى</th><th>القسم</th></tr></thead>
<tbody>@foreach($staff as $row)
<tr><td>{{ $row->user->name }}</td><td>{{ $row->job_title ?: '—' }}</td><td>{{ $row->department?->name_ar ?: '—' }}</td></tr>
@endforeach</tbody></table>{{ $staff->links() }}</div>
@endsection