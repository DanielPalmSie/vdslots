@extends('admin.layout')

@section('content-header')
    @include('admin.rg.partials.content-header', array("active" => "Multiple changes to rg limits"))
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.rg.partials.topmenu')
        @include('admin.rg.partials.multiple-changes-rg-limits-filters')
        @include('admin.partials.datatable')
    </div>
@endsection
