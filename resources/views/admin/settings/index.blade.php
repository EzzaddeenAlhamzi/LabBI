@extends('layouts.app')
@section('heading', 'إعدادات النظام')
@section('subheading', 'قيم تشغيلية غير سرية — الأسرار من البيئة فقط ولا تُعرض هنا')
@section('content')
<div class="card">
    <table>
        <tbody>
        @foreach($settings as $key => $value)
            <tr>
                <th style="width:40%">{{ $key }}</th>
                <td>
                    @if(is_bool($value))
                        {{ $value ? 'true' : 'false' }}
                    @else
                        {{ $value === null || $value === '' ? '—' : $value }}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p class="muted">لا تُعرض كلمات المرور أو APP_KEY أو مفاتيح السحابة أو أسرار SMTP هنا أبداً.</p>
    <form method="post" action="{{ route('admin.settings.touch') }}">
        @csrf
        <button class="btn" type="submit">تسجيل مراجعة الإعدادات</button>
    </form>
</div>
@endsection
