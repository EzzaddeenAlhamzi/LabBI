@extends('layouts.admin')
@section('heading', 'المرفقات')
@section('subheading', 'PDF وصور فقط — بدون مولّد تقرير PDF نهائي (المرحلة 3)')
@section('content')
<div class="grid-2" style="align-items:start">
<div class="card">
    <div class="card-title"><x-icon name="upload" /> رفع مرفق</div>
    <form method="post" action="{{ route('lab.attachments.store') }}" enctype="multipart/form-data" class="form-grid" style="grid-template-columns:1fr;align-items:end">
        @csrf
        <div class="field">
            <label for="file">الملف (PDF / صورة، بحد أقصى 5MB) <span class="req">*</span></label>
            <input type="file" id="file" name="file" accept=".pdf,image/*" required>
        </div>
        <div class="field">
            <label for="test_order_id">ربط بطلب (اختياري)</label>
            <select id="test_order_id" name="test_order_id">
                <option value="">—</option>
                @foreach($orders as $order)
                <option value="{{ $order->id }}">{{ $order->order_number }} — {{ $order->patient->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="result_id">معرف النتيجة (اختياري)</label>
            <input type="number" id="result_id" name="result_id" placeholder="result id">
        </div>
        <div class="form-actions"><button class="btn" type="submit"><x-icon name="upload" /> رفع</button></div>
    </form>
</div>

<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الاسم</th><th>النوع</th><th>المريض</th><th>الطلب</th><th>رفع بواسطة</th><th>الوقت</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($attachments as $attachment)
    <tr>
        <td><b>{{ $attachment->original_name }}</b></td>
        <td class="muted-cell small" dir="ltr">{{ $attachment->mime_type }}</td>
        <td>{{ $attachment->patient->full_name }}</td>
        <td dir="ltr">{{ $attachment->order?->order_number ?? '—' }}</td>
        <td class="muted-cell">{{ $attachment->uploader->name }}</td>
        <td class="muted-cell" dir="ltr">{{ $attachment->uploaded_at }}</td>
        <td class="text-end"><div class="row-actions" style="justify-content:flex-end"><a class="btn btn-ghost btn-sm" href="{{ route('lab.attachments.download', $attachment) }}"><x-icon name="download" /> تنزيل</a></div></td>
    </tr>
    @empty
    <tr><td colspan="7"><div class="empty"><div class="empty-icon"><x-icon name="paperclip" /></div><h3>لا مرفقات</h3><p>لا توجد مرفقات مرفوعة بعد.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($attachments->hasPages())<div class="pager">{{ $attachments->links('admin.partials.pagination') }}</div>@endif
</div>
</div>
@endsection
