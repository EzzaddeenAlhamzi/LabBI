@extends('layouts.app')
@section('heading', 'تفاصيل النسخة الاحتياطية')
@section('subheading', 'تحذير: الاستعادة تستبدل بيانات قاعدة البيانات الحالية')
@section('content')
<div class="card">
    <p><strong>#</strong> {{ $backup->id }}</p>
    <p><strong>الحالة:</strong> {{ $backup->status }}</p>
    <p><strong>الوقت:</strong> {{ $backup->taken_at }}</p>
    <p><strong>الحجم:</strong> {{ $backup->humanSize() }}</p>
    <p><strong>القرص:</strong> {{ $backup->disk }}</p>
    <p><strong>المسار:</strong> <code>{{ $backup->storage_path }}</code></p>
    <p><strong>المشغّل:</strong> {{ $backup->trigger }} — {{ $backup->creator?->name ?? 'نظام' }}</p>
    @if($backup->error_summary)
        <div class="errors">{{ $backup->error_summary }}</div>
    @endif
    @if($backup->manifest)
        <h3>الملف التعريفي</h3>
        <pre style="white-space:pre-wrap;direction:ltr;text-align:left;font-size:0.85rem">{{ json_encode($backup->manifest, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
    @endif
</div>

@if($backup->isSuccessful() || $backup->status === 'restored')
<div class="card" style="border-color:#f0b4b4">
    <h3 style="color:var(--danger)">استعادة تدميرية</h3>
    <p>ستُستبدل بيانات قاعدة البيانات الحالية بمحتوى هذه النسخة. نفّذ ذلك فقط على بيئة اختبار أو بعد موافقة تشغيلية واضحة.</p>
    <form method="post" action="{{ route('admin.backups.restore', $backup) }}">
        @csrf
        <label>
            <input type="checkbox" name="acknowledge" value="1" style="width:auto;margin-left:0.5rem">
            أقرّ بأن الاستعادة تدميرية وقد تفقد البيانات الحالية غير المضمّنة في النسخة.
        </label>
        <label for="confirm_text">اكتب RESTORE للتأكيد</label>
        <input type="text" name="confirm_text" id="confirm_text" autocomplete="off" placeholder="RESTORE">
        <button class="btn btn-danger" type="submit">تنفيذ الاستعادة</button>
    </form>
</div>
@endif

<a class="btn btn-ghost" href="{{ route('admin.backups.index') }}">رجوع</a>
@endsection
