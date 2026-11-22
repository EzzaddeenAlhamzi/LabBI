@php($isAdmin = auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('lab_technician') || auth()->user()?->hasRole('doctor'))
@extends($isAdmin ? 'layouts.admin' : 'layouts.app')
@section('title', 'الإشعارات')
@section('heading', 'مركز التنبيهات')
@section('subheading')
غير المقروء: {{ $unread }}
@endsection
@section('content')
@if($isAdmin)
@section('page_actions')
    <form method="post" action="{{ route('notifications.read_all') }}">@csrf
        <button class="btn btn-secondary" type="submit"><x-icon name="check" /> تعليم الكل كمقروء</button>
    </form>
@endsection
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
        <thead>
        <tr>
            <th>النوع</th>
            <th>العنوان</th>
            <th>الأولوية</th>
            <th>الحالة</th>
            <th>التاريخ</th>
            <th class="text-end">إجراءات</th>
        </tr>
        </thead>
        <tbody>
        @forelse($notifications as $n)
            <tr style="{{ $n->is_read ? '' : 'background:#FFFBEB' }}">
                <td><span class="badge badge-neutral">{{ $n->type }}</span></td>
                <td><b>{{ $n->title }}</b></td>
                <td>
                    @if($n->priority === 'critical')
                        <x-status-badge status="open" label="حرج" />
                    @else
                        <x-status-badge tone="neutral" label="عادي" />
                    @endif
                </td>
                <td><x-status-badge :status="$n->is_read ? 'completed' : 'pending'" :label="$n->is_read ? 'مقروء' : 'غير مقروء'" /></td>
                <td class="muted-cell" dir="ltr" style="text-align:start">{{ $n->created_at }}</td>
                <td><div class="row-actions"><a class="btn btn-ghost btn-sm" href="{{ route('notifications.show', $n) }}"><x-icon name="eye" /> عرض</a></div></td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><div class="empty-icon"><x-icon name="bell" /></div><h3>لا توجد إشعارات</h3><p>لا توجد إشعارات في صندوق الوارد.</p></div></td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    @if($notifications->hasPages())<div class="pager">{{ $notifications->links('admin.partials.pagination') }}</div>@endif
</div>
@else
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
    {{ $notifications->links('admin.partials.pagination') }}
</div>
@endif
@endsection
