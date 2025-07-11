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
                    <li class="breadcrumb-item active" aria-current="page">Anonymous Methods List</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin.fraud.partials.topmenu')
    @include('admin.fraud.partials.anonymousmethodsfilter')
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Anonymous Methods List</h3>
            @include('admin.fraud.partials.download-button')
        </div><!-- /.card-header -->
        <div class="card-body">
            <table class="fraud-section-datatable table table-striped table-bordered dt-responsive w-100 border-collapse">
                <thead>
                <tr>
                    <th>User ID</th>
                    <th>Verified</th>
                    <th>Country</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Currency</th>
                    <th>Internal Transaction Id</th>
                    <th>Time</th>
                    <th>Date</th>
                    <th>External Transaction Id</th>
                    <th>Recorded IP</th>
                </tr>
                </thead>
                <tbody>
                @foreach($anonymous_methods as $element)
                    <tr>
                        <td class="align-middle">
                            <a target="_blank" href="{{ \App\Helpers\URLHelper::generateUserProfileLink($app, htmlspecialchars($element->user_id)) }}">{{ htmlspecialchars($element->user_id) }}</a>
                        </td>
                        <td>{{ $element->user->settings->first()->value == 1 ? 'Yes' : 'No' }}</td>
                        <td>{{ $element->user->country }}</td>
                        <td>{{ $element->status }}</td>
                        <td>{{ $element->amount / 100 }}</td>
                        <td>{{ $element->currency }}</td>
                        <td>{{ $element->id }}</td>
                        <td>{{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',$element->timestamp)->toTimeString() }}</td>
                        <td>{{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',$element->timestamp)->toDateString() }}</td>
                        <td>{{ $element->ext_id }}</td>
                        <td>{{ $element->ip_num }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div><!-- /.card-body -->
    </div>

@endsection

@include('admin.fraud.partials.fraud-footer')
