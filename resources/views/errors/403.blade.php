@extends('layouts.app')
@section('heading', 'غير مصرح')
@section('content')
<div class="card"><p>{{ $exception->getMessage() ?: 'ليس لديك صلاحية للوصول إلى هذه الصفحة.' }}</p>
<a class="btn" href="{{ url()->previous() }}">رجوع</a></div>
@endsection