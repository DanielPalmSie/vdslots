@extends('admin.layout')
@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    <div class="card card-primary border border-primary">
        <div class="card-header">
            <h3 class="card-title">
                Risk Score
            </h3>
        </div>
        <div class="card-body">
            <div class='row'><div class='col-12 text-center'><em>Fraud Score</em></div></div>
            <div class="row justify-content-center">
                <div class="col-12 text-center">
                    <div class="row justify-content-center align-items-center mb-1">
                        @foreach ($rating_score as $rating)
                            <div class="mr-1">
                                <div class="risk-indicator text-xs-custom {{ $rating['active'] ? 'risk-indicator-active' : '' }} d-flex align-self-center align-items-center justify-content-center"
                                     style="background-color: {{ \App\Helpers\GrsHelper::getGlobalScoreColor($app, $rating['title']) }};">
                                    <span>{{ $rating['title'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            </div>

            <div class="table-responsive">
                <table id="obj-datatable" class="table table-bordered dt-responsive table-striped" cellspacing="0"
                       width="100%">
                    <thead>
                    <tr>
                        <th></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($columns as $k => $v)
                        <tr>
                            <td style="font-weight: bold">{{ $v }}</td>
                            <td>{{ $page['data'][0]->{$k} }} </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
