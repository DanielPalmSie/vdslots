@extends('admin.layout')

@section('content')
<div class="container-fluid">
    @include('admin.messaging.partials.topmenu')

    <div class="card">
        <div class="nav-tabs-custom">
            @includeIf("admin.messaging.partials.submenu")
            <div class="tab-content p-3">
                <div class="tab-pane active">
                    @include('admin.messaging.campaigns.partials.past-list')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


