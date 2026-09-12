@extends('layouts.app')
@section('heading', 'المديات المرجعية والحرجة')
@section('subheading', 'بيانات المديات فقط — محرك القيم الحرجة غير مضمّن في المرحلة 1')
@section('content')
<div class="grid-2">
<div class="card">
<table><thead><tr><th>الفحص</th><th>طبيعي</th><th>حرج</th></tr></thead>
<tbody>@foreach($ranges as $range)
<tr>
<td>{{ $range->test->code }}</td>
<td>{{ $range->normal_low }} – {{ $range->normal_high }}</td>
<td>{{ $range->critical_low }} – {{ $range->critical_high }}</td>
</tr>
@endforeach</tbody></table>{{ $ranges->links() }}
</div>
<div class="card">
<h3>إضافة مدى</h3>
<form method="post" action="{{ route('admin.ranges.store') }}">@csrf
<label>الفحص</label>
<select name="test_id" required>@foreach($tests as $test)<option value="{{ $test->id }}">{{ $test->code }} — {{ $test->name_ar }}</option>@endforeach</select>
<label>طبيعي من</label><input name="normal_low" type="number" step="any">
<label>طبيعي إلى</label><input name="normal_high" type="number" step="any">
<label>حرج من</label><input name="critical_low" type="number" step="any">
<label>حرج إلى</label><input name="critical_high" type="number" step="any">
<button class="btn" type="submit">حفظ</button>
</form>
</div>
</div>
@endsection