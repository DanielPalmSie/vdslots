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
                    <li class="breadcrumb-item active" aria-current="page">Bonus abusers</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin.fraud.partials.topmenu')
    @include('admin.fraud.partials.bonus-abusers-filter')
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Bonus Abusers</h3>
        </div>
        <div class="card-body">
            <table class="fraud-section-datatable table table-striped table-bordered dt-responsive w-100 border-collapse">
                <thead>
                <tr>
                    <th>User ID</th>
                    <th>Verified</th>
                    <th>Country</th>
                    <th>Currency</th>
                    <th>Wagered Sum</th>
                    <th>Bonus Sum</th>
                    <th>Bonus Percentage over Wager Sum</th>
                </tr>
                </thead>
                <tbody>
                @foreach($data['list'] as $element)
                    <tr>
                        <td class="align-middle"><a target="_blank" href="{{ \App\Helpers\URLHelper::generateUserProfileLink($app, htmlspecialchars($element->user_id)) }}">{{ htmlspecialchars($element->user_id) }}</a></td>
                        <td>{{ $element->verified == 1 ? 'Yes' : 'No' }}</td>
                        <td>{{ htmlspecialchars($element->country) }}</td>
                        <td>{{ $element->currency }}</td>
                        <td>{{ round($element->wagered_sum/100,2) }}</td>
                        <td>{{ round($element->bonus_sum/100,2) }}</td>
                        <td>{{ round($element->bonus_percentage,2) }} %</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@include('admin.fraud.partials.fraud-footer')
