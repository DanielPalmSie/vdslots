@extends('admin.layout')
@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.betsandwins.partials.filter-all')
@endsection