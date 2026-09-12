@extends('layouts.app')
@section('title', 'الإشعارات')
@section('heading', 'مركز التنبيهات')
@section('subheading')
غير المقروء: {{ $unread }}
@endsection
@section('content')
<div class="actions" style="margin-bottom:1rem">
    <form method="post" action="{{ route('notifications.read_all') }}">@csrf
        <button class="btn btn-secondary" type="submit">تعليم الكل كمقروء</button>
    </form>
</div>
<div class="card">
    <table>
        <thead>
        <tr>
            <th>النوع</th>
            <th>العنوان</th>
            <th>الأولوية</th>
            <th>الحالة</th>
            <th>التاريخ</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($notifications as $n)
            <tr style="{{ $n->is_read ? '' : 'font-weight:600' }}">
                <td>{{ $n->type }}</td>
                <td>{{ $n->title }}</td>
                <td>
                    @if($n->priority === 'critical')
                        <span class="badge" style="background:#fdecec;color:var(--danger)">حرج</span>
                    @else
                        <span class="badge">عادي</span>
                    @endif
                </td>
                <td>{{ $n->is_read ? 'مقروء' : 'غير مقروء' }}</td>
                <td>{{ $n->created_at }}</td>
                <td><a href="{{ route('notifications.show', $n) }}">عرض</a></td>
            </tr>
        @empty
            <tr><td colspan="6">لا توجد إشعارات.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $notifications->links() }}
</div>
@endsection
