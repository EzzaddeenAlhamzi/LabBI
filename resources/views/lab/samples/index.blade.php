@extends('layouts.admin')
@section('heading', 'العينات')
@section('subheading', 'سجل العينات المستلمة والمرفوضة')
@section('page_actions')
    <a class="btn" href="{{ route('lab.samples.reception') }}"><x-icon name="test-tube" /> استلام عينة</a>
@endsection
@section('content')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>رقم العينة</th><th>الطلب</th><th>المريض</th><th>الفحص</th><th>الحالة</th><th>الاستلام</th></tr></thead>
    <tbody>
    @forelse($samples as $sample)
    <tr>
        <td dir="ltr"><b>{{ $sample->sample_number }}</b></td>
        <td dir="ltr">{{ $sample->order->order_number }}</td>
        <td>{{ $sample->patient->full_name }}</td>
        <td class="muted-cell">{{ $sample->test?->name_ar ?? '—' }}</td>
        <td><x-status-badge :status="$sample->status" :label="\App\Models\Sample::statusLabel($sample->status)" /></td>
        <td class="muted-cell" dir="ltr">{{ $sample->received_at?->format('Y-m-d H:i') ?? '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="6"><div class="empty"><div class="empty-icon"><x-icon name="test-tube" /></div><h3>لا توجد عينات</h3><p>لم يتم تسجيل أي عينات بعد.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($samples->hasPages())<div class="pager">{{ $samples->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
