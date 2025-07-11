@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    Fraud Section
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Fraud</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Similar Account</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')

    @include('admin.fraud.partials.topmenu')
    @include('admin.rg.partials.similar-account-filters')
    @include('admin.partials.datatable')

@endsection
