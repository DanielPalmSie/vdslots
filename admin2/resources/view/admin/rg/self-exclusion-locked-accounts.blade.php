@extends('admin.layout')

@section('content-header')
    @include('admin.rg.partials.content-header', array("active" => "Responsible Gaming Limits Report"))
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.rg.partials.topmenu')
        @include('admin.rg.partials.self-exclusion-locked-accounts-filters')
        @include('admin.partials.datatable')
    </div>
@endsection
