@extends('layouts.app')
@section('heading', 'الأقسام')
@section('content')
<div class="grid-2">
<div class="card">
<table><thead><tr><th>الرمز</th><th>الاسم</th><th>الحالة</th></tr></thead>
<tbody>@foreach($departments as $department)
<tr><td>{{ $department->code }}</td><td>{{ $department->name_ar }}</td><td>{{ $department->is_active ? 'نشط' : 'موقوف' }}</td></tr>
@endforeach</tbody></table>{{ $departments->links() }}
</div>
<div class="card">
<h3>إضافة قسم</h3>
<form method="post" action="{{ route('admin.departments.store') }}">@csrf
<label>الرمز</label><input name="code" required>
<label>الاسم بالعربية</label><input name="name_ar" required>
<button class="btn" type="submit">إضافة</button>
</form>
</div>
</div>
@endsection