@extends('layouts.app')
@section('heading', 'إدارة النسخ الاحتياطي')
@section('subheading', 'نسخ قاعدة البيانات والملفات الخاصة — الاستعادة عملية تدميرية')
@section('content')
<div class="card">
    <div class="actions" style="justify-content:space-between;align-items:center">
        <div>
            <p class="muted" style="margin:0">القرص: <strong>{{ config('labbi.backup.disk') }}</strong> · الجدولة: يومياً {{ config('labbi.backup.schedule_time') }}</p>
        </div>
        <form method="post" action="{{ route('admin.backups.store') }}">
            @csrf
            <button class="btn" type="submit">تشغيل نسخة يدوية</button>
        </form>
    </div>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>الوقت</th>
                <th>الحالة</th>
                <th>الحجم</th>
                <th>التخزين</th>
                <th>المشغّل</th>
                <th>المسار</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($backups as $backup)
            <tr>
                <td>{{ $backup->id }}</td>
                <td>{{ $backup->taken_at }}</td>
                <td><span class="badge">{{ $backup->status }}</span></td>
                <td>{{ $backup->humanSize() }}</td>
                <td>{{ $backup->disk }}</td>
                <td>{{ $backup->trigger }} / {{ $backup->creator?->name ?? 'نظام' }}</td>
                <td class="muted" style="font-size:0.8rem">{{ $backup->storage_path }}</td>
                <td><a href="{{ route('admin.backups.show', $backup) }}">إدارة</a></td>
            </tr>
        @empty
            <tr><td colspan="8">لا توجد نسخ بعد.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $backups->links() }}
</div>
@endsection
