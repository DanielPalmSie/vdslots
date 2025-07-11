@extends('admin.layout')

@section('content-header')
    <h1>Fraud Section</h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-gear"></i>Admin Home</a></li>
        <li><a href="#">Fraud</a></li>
        <li class="active">MinFraud</li>
    </ol>
@endsection

@section('content')

    @include('admin.fraud.partials.topmenu')
    @include('admin.rg.partials.min-fraud-filters')
    @include('admin.partials.datatable')
    
@endsection
