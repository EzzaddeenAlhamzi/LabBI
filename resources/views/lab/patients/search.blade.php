@extends('layouts.admin')
@section('heading', 'بحث المرضى')
@section('subheading', 'بحث بالرقم الطبي أو الاسم أو الهاتف')
@section('content')
<div class="card">
    <form method="get" class="form-grid" style="grid-template-columns: 1fr auto; align-items:end">
        <div class="field" style="margin-bottom:0">
            <label for="q">بحث</label>
            <input id="q" name="q" value="{{ $search ?? '' }}" placeholder="الرقم الطبي / الاسم / الهاتف">
        </div>
        <div class="flex" style="margin-bottom:0">
            <button class="btn" type="submit"><x-icon name="search" /> بحث</button>
        </div>
    </form>
</div>

@if(($search ?? '') !== '')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
    <thead><tr><th>الرقم الطبي</th><th>الاسم</th><th>الهاتف</th></tr></thead>
    <tbody>
    @forelse($patients as $patient)
    <tr>
        <td dir="ltr">{{ $patient->medical_file_number }}</td>
        <td>{{ $patient->full_name }}</td>
        <td class="muted-cell" dir="ltr">{{ $patient->phone }}</td>
    </tr>
    @empty
    <tr><td colspan="3"><div class="empty"><div class="empty-icon"><x-icon name="users" /></div><h3>لا نتائج</h3><p>لا يوجد مرضى مطابقون لبيانات البحث.</p></div></td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>
@endif
@endsection
