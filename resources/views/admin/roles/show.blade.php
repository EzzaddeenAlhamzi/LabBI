@extends('layouts.app')
@section('heading', 'دور: '.$role->name_ar)
@section('content')
<div class="card">
<p>الرمز: <code>{{ $role->code }}</code></p>
<ul>
@foreach($role->permissions as $permission)
<li><code>{{ $permission->code }}</code></li>
@endforeach
</ul>
</div>
@endsection