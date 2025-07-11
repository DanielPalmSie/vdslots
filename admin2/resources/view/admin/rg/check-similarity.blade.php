@extends('admin.layout')


@section('content')
    @include('admin.user.partials.topmenu')
    @include('admin.rg.partials.check-similarity-filter')
    @include('admin.partials.datatable')

@endsection
