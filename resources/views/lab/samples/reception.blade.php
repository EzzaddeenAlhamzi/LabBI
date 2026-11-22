@extends('layouts.admin')
@section('heading', 'استلام ومطابقة العينة')
@section('subheading', 'تأكيد استلام العينة ومطابقتها مع الطلب وفحصها')
@section('page_actions')
    <a class="btn btn-ghost" href="{{ route('lab.samples.index') }}"><x-icon name="chevron-right" /> العينات</a>
@endsection
@section('content')
<div class="card">
    <div class="card-title"><x-icon name="test-tube" /> بيانات الاستلام</div>
    <p class="card-sub">اختر الطلب ثم عنصر الفحص للمطابقة. رقم العينة يُولَّد تلقائياً إن تُرك فارغاً.</p>
    <form method="post" action="{{ route('lab.samples.receive') }}" class="form-grid" style="grid-template-columns: 1fr 1fr 1fr; align-items:end">
        @csrf
        <div class="field">
            <label for="order">الطلب <span class="req">*</span></label>
            <select id="order" name="test_order_id" required>
                @foreach($orders as $order)
                <option value="{{ $order->id }}" @selected(request('order')==$order->id)>
                    {{ $order->order_number }} — {{ $order->patient->full_name }} ({{ $order->patient->medical_file_number }})
                </option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="item">عنصر الطلب / الفحص (للمطابقة)</label>
            <select id="item" name="test_order_item_id">
                <option value="">أول فحص في الطلب</option>
                @foreach($orders as $order)
                    @foreach($order->items as $item)
                        <option value="{{ $item->id }}" data-order="{{ $order->id }}">{{ $order->order_number }} / {{ $item->test->code }}</option>
                    @endforeach
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="sample_number">رقم العينة (اختياري)</label>
            <input id="sample_number" name="sample_number" placeholder="يُولَّد تلقائياً إن تُرك فارغاً" maxlength="50">
        </div>
        <div class="form-actions" style="grid-column:1/-1">
            <button class="btn" type="submit"><x-icon name="check" /> تأكيد الاستلام والمطابقة</button>
        </div>
    </form>
</div>
@endsection
