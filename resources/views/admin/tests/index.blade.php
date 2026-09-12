@extends('layouts.app')
@section('heading', 'دليل الفحوصات')
@section('content')
<div class="actions" style="margin-bottom:1rem"><a class="btn" href="{{ route('admin.tests.create') }}">فحص جديد</a></div>
<div class="card"><table>
<thead><tr><th>الرمز</th><th>الاسم</th><th>الوحدة</th><th>القسم</th><th></th></tr></thead>
<tbody>@foreach($tests as $test)
<tr><td>{{ $test->code }}</td><td>{{ $test->name_ar }}</td><td>{{ $test->unit }}</td><td>{{ $test->department?->name_ar }}</td>
<td><a href="{{ route('admin.tests.show', $test) }}">تفاصيل</a></td></tr>
@endforeach</tbody></table>{{ $tests->links() }}</div>
@endsection