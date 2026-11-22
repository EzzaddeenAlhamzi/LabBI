@extends('layouts.admin')
@section('heading', 'فحوصاتي')
@section('subheading', 'طلباتي المخبرية ونتائجي المتاحة')
@section('page_actions')
    <a class="btn" href="{{ route('patient.requests.index') }}"><x-icon name="mail" /> طلباتي للطبيب</a>
@endsection
@section('content')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الطلب</th><th>الحالة</th><th>الفحوصات والنتائج</th><th>التاريخ</th></tr></thead>
    <tbody>
    @forelse($orders as $order)
    <tr>
        <td dir="ltr"><b>{{ $order->order_number }}</b></td>
        <td><x-status-badge :status="$order->status" :label="\App\Models\TestOrder::statusLabel($order->status)" /></td>
        <td>
            <ul style="margin:0;padding-inline-start:1rem">
            @foreach($order->items as $item)
            <li>
                {{ $item->test->name_ar }}
                @if($item->result)
                    — <a href="{{ route('patient.results.show', $item->result) }}">
                        @if($item->result->status === 'approved')
                            عرض النتيجة
                        @else
                            حالة: {{ \App\Models\Result::statusLabel($item->result->status) }}
                        @endif
                    </a>
                @else
                    — لم تُدخل بعد
                @endif
            </li>
            @endforeach
            </ul>
        </td>
        <td class="muted-cell" dir="ltr">{{ $order->ordered_at->format('Y-m-d') }}</td>
    </tr>
    @empty
    <tr><td colspan="4"><div class="empty"><div class="empty-icon"><x-icon name="flask-conical" /></div><h3>لا فحوصات بعد</h3><p>لم يتم إنشاء أي طلبات فحص بعد.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($orders->hasPages())<div class="pager">{{ $orders->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
