@extends('admin.layout')

@section('content-header')
    <h1>Responsible Gaming</h1>
    <ol class="breadcrumb">
        <li><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-gear"></i>Admin Home</a></li>
        <!--<li><a href="{{ $app['url_generator']->generate('/') }}">Responsible Gaming</a></li>-->
        <li class="active">Dashboard</li>
    </ol>
@endsection

@section('content')

    @include('admin.rg.partials.topmenu')
    @include('admin.rg.partials.extended-game-play-filters')
    @include('admin.partials.datatable')
    
@endsection
