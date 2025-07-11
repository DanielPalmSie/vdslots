@extends('admin.layout')

@section('content-header')
    @include('admin.rg.partials.content-header', array("active" => "Change of deposit pattern"))
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.rg.partials.topmenu')
        @include('admin.rg.partials.change-of-deposit-pattern')
        @include('admin.partials.datatable')
    </div>
@endsection
