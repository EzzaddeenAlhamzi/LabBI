@extends('layouts.app')
@section('heading', 'طلبات المرضى')
@section('content')
<div class="card">
<table>
<thead><tr><th>المريض</th><th>النوع</th><th>الرسالة</th><th>الحالة</th><th>إجراء</th></tr></thead>
<tbody>
@forelse($requests as $req)
<tr>
    <td>{{ $req->patient?->full_name }}</td>
    <td>{{ $req->typeLabel() }}</td>
    <td>{{ $req->message }}</td>
    <td>{{ $req->statusLabel() }}</td>
    <td>
        @if($req->status === 'submitted')
            <form method="post" action="{{ route('doctor.requests.under_review', $req) }}" style="display:inline">@csrf
                <button class="btn btn-ghost" type="submit">بدء المراجعة</button>
            </form>
        @endif
        @if(in_array($req->status, ['submitted','under_review'], true))
            <form method="post" action="{{ route('doctor.requests.resolve', $req) }}" style="margin-top:0.5rem">@csrf
                <input name="resolution_notes" placeholder="ملاحظات الحل" required>
                <button class="btn" type="submit">حل</button>
            </form>
            <form method="post" action="{{ route('doctor.requests.reject', $req) }}" style="margin-top:0.5rem">@csrf
                <input name="resolution_notes" placeholder="سبب الرفض" required>
                <button class="btn btn-danger" type="submit">رفض</button>
            </form>
        @else
            <span class="muted">{{ $req->resolution_notes }}</span>
        @endif
    </td>
</tr>
@empty
<tr><td colspan="5">لا توجد طلبات.</td></tr>
@endforelse
</tbody>
</table>
{{ $requests->links() }}
</div>
@endsection
