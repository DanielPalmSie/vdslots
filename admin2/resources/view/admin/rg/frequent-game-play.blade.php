@extends('admin.layout')

@section('content-header')
    @include('admin.rg.partials.content-header', array("active" => "Frequent game play"))
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.rg.partials.topmenu')
        @include('admin.rg.partials.frequent-game-play-filters')
        @include('admin.partials.datatable')
    </div>
@endsection
