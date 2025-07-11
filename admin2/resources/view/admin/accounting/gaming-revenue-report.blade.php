@extends('admin.layout')

@section('content')
    @include('admin.accounting.partials.topmenu')
    @include('admin.accounting.partials.gaming-revenue-and-open-bets-report-filter')
    @include('admin.accounting.partials.gaming-revenue-report-table')
@endsection
