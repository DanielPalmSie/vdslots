@extends('admin.layout')

@section('content-header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Sportsbook - Clean Events</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('sportsbook.index') }}">Sportsbook</a></li>
                    <li class="breadcrumb-item active">Clean Events</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.sportsbook.partials.topmenu') @include('admin.sportsbook.partials.flash-messages') @include('admin.sportsbook.partials.eventsfilter')
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Event Details</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="table-responsive">
                            <table class="clean-events-datatable table dt-responsive w-100">
                                <thead>
                                    <tr>
                                        <th class="border-top-0">Event ID</th>
                                        <th class="border-top-0">Event Category</th>
                                        <th class="border-top-0">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(isset($event))
                                    <tr>
                                        <td>{{ $event['ext_id'] }}</td>
                                        <td>{{ $event['sport_ext_id'] }}</td>
                                        <td>
                                        <form action="{{ $app['url_generator']->generate('remove-events') }}" method="post">
                                            <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>">
                                            <input name="remove_event" value="{{$event_id}}" class="form-control" placeholder="Event ID" type="hidden">
                                            <button class="btn btn-info">Remove Event</button>
                                        </form>
                                        </td>
                                    </tr>
                                    @else
                                    <tr>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
