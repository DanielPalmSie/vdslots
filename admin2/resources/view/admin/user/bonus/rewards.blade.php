@extends('admin.layout')
<?php
$u = cu($user->username);
?>
@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')
    @include('admin.user.bonus.partials.bonuses-filter')
    <div class="card">
        @include('admin.user.bonus.partials.nav-bonuses')
        <div class="card-body">
            <div class="row">
                <div class="table-responsive">
                    <table id="user-datatable" class="table table-striped table-bordered dt-responsive"
                           cellspacing="0" width="100%">
                        <thead>
                        <tr>
                            <th>Bonus Type</th>
                            <th>Bonus</th>
                            <th>Amount ({{ $user->currency }})</th>
                            <th>Reward ({{ $user->currency }})</th>
                            <th>Activation Time</th>
                            <th>Last Change</th>
                            <th>Bonus Status</th>
                            <th>Wager Req. ({{ $user->currency }})</th>
                            <th>Bonus Progress</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($rewards as $reward)
                            <tr>
                                <td>{{ $reward->bonus_type }}</td>
                                <td>{{ \App\Helpers\DataFormatHelper::getBonusName($reward->bonus, $user->repo->getCurrencyObject()) }}</td>
                                <td>{{ !is_null($reward->bonus_amount) ? $reward->bonus_amount / 100 : '' }}</td>
                                <td>{{ !empty($reward->bonus_reward) ? $reward->bonus_reward / 100 : '' }}</td>
                                <td>{{ $reward->activation_time }}</td>
                                <td>{{ $reward->last_change }}</td>
                                <td>{{ ucwords($reward->bonus_status) }}</td>
                                <td>{{ \App\Helpers\DataFormatHelper::nf($reward->wager_req) }}</td>
                                <td>@if($reward->progress == 0 && $reward->bonus_status == 'approved') 100 @else {{ round($reward->progress,2) }} @endif%</td>
                                <td>
                                    @if($can_forfeit && $reward->bonus_status == 'active')
                                        <a href="{{ $app['url_generator']->generate('admin.user-delete-bonus-entry', ['user' => $user->id, 'reward_id' => $reward->entry_id, 'bonus_id' => $reward->bonus_id ]) }}">Forfeit</a>
                                    @endif
                                    @if($can_reactivate && phive('Bonuses')->canReactivate((array)$reward, $user->id) === true && $reward->bonus_status == 'failed' && (($reward->exclusive != 4 && $reward->bonus_type != 'freespin') || $reward->exclusive == 4))
                                        <a href="{{ $app['url_generator']->generate('admin.user-reactivate-bonus-entry', ['user' => $user->id, 'reward_id' => $reward->entry_id]) }}">Reactivate</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            $("#user-datatable").DataTable({
                "pageLength": 25,
                "language": {
                    "emptyTable": "No results found.",
                    "lengthMenu": "Display _MENU_ records per page"
                },
                "order": [[ {{ $sort['column'] }}, "{{ $sort['type'] }}"]],
                "columnDefs": [{"targets": 9, "orderable": false, "searchable": false}]
            });
        });
    </script>
@endsection
