@extends('layouts.admin')
@section('title', 'إعدادات النظام')
@section('heading', 'إعدادات النظام')
@section('subheading', 'قيم تشغيلية غير سرية — الأسرار من البيئة فقط ولا تُعرض هنا')
@section('page_actions')
    <form method="post" action="{{ route('admin.settings.touch') }}">@csrf
        <button class="btn btn-secondary" type="submit"><x-icon name="check" /> تسجيل مراجعة الإعدادات</button>
    </form>
@endsection
@section('content')
<div class="card flush">
    <div class="table-wrap">
    <table class="data">
        <tbody>
        @foreach($settings as $key => $value)
            <tr>
                <td style="width:40%"><code>{{ $key }}</code></td>
                <td>
                    @if(is_bool($value))
                        <x-status-badge :status="$value ? 'active' : 'inactive'" :label="$value ? 'true' : 'false'" />
                    @else
                        {{ $value === null || $value === '' ? '—' : $value }}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
<div class="alert alert-info"><x-icon name="info" /><div>لا تُعرض كلمات المرور أو APP_KEY أو مفاتيح السحابة أو أسرار SMTP هنا أبداً.</div></div>
@endsection
