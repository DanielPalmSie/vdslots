@extends('admin.layout')

@section('content')
    @include('admin.accounting.partials.topmenu')
    @include('admin.accounting.partials.liability-filter')
    @include('admin.accounting.partials.liability-table')
@endsection
