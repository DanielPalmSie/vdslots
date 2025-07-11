@extends('admin.layout')

@section('content-header')
    @include('admin.rg.partials.content-header', array("active" => "Interactions"))
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.rg.partials.topmenu')
        @include('admin.rg.partials.interactions-filters')
        @include('admin.partials.datatable')
    </div>
@endsection
