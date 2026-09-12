@extends('layouts.app')
@section('heading', 'المرفقات')
@section('subheading', 'PDF وصور فقط — بدون مولّد تقرير PDF نهائي (المرحلة 3)')
@section('content')
<div class="card">
<h3>رفع مرفق</h3>
<form method="post" action="{{ route('lab.attachments.store') }}" enctype="multipart/form-data">
@csrf
<label>الملف (PDF / صورة، بحد أقصى 5MB)</label>
<input type="file" name="file" accept=".pdf,image/*" required>
<label>ربط بطلب (اختياري)</label>
<select name="test_order_id">
<option value="">—</option>
@foreach($orders as $order)
<option value="{{ $order->id }}">{{ $order->order_number }} — {{ $order->patient->full_name }}</option>
@endforeach
</select>
<label>معرف النتيجة (اختياري)</label>
<input type="number" name="result_id" placeholder="result id">
<button class="btn" type="submit">رفع</button>
</form>
</div>
<div class="card">
<table>
<thead><tr><th>الاسم</th><th>النوع</th><th>المريض</th><th>الطلب</th><th>رفع بواسطة</th><th>الوقت</th><th></th></tr></thead>
<tbody>
@forelse($attachments as $attachment)
<tr>
<td>{{ $attachment->original_name }}</td>
<td>{{ $attachment->mime_type }}</td>
<td>{{ $attachment->patient->full_name }}</td>
<td>{{ $attachment->order?->order_number ?? '—' }}</td>
<td>{{ $attachment->uploader->name }}</td>
<td>{{ $attachment->uploaded_at }}</td>
<td><a href="{{ route('lab.attachments.download', $attachment) }}">تنزيل</a></td>
</tr>
@empty
<tr><td colspan="7">لا مرفقات بعد.</td></tr>
@endforelse
</tbody>
</table>
{{ $attachments->links() }}
</div>
@endsection
