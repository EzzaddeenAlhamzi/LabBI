@extends('layouts.app')
@section('heading', 'العينات')
@section('content')
<div class="card"><table>
<thead><tr><th>رقم العينة</th><th>الطلب</th><th>المريض</th><th>الحالة</th><th>الاستلام</th></tr></thead>
<tbody>@foreach($samples as $sample)
<tr>
<td>{{ $sample->sample_number }}</td>
<td>{{ $sample->order->order_number }}</td>
<td>{{ $sample->patient->full_name }}</td>
<td>{{ \App\Models\Sample::statusLabel($sample->status) }}</td>
<td>{{ optional($sample->received_at)->format('Y-m-d H:i') }}</td>
</tr>
@endforeach</tbody></table>{{ $samples->links() }}</div>
@endsection