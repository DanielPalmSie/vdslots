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
                    <li class="breadcrumb-item active" aria-current="page">[B.O.S] Daily Gladiators</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('admin.fraud.partials.topmenu')
    @include('admin.fraud.partials.dailygladiatorsfilter')
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Battle Of Slots Gladiators</h3>
            @include('admin.fraud.partials.download-button')
        </div><!-- /.card-header -->
        <div class="card-body">
            <table class="fraud-section-datatable table table-striped table-bordered dt-responsive w-100 border-collapse">
                <thead>
                <tr>
                    <th>User ID</th>
                    <th>Verified</th>
                    <th>Battle Alias</th>
                    <th>Country</th>
                    <th>Lifetime Number of Battles</th>
                    <th>Number of Battles</th>
                    <th>Lifetime Win/Loss Ratio %</th>
                    <th>Win/Loss Ratio %</th>
                    <th>Lifetime Spin Ratio %</th>
                    <th>Spins</th>
                    <th>Spins Left</th>
                    <th data-toggle="tooltip" title="Tooltip!">Spin Ratio %</th>
                </tr>
                </thead>
                <tbody>
                @foreach($daily_gladiators as $element)
                    <tr>
                        <td class="align-middle">
                            <a target="_blank" href="{{ \App\Helpers\URLHelper::generateUserProfileLink($app, htmlspecialchars($element->user_id)) }}">{{ htmlspecialchars($element->user_id) }}</a>
                        </td>
                        <td>{{ $element->verified == 1 ? 'Yes' : 'No' }}</td>
                        <td>{{ $element->battle_alias }}</td>
                        <td>{{ htmlspecialchars($element->country) }}</td>
                        <td>{{ $element->lifetime_battles }}</td>
                        <td>{{ $element->daily_battles }}</td>
                        <td>{{ $element->lifetime_battles/100 * $element->lifetime_win_count }}</td>
                        <td>{{ $element->daily_battles/100 * $element->daily_win_count }}</td>
                        <td>{{ $element->lifetime_spin_ratio }}</td>
                        <td>{{ $element->total_spins }}</td>
                        <td>{{ $element->total_spins_left }}</td>
                        <td>{{ $element->spin_ratio }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div><!-- /.card-body -->
    </div>

@endsection

@include('admin.fraud.partials.fraud-footer')
