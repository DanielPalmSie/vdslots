@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.messaging.partials.topmenu')

        <div class="card">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link active">All offline campaigns</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.offline-campaigns.new') }}"><i class="fa fa-plus-square"></i> Add offline campaign</a></li>

                </ul>
                <div class="tab-content p-3">
                    <div class="tab-pane active">
                        @include('admin.messaging.offline-campaigns.partials.list')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
