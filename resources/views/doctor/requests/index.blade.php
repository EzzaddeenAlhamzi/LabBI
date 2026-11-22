@extends('layouts.admin')
@section('heading', 'طلبات المرضى')
@section('subheading', 'طلبات وإجراءات المرضى المرتبطة بحسابك')
@section('content')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>المريض</th><th>النوع</th><th>الرسالة</th><th>الحالة</th><th>إجراء</th></tr></thead>
    <tbody>
    @forelse($requests as $req)
    <tr>
        <td>{{ $req->patient?->full_name }}</td>
        <td>{{ $req->typeLabel() }}</td>
        <td class="muted-cell">{{ $req->message }}</td>
        <td><x-status-badge :status="$req->status === 'submitted' ? 'pending' : ($req->status === 'under_review' ? 'in_analysis' : ($req->status === 'resolved' ? 'completed' : 'rejected'))" :label="$req->statusLabel()" /></td>
        <td>
            @if($req->status === 'submitted')
                <form method="post" action="{{ route('doctor.requests.under_review', $req) }}" style="display:inline">@csrf
                    <button class="btn btn-ghost btn-sm" type="submit"><x-icon name="eye" /> بدء المراجعة</button>
                </form>
            @endif
            @if(in_array($req->status, ['submitted','under_review'], true))
                <form method="post" action="{{ route('doctor.requests.resolve', $req) }}" style="margin-top:.5rem">@csrf
                    <input name="resolution_notes" placeholder="ملاحظات الحل" required>
                    <button class="btn btn-sm" type="submit"><x-icon name="check" /> حل</button>
                </form>
                <form method="post" action="{{ route('doctor.requests.reject', $req) }}" style="margin-top:.5rem">@csrf
                    <input name="resolution_notes" placeholder="سبب الرفض" required>
                    <button class="btn btn-danger btn-sm" type="submit"><x-icon name="x" /> رفض</button>
                </form>
            @else
                <span class="muted-cell small">{{ $req->resolution_notes }}</span>
            @endif
        </td>
    </tr>
    @empty
    <tr><td colspan="5"><div class="empty"><div class="empty-icon"><x-icon name="mail" /></div><h3>لا توجد طلبات</h3><p>لا توجد طلبات مرضى حالياً.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($requests->hasPages())<div class="pager">{{ $requests->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
