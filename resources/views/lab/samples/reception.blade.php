@extends('layouts.app')
@section('heading', 'استلام ومطابقة العينة')
@section('content')
<div class="card">
<form method="post" action="{{ route('lab.samples.receive') }}">@csrf
<label>الطلب</label>
<select name="test_order_id" id="order" required>
@foreach($orders as $order)
<option value="{{ $order->id }}" @selected(request('order')==$order->id)>
{{ $order->order_number }} — {{ $order->patient->full_name }} ({{ $order->patient->medical_file_number }})
</option>
@endforeach
</select>
<label>عنصر الطلب / الفحص (للمطابقة)</label>
<select name="test_order_item_id">
<option value="">أول فحص في الطلب</option>
@foreach($orders as $order)
@foreach($order->items as $item)
<option value="{{ $item->id }}" data-order="{{ $order->id }}">{{ $order->order_number }} / {{ $item->test->code }}</option>
@endforeach
@endforeach
</select>
<label>رقم العينة (اختياري)</label>
<input name="sample_number" placeholder="يُولَّد تلقائياً إن تُرك فارغاً">
<button class="btn" type="submit">تأكيد الاستلام والمطابقة</button>
</form>
</div>
@endsection