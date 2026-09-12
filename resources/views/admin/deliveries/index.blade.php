@extends('layouts.app')
@section('heading', 'التوصيل والتقارير')
@section('subheading', 'رؤية الإدارة لحالات الإرسال وأرشيف PDF')
@section('content')
<div class="card">
    <h3>تتبع التوصيل</h3>
    <table>
        <thead><tr><th>القناة</th><th>المستخدم</th><th>المستلم</th><th>الحالة</th><th>محاولات</th><th>مرجع</th><th>خطأ</th><th>وقت</th></tr></thead>
        <tbody>
        @forelse($deliveries as $d)
            <tr>
                <td>{{ $d->channel }}</td>
                <td>{{ $d->notification?->user?->name }}</td>
                <td>{{ $d->recipient }}</td>
                <td>{{ $d->status }}</td>
                <td>{{ $d->attempts }}</td>
                <td>{{ $d->provider_ref }}</td>
                <td>{{ $d->error_summary }}</td>
                <td>{{ $d->sent_at ?? $d->failed_at ?? $d->created_at }}</td>
            </tr>
        @empty
            <tr><td colspan="8">لا سجلات توصيل بعد.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $deliveries->links() }}
</div>
<div class="card">
    <h3>أرشيف التقارير المُنشأة</h3>
    <table>
        <thead><tr><th>المريض</th><th>الفحص</th><th>الطلب</th><th>المفتاح</th><th>تاريخ</th><th></th></tr></thead>
        <tbody>
        @forelse($reports as $report)
            <tr>
                <td>{{ $report->patient?->full_name }}</td>
                <td>{{ $report->result?->test?->name_ar }}</td>
                <td>{{ $report->order?->order_number }}</td>
                <td class="muted" style="font-size:0.8rem">{{ $report->storage_key }}</td>
                <td>{{ $report->generated_at }}</td>
                <td><a href="{{ route('reports.download', $report) }}">تنزيل</a></td>
            </tr>
        @empty
            <tr><td colspan="6">لا تقارير.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $reports->links() }}
</div>
@endsection
