<?php
/**
 *
 */

?>

<div class="card card-outline card-warning up-box @if($other_data_collapse == 1) collapsed-box @endif" id="other-data-box">
    <div class="card-header border-bottom-0">
        <h3 class="card-title text-lg">Gamification information</h3>
        <div class="card-tools">
            <button class="btn btn-tool" data-boxname="other-data-box" id="other-data-box-btn" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                <i class="fa fa-{{ $other_data_collapse == 1 ? 'plus' : 'minus' }}"></i>
            </button>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="row">
        <div class="col-12 col-sm-6 col-md-6 col-lg-4">
            <ul class="list-group list-group-unbordered">
                <li class="list-group-item d-flex justify-content-between">
                    <b>Side games winnings last 12 months</b> <p>{{ \App\Helpers\DataFormatHelper::nf($user_reward_list['sum']['all']) }} {{ $user->currency }}</p>
                </li>
                @if($user_reward_list['sum']['all'] > 0)
                    @foreach($user_reward_list['list']['all'] as $name => $amount)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ ucwords($name) }}</span>
                            <p>{{ \App\Helpers\DataFormatHelper::nf($amount) }} {{ $user->currency }}</p>
                        </li>
                    @endforeach
                @endif
                <li class="list-group-item d-flex justify-content-between">
                    <b>Side games winnings last 12 months as %</b>
                    <p>{{ \App\Helpers\DataFormatHelper::pf($user_reward_list['sum']['all']/$user->repo->getWagerData(\Carbon\Carbon::now()->subMonths(12), \Carbon\Carbon::now(), false))}}</p>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <b>Side games winnings this month</b> <p class="pull-right">{{ \App\Helpers\DataFormatHelper::nf($user_reward_list['sum']['month']) }} {{ $user->currency }}</p>
                </li>
                @if($user_reward_list['sum']['month'] > 0)
                    @foreach($user_reward_list['list']['month'] as $name => $amount)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ ucwords($name) }}</span>
                            <p>{{ \App\Helpers\DataFormatHelper::nf($amount) }} {{ $user->currency }}</p>
                        </li>
                    @endforeach
                @endif
                <li class="list-group-item d-flex justify-content-between">
                    <b>Side games winnings this month as %</b>
                    <p>{{ \App\Helpers\DataFormatHelper::pf($user_reward_list['sum']['month']/$user->repo->getWagerData(\Carbon\Carbon::now()->startOfMonth(), null, false))}}</p>
                </li>
            </ul>
        </div>
        <div class="col-12 col-sm-6 col-md-6 col-lg-4">
            <ul class="list-group list-group-unbordered">
                <li class="list-group-item d-flex justify-content-between">
                    <b>Failed side games winnings last 12 months</b>
                    <p>{{ \App\Helpers\DataFormatHelper::nf($failed_rewards_data['all']) }} {{ $user->currency }}</p>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <b>Failed side games winnings this month</b>
                    <p>{{ \App\Helpers\DataFormatHelper::nf($failed_rewards_data['month']) }} {{ $user->currency }}</p>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <b>Pending Weekend Booster</b>
                    <p>{{ $user->repo->getPendingCashback(true) }} {{ $user->currency }}</p>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <b>Weekend Booster this week</b>
                    <p>{{ $user->repo->getCashbackThisWeek(true) }} {{ $user->currency }}</p>
                </li>
                {{--<li class="list-group-item">
                    <b>Last week clash payout</b>
                    <p class="pull-right">{{ $last_race_payout }} spins</p>
                </li>
                <li class="list-group-item">
                    <b>Current week clash payout</b>
                    <p class="pull-right">{{ $pending_race_payout }} spins</p>
                </li>--}}
            </ul>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-6 col-lg-4">
            <ul class="list-group list-group-unbordered">
                <?php $segments = \App\Repositories\UserProfileRepository::getSegments($user)?>
                <li class="list-group-item d-flex justify-content-between">
                    <b>Segment this month</b><p>{{ $segments['this_month'] }}</p>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <b>Segment last month</b><p> {{ $segments['last_month'] }}</p>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <b>Experience</b>
                    <p>
                        @if($user->repo->getSetting('xp-points'))
                            {{ $user->repo->getSetting('xp-points') }} /
                            {{ \App\Helpers\DataFormatHelper::getXpThreshold($user->repo->getSetting('xp-level') + 1) }}
                        @else
                            0
                        @endif
                    </p>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <b>Level</b> <p>{{ $user->repo->getSetting('xp-level') ? $user->repo->getSetting('xp-level') : 0 }}</p>
                </li>
            </ul>
        </div>
    </div>
    </div>
</div>
