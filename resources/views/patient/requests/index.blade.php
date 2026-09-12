@extends('layouts.app')
@section('heading', 'طلباتي للطبيب')
@section('subheading', 'تفسير أو إعادة فحص — بدون دردشة مباشرة')
@section('content')
<div class="card">
    <h3>إرسال طلب جديد</h3>
    <form method="post" action="{{ route('patient.requests.store') }}">@csrf
        <label>النوع</label>
        <select name="type" required>
            <option value="interpretation">طلب تفسير</option>
            <option value="retest">طلب إعادة فحص</option>
        </select>
        <label>ربط بنتيجة معتمدة (اختياري)</label>
        <select name="result_id">
            <option value="">—</option>
            @foreach($approvedResults as $r)
                <option value="{{ $r->id }}">{{ $r->test?->name_ar }} — {{ $r->approved_at }}</option>
            @endforeach
        </select>
        <label>الرسالة</label>
        <textarea name="message" rows="4" required>{{ old('message') }}</textarea>
        <button class="btn" type="submit">إرسال</button>
    </form>
</div>
<div class="card">
    <h3>سجل الطلبات</h3>
    <table>
        <thead><tr><th>النوع</th><th>الرسالة</th><th>الحالة</th><th>الرد</th></tr></thead>
        <tbody>
        @forelse($requests as $req)
            <tr>
                <td>{{ $req->typeLabel() }}</td>
                <td>{{ $req->message }}</td>
                <td>{{ $req->statusLabel() }}</td>
                <td>{{ $req->resolution_notes }}</td>
            </tr>
        @empty
            <tr><td colspan="4">لا توجد طلبات.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $requests->links() }}
</div>
@endsection
