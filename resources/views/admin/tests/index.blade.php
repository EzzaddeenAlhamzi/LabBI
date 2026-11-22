@extends('layouts.admin')
@section('title', 'دليل الفحوصات')
@section('heading', 'دليل الفحوصات المخبرية')
@section('subheading', 'كتالوج الفحوصات المتاحة والواحدات وأقسامها')
@section('page_actions')
    <a class="btn" href="{{ route('admin.tests.create') }}"><x-icon name="plus" /> فحص جديد</a>
@endsection
@section('content')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الرمز</th><th>اسم الفحص</th><th>الاسم الإنجليزي</th><th>الوحدة</th><th>القسم</th><th>الحالة</th><th class="text-end">إجراءات</th></tr></thead>
    <tbody>
    @forelse($tests as $test)
    <tr>
        <td><code>{{ $test->code }}</code></td>
        <td><b>{{ $test->name_ar }}</b></td>
        <td class="muted-cell" dir="ltr" style="text-align:start">{{ $test->name_en ?: '—' }}</td>
        <td><span class="badge badge-neutral">{{ $test->unit ?: '—' }}</span></td>
        <td>{{ $test->department?->name_ar ?: '—' }}</td>
        <td><x-status-badge :status="$test->is_active ? 'active' : 'inactive'" :label="$test->is_active ? 'نشط' : 'موقوف'" /></td>
        <td><div class="row-actions"><a class="btn btn-ghost btn-sm" href="{{ route('admin.tests.show', $test) }}"><x-icon name="eye" /> تفاصيل</a></div></td>
    </tr>
    @empty
    <tr><td colspan="7"><div class="empty"><div class="empty-icon"><x-icon name="test-tubes" /></div><h3>لا توجد فحوصات</h3></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($tests->hasPages())<div class="pager">{{ $tests->links('admin.partials.pagination') }}</div>@endif
</div>
@endsection
