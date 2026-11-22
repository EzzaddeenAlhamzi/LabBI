@extends('layouts.admin')
@section('title', 'الأقسام')
@section('heading', 'الأقسام المخبرية')
@section('subheading', 'أقسام المختبر وفحوصاتها')
@section('content')
<div class="grid-2" style="align-items:start">
<div class="card flush">
    <div class="card-title" style="padding:1rem 1rem 0"><x-icon name="building" /> الأقسام ({{ $departments->total() }})</div>
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الرمز</th><th>الاسم</th><th>الحالة</th></tr></thead>
    <tbody>
    @forelse($departments as $department)
    <tr>
        <td><code>{{ $department->code }}</code></td>
        <td><b>{{ $department->name_ar }}</b></td>
        <td><x-status-badge :status="$department->is_active ? 'active' : 'inactive'" :label="$department->is_active ? 'نشط' : 'موقوف'" /></td>
    </tr>
    @empty
    <tr><td colspan="3"><div class="empty"><h3>لا توجد أقسام</h3></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
    @if($departments->hasPages())<div class="pager">{{ $departments->links('admin.partials.pagination') }}</div>@endif
</div>
<div class="card">
    <div class="form-section-title"><x-icon name="plus" /> إضافة قسم جديد</div>
    <form method="post" action="{{ route('admin.departments.store') }}">@csrf
        <div class="field">
            <label for="code">رمز القسم <span class="req">*</span></label>
            <input id="code" name="code" required dir="ltr" style="text-align:start">
        </div>
        <div class="field">
            <label for="name_ar">اسم القسم بالعربية <span class="req">*</span></label>
            <input id="name_ar" name="name_ar" required>
        </div>
        <button class="btn" type="submit"><x-icon name="check" /> إضافة القسم</button>
    </form>
</div>
</div>
@endsection
