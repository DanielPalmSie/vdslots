@extends('admin.layout')

@section('content-header')
    @include('admin.rg.partials.content-header', array("active" => "Frequent account closing and reopening"))
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.rg.partials.topmenu')
        @include('admin.rg.partials.frequent-account-closing-and-reopening-filters')
        @include('admin.partials.datatable')
    </div>
@endsection
