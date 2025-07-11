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
                    <li class="breadcrumb-item active" aria-current="page">Multi Methods Transaction List</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin.fraud.partials.topmenu')
    @include('admin.fraud.partials.multimethodsfilter')
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Multi Methods Transaction List</h3>
            @include('admin.fraud.partials.download-button')
        </div><!-- /.card-header -->
        <div class="card-body">
            <table class="fraud-section-datatable table table-striped table-bordered dt-responsive w-100 border-collapse">
                <thead>
                <tr>
                    <th>Transaction Type</th>
                    <th>Payment Method</th>
                    <th>Card Hash</th>
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
                @foreach($multi_methods as $element)
                    <tr>
                        <td>{{ $element->type }}</td>
                        <td>{{ $element->payment_method }}</td>
                        <td>{{ $element->card_hash }}</td>
                        <td>
                            <a target="_blank" href="{{ \App\Helpers\URLHelper::generateUserProfileLink($app, $element->user_id) }}">{{ htmlspecialchars($element->user_id) }}</a>
                        </td>
                        <td>{{ $element->verified == 1 ? 'Yes' : 'No' }}</td>
                        <td>{{ $element->country }}</td>
                        <td>{{ $element->status }}</td>
                        <td>{{ $element->amount / 100 }}</td>
                        <td>{{ $element->currency }}</td>
                        <td>{{ $element->internal_id }}</td>
                        <td>{{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',$element->transaction_time)->toTimeString() }}</td>
                        <td>{{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',$element->transaction_time)->toDateString() }}</td>
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
