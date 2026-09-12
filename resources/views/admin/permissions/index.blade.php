@extends('layouts.app')
@section('heading', 'الصلاحيات')
@section('content')
<div class="card">
<table>
<thead><tr><th>الرمز</th><th>الأدوار الممنوحة</th></tr></thead>
<tbody>
@foreach($permissions as $permission)
<tr>
<td><code>{{ $permission->code }}</code></td>
<td>{{ $permission->roles->pluck('name_ar')->join('، ') ?: '—' }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endsection