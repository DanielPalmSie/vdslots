<?php
/**
 * @var \App\Models\User $user
 */

use App\Constants\Networks;
use Carbon\Carbon;

function getSportsData($repo, Carbon $start_date = null, Carbon $end_date = null, array $networks = [], string $data_type, bool $with_format = false): int
{
    $total = 0;
    foreach ($networks as $network) {
        switch ($data_type) {
            case 'gross':
                $total += (int) $repo->getSportsGrossData($start_date, $end_date, $with_format, $network['name'], $network['product']);
                break;
            case 'ngr':
                $total += (int) $repo->getSportsNGRData($start_date, $end_date, $with_format, $network['name'], $network['product']);
                break;
            case 'wager':
                $total += (int) $repo->getSportsWagerData($start_date, $end_date, $with_format, $network['name'], $network['product']);
                break;
            default:
                throw new InvalidArgumentException("Invalid data type '$data_type'");
        }
    }

    return $total;
}


$deposits_collapse = isset($_COOKIE["new-bo-deposits-box"]) === false || $_COOKIE["new-bo-deposits-box"] == 1;
$withdrawals_collapse = isset($_COOKIE["new-bo-withdrawals-box"]) === false || $_COOKIE["new-bo-withdrawals-box"] == 1;
$start_date = $start_date ?? null;
$end_date = $end_date ?? null;
$is_custom = !empty($start_date) && empty($initial_state);

$now = Carbon::now();
$start_of_last_year = Carbon::now()->subMonths(12);
$start_of_last_month = Carbon::now()->subMonth()->startOfMonth();
$end_of_last_month = Carbon::now()->subMonth()->endOfMonth();
$start_of_current_month = Carbon::now()->startOfMonth();
$custom_start_date = $is_custom ? $start_date : $start_of_current_month;
$custom_end_date = $is_custom ? $end_date : null;

$networks = [
    'sportsbook' => [Networks::BETRADAR, Networks::ALTENAR],
    'poolx' => [Networks::POOLX],
    'bingo' => [Networks::BINGO],
];

$financial_data = [
    [
        'id' => 'gross-last-12-months',
        'name' => 'Gross last 12 months',
        'casino' => $user->repo->getGrossData($start_of_last_year, $now, false),
        'sportsbook' => getSportsData($user->repo, $start_of_last_year, $now, $networks['sportsbook'], 'gross'),
        'poolx_online' => getSportsData($user->repo, $start_of_last_year, $now, $networks['poolx'], 'gross'),
        'bingo' => getSportsData($user->repo, $start_of_last_year, $now, $networks['bingo'], 'gross'),
    ],
    [
        'id' => 'ngr-last-12-months',
        'name' => 'NGR last 12 months',
        'casino' => $user->repo->getNGRData($start_of_last_year, $now, false),
        'sportsbook' => getSportsData($user->repo, $start_of_last_year, $now, $networks['sportsbook'], 'ngr'),
        'poolx_online' => getSportsData($user->repo, $start_of_last_year, $now, $networks['poolx'], 'ngr'),
        'bingo' => getSportsData($user->repo, $start_of_last_year, $now, $networks['bingo'], 'ngr'),
    ],
    [
        'id' => 'wager-last-12-months',
        'name' => 'Wager last 12 months',
        'casino' => $user->repo->getWagerData($start_of_last_year, $now, false),
        'sportsbook' => getSportsData($user->repo, $start_of_last_year, $now, $networks['sportsbook'], 'wager'),
        'poolx_online' => getSportsData($user->repo, $start_of_last_year, $now, $networks['poolx'], 'wager'),
        'bingo' => getSportsData($user->repo, $start_of_last_year, $now, $networks['bingo'], 'wager'),
    ],
    [
        'id' => 'gross-previous-month',
        'name' => 'Gross previous month',
        'casino' => $user->repo->getGrossData($start_of_last_month, $end_of_last_month, false),
        'sportsbook' => getSportsData($user->repo, $start_of_last_month, $end_of_last_month, $networks['sportsbook'], 'gross'),
        'poolx_online' => getSportsData($user->repo, $start_of_last_month, $end_of_last_month, $networks['poolx'], 'gross'),
        'bingo' => getSportsData($user->repo, $start_of_last_month, $end_of_last_month, $networks['bingo'], 'gross'),
    ],
    [
        'id' => 'ngr-previous-month',
        'name' => 'NGR previous month',
        'casino' => $user->repo->getNGRData($start_of_last_month, $end_of_last_month, false),
        'sportsbook' => getSportsData($user->repo, $start_of_last_month, $end_of_last_month, $networks['sportsbook'], 'ngr'),
        'poolx_online' => getSportsData($user->repo, $start_of_last_month, $end_of_last_month, $networks['poolx'], 'ngr'),
        'bingo' => getSportsData($user->repo, $start_of_last_month, $end_of_last_month, $networks['bingo'], 'ngr'),
    ],
    [
        'id' => 'wager-previous-month',
        'name' => 'Wager previous month',
        'casino' => $user->repo->getWagerData($start_of_last_month, $end_of_last_month, false),
        'sportsbook' => getSportsData($user->repo, $start_of_last_month, $end_of_last_month, $networks['sportsbook'], 'wager'),
        'poolx_online' => getSportsData($user->repo, $start_of_last_month, $end_of_last_month, $networks['poolx'], 'wager'),
        'bingo' => getSportsData($user->repo, $start_of_last_month, $end_of_last_month, $networks['bingo'], 'wager'),
    ],
    [
        'id' => 'gross-current-or-custom-month',
        'name' => 'Gross ' . ($is_custom ? 'custom' : 'current') . ' month',
        'casino' => $user->repo->getGrossData($custom_start_date, $custom_end_date, false),
        'sportsbook' => getSportsData($user->repo, $custom_start_date, $custom_end_date, $networks['sportsbook'], 'gross'),
        'poolx_online' => getSportsData($user->repo, $custom_start_date, $custom_end_date, $networks['poolx'], 'gross'),
        'bingo' => getSportsData($user->repo, $custom_start_date, $custom_end_date, $networks['bingo'], 'gross'),
    ],
    [
        'id' => 'ngr-current-or-custom-month',
        'name' => 'NGR ' . ($is_custom ? 'custom' : 'current') . ' month',
        'casino' => $user->repo->getNGRData($custom_start_date, $custom_end_date, false),
        'sportsbook' => getSportsData($user->repo, $custom_start_date, $custom_end_date, $networks['sportsbook'], 'ngr'),
        'poolx_online' => getSportsData($user->repo, $custom_start_date, $custom_end_date, $networks['poolx'], 'ngr'),
        'bingo' => getSportsData($user->repo, $custom_start_date, $custom_end_date, $networks['bingo'], 'ngr'),
    ],
    [
        'id' => 'wager-current-or-custom-month',
        'name' => 'Wager ' . ($is_custom ? 'custom' : 'current') . ' month',
        'casino' => $user->repo->getWagerData($custom_start_date, $custom_end_date, false),
        'sportsbook' => getSportsData($user->repo, $custom_start_date, $custom_end_date, $networks['sportsbook'], 'wager'),
        'poolx_online' => getSportsData($user->repo, $custom_start_date, $custom_end_date, $networks['poolx'], 'wager'),
        'bingo' => getSportsData($user->repo, $custom_start_date, $custom_end_date, $networks['bingo'], 'wager'),
    ],
];
?>

<div class="row">
    @foreach($financial_data as $index => $data)
            <?php
            $section_cookie = $_COOKIE["new-bo-" . $data['id'] . '-box'];
            $section_collapse = isset($section_cookie) === false || $section_cookie == 1;
            ?>
        @if(($index + 1) % 3 === 1)
            <div class="col col-sm-4 col-md-4 col-lg-4">
                <ul class="list-group list-group-unbordered">
                    @endif
                    <li class="list-group-item p-2">
                        <a class="btn-box-tool" href="javascript:void(0)" data-boxname="{{ $data['id'] }}-box"
                           id="{{ $data['id'] }}-box-btn" data-target="#{{$data['id']}}-box" data-toggle="collapse"
                           title="Collapse">
                            <i class="fa fa-{{ $section_collapse ? 'plus' : 'minus' }} text-muted" aria-hidden="true"></i>
                        </a><b>{{ $data['name']}}</b>
                        <p class="float-right mb-0">{{ \App\Helpers\DataFormatHelper::nf($data['casino'] + $data['sportsbook'] + $data['poolx_online'] + $data['bingo']) }} {{ $user->currency }}</p>
                    </li>
                    <ul class="list-group list-group-unbordered @if($section_collapse) collapse show @else collapse @endif"
                        id="{{ $data['id'] }}-box">
                        <li class="list-group-item vertical p-2 border-bottom-1 border-top-0">
                            <span style="margin-left: 15px">Casino</span>
                            <p class="float-right mb-0">{{ \App\Helpers\DataFormatHelper::nf($data['casino']) }} {{ $user->currency }}</p>
                        </li>
                        <li class="list-group-item vertical p-2 border-bottom-1">
                            <span style="margin-left: 15px">Sportsbook</span>
                            <p class="float-right mb-0">{{  \App\Helpers\DataFormatHelper::nf($data['sportsbook']) }} {{ $user->currency }}</p>
                        </li>
                        <li class="list-group-item vertical p-2">
                            <span style="margin-left: 15px">Pool Bet Online</span>
                            <p class="float-right mb-0">
                                {{  \App\Helpers\DataFormatHelper::nf($data['poolx_online']) }} {{ $user->currency }}
                            </p>
                        </li>
                        <li class="list-group-item vertical p-2">
                            <span style="margin-left: 15px">Bingo</span>
                            <p class="float-right mb-0">
                                {{ \App\Helpers\DataFormatHelper::nf($data['bingo']) }} {{ $user->currency }}
                            </p>
                        </li>
                    </ul>
                    @if(($index + 1) % 3 === 0)
                </ul>
            </div>
        @endif
    @endforeach
</div>
<div class="row">

    <div class="col-12 col-sm-6 col-md-6 col-lg-6">
        <ul class="list-group list-group-unbordered mt-4">
            <li class="list-group-item p-2">
                <a class="btn-box-tool" href="javascript:void(0)" data-boxname="deposits-box" id="deposits-box-btn"
                   data-target="#deposits-box" data-toggle="collapse" title="Collapse">
                    <i class="fa fa-{{ $deposits_collapse == 1 ? 'plus' : 'minus' }} text-muted" aria-hidden="true"></i>
                </a><b>Deposits {{ $is_custom ? 'custom period' : 'last 12 months' }}</b>
                <p class="float-right mb-0">{{ \App\Helpers\DataFormatHelper::nf($deposits['sum']) }} {{ $user->currency }}</p>
            </li>
            @if($deposits['sum'] > 0)
                <ul class="list-group list-group-unbordered @if($deposits_collapse == 1) collapse show @else collapse @endif"
                    id="deposits-box">
                    @foreach($deposits['list'] as $key => $val)
                        @if (count($val['data']) == 1)
                            <li class="list-group-item p-2">
                                <span
                                    style="margin-left: 15px">{{ ucwords($val['data'][0]['method']) }} {{ $val['data'][0]['scheme'] }} {{ $val['data'][0]['card_hash'] }}</span>
                                <p class="float-right mb-o">{{ \App\Helpers\DataFormatHelper::nf($val['data'][0]['amount']) }} {{ $user->currency }}</p>
                            </li>
                        @else
                            <li class="list-group-item p-2">
                                <a style="margin-left: 10px" class="btn-box-tool multiple-method-btn-tool"
                                   href="javascript:void(0)" data-boxname="deposits-{{ $key }}-box"
                                   id="deposits-{{ $key }}-box-btn" data-target="#deposits-{{ $key }}-list-id"
                                   data-toggle="collapse" title="Collapse">
                                    <i class="fa fa-plus text-muted" aria-hidden="true"></i></a>
                                <span>{{ ucwords($key) }} (multiple)</span>
                                <p class="float-right mb-0">{{ \App\Helpers\DataFormatHelper::nf($val['method_sum']) }} {{ $user->currency }}</p>
                            </li>
                            <ul style="margin-bottom: 1px" class="list-group list-group-unbordered collapse"
                                id="deposits-{{ $key }}-list-id">
                                @foreach($val['data'] as $sub_element)
                                    <li class="list-group-item p-2">
                                <span style="margin-left: 32px">
                                    {{ trim($sub_element['scheme'] . ' ' . $sub_element['card_hash']) ?: 'Missing details' }}
                                </span>
                                        <p class="float-right mb-0">{{ \App\Helpers\DataFormatHelper::nf($sub_element['amount']) }} {{ $user->currency }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endforeach
                </ul>
            @endif
        </ul>
    </div>
    <div class="col-sm-6 col-md-6 col-lg-6">
        <ul class="list-group list-group-unbordered mt-4">
            <li class="list-group-item p-2">
                <a class="btn-box-tool" href="javascript:void(0)" data-boxname="withdrawals-box"
                   id="withdrawals-box-btn" data-target="#withdrawals-box" data-toggle="collapse" title="Collapse">
                    <i class="fa fa-{{ $withdrawals_collapse == 1 ? 'plus' : 'minus' }} text-muted" aria-hidden="true"></i>
                </a><b>Withdrawals {{ $is_custom ? 'custom period' :'last 12 months' }}</b>
                <p class="float-right mb-0">{{ \App\Helpers\DataFormatHelper::nf($withdrawals['sum']) }} {{ $user->currency }}</p>
            </li>
            @if($withdrawals['sum'] > 0)
                <ul class="list-group list-group-unbordered @if($withdrawals_collapse == 1) collapse show @else collapse @endif"
                    id="withdrawals-box">
                    @foreach($withdrawals['list'] as $key => $val)
                        @if (count($val['data']) == 1)
                            <li class="list-group-item p-2">
                                <span
                                    style="margin-left: 15px">{{ ucwords($val['data'][0]['single_account_label']) }}</span>
                                <p class="float-right mb-0">{{ \App\Helpers\DataFormatHelper::nf($val['data'][0]['amount']) }} {{ $user->currency }}</p>
                            </li>
                        @else
                            <li class="list-group-item p-2">
                                <a style="margin-left: 10px" class="btn-box-tool multiple-method-btn-tool"
                                   href="javascript:void(0)" data-boxname="withdrawals-{{ $key }}-box"
                                   id="withdrawals-{{ $key }}-box-btn"
                                   data-target="#withdrawals-{{ str_replace(' ', '-', $key) }}-list-id"
                                   data-toggle="collapse" title="Collapse">
                                    <i class="fa fa-plus text-muted" aria-hidden="true"></i></a>
                                <span>{{ ucwords($key) }} (multiple)</span>
                                <p class="float-right mb-0">{{ \App\Helpers\DataFormatHelper::nf($val['method_sum']) }} {{ $user->currency }}</p>
                            </li>
                            <ul style="margin-bottom: 1px" class="list-group list-group-unbordered collapse"
                                id="withdrawals-{{ str_replace(' ', '-', $key) }}-list-id">
                                @foreach($val['data'] as $sub_element)
                                    <li class="list-group-item p-2">
                                <span style="margin-left: 32px">
                                    {{ $sub_element['multiple_accounts_label'] ?: 'Missing details' }}
                                </span>
                                        <p class="float-right mb-0">{{ \App\Helpers\DataFormatHelper::nf($sub_element['amount']) }} {{ $user->currency }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endforeach
                </ul>
            @endif
        </ul>
    </div>
</div>

@section('footer-javascript')
    @parent
    <script>
        $(function () {
            @foreach($financial_data as $key => $data)
            manageCollapsible("{{$data['id']}}-box-btn");
            @endforeach

            manageCollapsible("deposits-box-btn");
            manageCollapsible("withdrawals-box-btn");
            $('.multiple-method-btn-tool').click(function (e) {
                self = $(this).find("i");
                if (self.hasClass("fa-plus")) {
                    self.removeClass('fa-plus').addClass('fa-minus');
                } else {
                    self.removeClass('fa-minus').addClass('fa-plus');
                }
            });
        });
    </script>
@endsection

