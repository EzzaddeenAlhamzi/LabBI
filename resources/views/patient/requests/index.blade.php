@extends('layouts.admin')
@section('heading', 'طلباتي للطبيب')
@section('subheading', 'تفسير أو إعادة فحص — بدون دردشة مباشرة')
@section('content')
<div class="grid-2" style="align-items:start">
<div class="card">
    <div class="card-title"><x-icon name="mail" /> إرسال طلب جديد</div>
    <form method="post" action="{{ route('patient.requests.store') }}" class="form-grid" style="grid-template-columns:1fr;align-items:end">
        @csrf
        <div class="field">
            <label for="type">النوع <span class="req">*</span></label>
            <select id="type" name="type" required>
                <option value="interpretation">طلب تفسير</option>
                <option value="retest">طلب إعادة فحص</option>
            </select>
        </div>
        <div class="field">
            <label for="result_id">ربط بنتيجة معتمدة (اختياري)</label>
            <select id="result_id" name="result_id">
                <option value="">—</option>
                @foreach($approvedResults as $r)
                    <option value="{{ $r->id }}">{{ $r->test?->name_ar }} — {{ $r->approved_at }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="message">الرسالة <span class="req">*</span></label>
            <textarea id="message" name="message" rows="4" required>{{ old('message') }}</textarea>
        </div>
        <div class="form-actions"><button class="btn" type="submit"><x-icon name="send" /> إرسال</button></div>
    </form>
</div>

<div class="card flush">
    <div class="table-wrap">
    <table class="data">
        <thead><tr><th>النوع</th><th>الرسالة</th><th>الحالة</th><th>الرد</th></tr></thead>
        <tbody>
        @forelse($requests as $req)
            <tr>
                <td>{{ $req->typeLabel() }}</td>
                <td class="muted-cell">{{ $req->message }}</td>
                <td>
                    @php
                        $rtone = match($req->status) {
                            'submitted' => 'warning', 'under_review' => 'info',
                            'resolved' => 'success', default => 'neutral',
                        };
                    @endphp
                    <x-status-badge :tone="$rtone" :label="$req->statusLabel()" />
                </td>
                <td class="muted-cell small">{{ $req->resolution_notes }}</td>
            </tr>
        @empty
            <tr><td colspan="4"><div class="empty"><div class="empty-icon"><x-icon name="mail" /></div><h3>لا توجد طلبات</h3><p>لم ترسل أي طلبات بعد.</p></div></td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    @if($requests->hasPages())<div class="pager">{{ $requests->links('admin.partials.pagination') }}</div>@endif
</div>
</div>
@endsection
