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
                    <li class="breadcrumb-item active" aria-current="page">Non Turned-over Withdrawals</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin.fraud.partials.topmenu')
    @include('admin.fraud.partials.nonturnedfilter')
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Non Turned-over Withdrawals</h3>
            @include('admin.fraud.partials.download-button')
        </div><!-- /.card-header -->
        <div class="card-body">
            <table class="fraud-section-datatable table table-striped table-bordered dt-responsive w-100 border-collapse">
                <thead>
                <tr>
                    <th>User ID</th>
                    <th>Verified</th>
                    <th>Country</th>
                    <th>Currency</th>
                    <th>Withd. 1 Amount</th>
                    <th>Withd. 1 Method</th>
                    <th>Withd. 1 Timestamp</th>
                    <th>Withd. 2 Amount</th>
                    <th>Withd. 2 Method</th>
                    <th>Withd. 2 Timestamp</th>
                    <th>Number of Deposits</th>
                    <th>Deposited Sum</th>
                    <th>Wagered Sum</th>
                    <th>Non Turned-over Amount</th>
                    <th>Non Turned-over Percentage</th>
                </tr>
                </thead>
                <tbody>
                @foreach($non_turned_over as $element)
                    <tr>
                        <td class="align-middle">
                            <a target="_blank" href="{{ \App\Helpers\URLHelper::generateUserProfileLink($app, $element->user_id) }}">{{ htmlspecialchars($element->user_id)}}</a>
                        </td>
                        <td>{{ $element->verified == 1 ? 'Yes' : 'No' }}</td>
                        <td>{{ htmlspecialchars($element->country) }}</td>
                        <td>{{ $element->currency }}</td>
                        <td>{{ $element->w_amount1/100 }}</td>
                        <td>{{ $element->w_method1 }}</td>
                        <td>{{ $element->w_stamp1 }}</td>
                        <td>{{ $element->w_amount2/100 }}</td>
                        <td>{{ $element->w_method2 }}</td>
                        <td>{{ $element->w_stamp2 }}</td>
                        <td>{{ $element->dep_cnt }}</td>
                        <td>{{ round($element->dep_sum/100,2) }}</td>
                        <td>{{ round($element->wager_sum/100,2) }}</td>
                        <td>{{ $element->non_turned/100 }}</td>
                        <td>{{ $element->percent * 100 }} %</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div><!-- /.card-body -->
    </div>

@endsection

@include('admin.fraud.partials.fraud-footer')
