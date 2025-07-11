@extends('admin.layout')

@section('content-header')
    @include('admin.rg.partials.content-header', array("active" => "High wagers per bet / spin"))
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.rg.partials.topmenu')
        @include('admin.rg.partials.high-wagers-per-bet-spin-relative-to-deposits-filters')
        @include('admin.partials.datatable')
    </div>
@endsection
