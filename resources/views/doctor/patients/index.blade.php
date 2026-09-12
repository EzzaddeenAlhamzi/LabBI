@extends('layouts.app')
@section('heading', 'مرضاي')
@section('content')
<div class="card"><table>
<thead><tr><th>الرقم الطبي</th><th>الاسم</th><th>الهاتف</th><th></th></tr></thead>
<tbody>@foreach($patients as $patient)
<tr><td>{{ $patient->medical_file_number }}</td><td>{{ $patient->full_name }}</td><td>{{ $patient->phone }}</td>
<td><a href="{{ route('doctor.patients.show', $patient) }}">عرض</a></td></tr>
@endforeach</tbody></table>{{ $patients->links() }}</div>
@endsection