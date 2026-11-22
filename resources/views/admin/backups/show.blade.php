@extends('layouts.admin')
@section('title', 'تفاصيل النسخة الاحتياطية')
@section('heading', 'نسخة احتياطية #'.$backup->id)
@section('subheading', 'تحذير: الاستعادة تستبدل بيانات قاعدة البيانات الحالية')
@section('page_actions')
    <a class="btn btn-secondary" href="{{ route('admin.backups.index') }}"><x-icon name="chevron-right" /> رجوع للنسخ</a>
@endsection
@section('content')
<div class="card">
    <div class="card-title"><x-icon name="database" /> بيانات النسخة</div>
    <div class="dl">
        <div class="item"><div class="k">الرقم</div><div class="v">#{{ $backup->id }}</div></div>
        <div class="item"><div class="k">الحالة</div><div class="v"><x-status-badge :status="$backup->status" :label="$backup->status" /></div></div>
        <div class="item"><div class="k">وقت النسخ</div><div class="v" dir="ltr" style="text-align:start">{{ $backup->taken_at }}</div></div>
        <div class="item"><div class="k">الحجم</div><div class="v">{{ $backup->humanSize() }}</div></div>
        <div class="item"><div class="k">القرص</div><div class="v">{{ $backup->disk }}</div></div>
        <div class="item"><div class="k">المشغّل</div><div class="v">{{ $backup->trigger }} — {{ $backup->creator?->name ?? 'نظام' }}</div></div>
        <div class="item" style="grid-column:1/-1"><div class="k">المسار</div><div class="v"><code>{{ $backup->storage_path }}</code></div></div>
    </div>
    @if($backup->error_summary)
        <div class="alert alert-error mt-2"><x-icon name="alert" /><div>{{ $backup->error_summary }}</div></div>
    @endif
    @if($backup->manifest)
        <div class="card-title mt-2"><x-icon name="file-text" /> الملف التعريفي</div>
        <pre class="json">{{ json_encode($backup->manifest, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
    @endif
</div>

@if($backup->isSuccessful() || $backup->status === 'restored')
<div class="card" style="border-color:#FECACA;background:linear-gradient(180deg,#FFF,#FFFBFB)">
    <div class="card-title" style="color:var(--danger)"><x-icon name="alert-octagon" /> استعادة تدميرية</div>
    <div class="alert alert-warning mb-2"><x-icon name="alert" /><div>ستُستبدل بيانات قاعدة البيانات الحالية بمحتوى هذه النسخة. نفّذ ذلك فقط على بيئة اختبار أو بعد موافقة تشغيلية واضحة.</div></div>
    <form method="post" action="{{ route('admin.backups.restore', $backup) }}">
        @csrf
        <div class="field">
            <label class="check-line"><input type="checkbox" name="acknowledge" value="1"> أقرّ بأن الاستعادة تدميرية وقد تفقد البيانات الحالية غير المضمّنة في النسخة.</label>
        </div>
        <div class="field">
            <label for="confirm_text">اكتب <code>RESTORE</code> للتأكيد</label>
            <input type="text" name="confirm_text" id="confirm_text" autocomplete="off" placeholder="RESTORE" dir="ltr" style="text-align:start;max-width:280px">
        </div>
        <button class="btn btn-danger" type="submit"><x-icon name="alert" /> تنفيذ الاستعادة</button>
    </form>
</div>
@endif
@endsection
