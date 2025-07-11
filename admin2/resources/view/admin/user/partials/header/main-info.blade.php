<?php
/**
 * @var \App\Models\User          $user
 * @var \Silex\Application        $app
 * @var \App\Models\UserComplaint $issue
 */

/** @var \App\Repositories\UserSettingsRepository $settings_repo */

use App\Classes\Distributed;

$settings_repo = new \App\Repositories\UserSettingsRepository($user);

$user_reward_list = $user->repo->getRewardsData(\Carbon\Carbon::now()->subMonths(12), \Carbon\Carbon::now());
$failed_rewards_data = $user->repo->getFailedRewardsData(\Carbon\Carbon::now()->subMonths(12), \Carbon\Carbon::now());
$active_race_entry = $user->repo->getCurrentRaceData();

$remote_profiles = $app['vs.sections']['user.main-info']['show.brands-links'] ? Distributed::getCustomerProfiles($app, $user) : [];

$last_race_payout = $user->repo->getClashPayoutForWeek(-1, true);
$pending_race_payout = $user->repo->getClashPayoutForWeek(0, true);

//todo if not set collapse by default all except user_info, personal_data and comments
$user_info_collapse = $_COOKIE["new-bo-user-information-box"];
$personal_data_collapse = $_COOKIE["new-bo-personal-data-box"];
$financial_data_collapse = $_COOKIE["new-bo-financial-data-box"];
$other_data_collapse = $_COOKIE["new-bo-other-data-box"];
$comments_collapse = $_COOKIE["new-bo-comments-box"];
$follow_up_collapse = $_COOKIE["new-bo-follow-up-box"];
$issues_collapse = $_COOKIE["new-bo-issues-box"];
$forums_collapse = $_COOKIE["new-bo-forums-box"];
$promo_marketing_channels_box = $_COOKIE["new-bo-promo-marketing-channels-box"];

$user->settings_repo->populateSettings();
?>

<div class="card @if($user->repo->getLastComplaint()) card-danger @else card-primary @endif @if($user_info_collapse == 1) collapsed-card @endif border border-primary" id="user-information-box">
    <div class="card-header">
        <h4 class="card-title text-lg mr-3">User Information</h4>
        <ul class="list-inline m-0 p-0">
            @if (p('show.balances'))
                <li class="list-inline-item card-title">Account balance: <b>{{ $user->repo->getMainBalance(true) }}</b> {{ $user->currency }}</li>
                <li class="list-inline-item card-title">Bonus balance (wager): <b>{{ $user->repo->getRewardsBalance(true) }}</b> {{ $user->currency }}</li>
                <li class="list-inline-item card-title">Bonus balance (real): <b>{{ $user->repo->getBonusBalance(true) }}</b> {{ $user->currency }}</li>
                {{-- For now we keep the new booster amount only here and on the dedicated page cause the other "weekend booster rows" inside "promotions informations" are related to bonuses.
                    With the new logic is not a bonus anymore so we cannot fiddle with those amounts. --}}
                @if($user->repo->hasVault())
                    <li class="list-inline-item card-title">
                        <a href="{{$app['url_generator']->generate('admin.user-casino-cashback', ['user' => $user->id])}}">
                            Vault balance: <b>{{ $user->repo->getVaultBalance() }}</b> {{ $user->currency }}
                        </a>
                    </li>
                @endif
            @endif
        </ul>
        <div class="card-tools">
            <button class="btn btn-tool" data-boxname="user-information-box"
                    id="user-information-box-btn" data-widget="collapse" data-toggle="tooltip" title="Collapse">
                <i class="fa fa-{{ $user_info_collapse == 1 ? 'plus' : 'minus' }} text-white"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-12 col-sm-12 col-md-8 col-lg-8">
                @include('admin.user.partials.boxes.personal-data')
                @include('admin.user.partials.boxes.financial-data-box')
                @include('admin.user.partials.boxes.promotions')
                @include('admin.user.partials.boxes.game-data-box')
                @include('admin.user.partials.boxes.promo-marketing-channels')
            </div>
            <div class="col-12 col-sm-12 col-md-4 col-lg-4">
                @include('admin.user.partials.boxes.complaints')
                @if(p('show.forums'))
                    @include('admin.user.partials.boxes.forums')
                @endif
                @include('admin.user.partials.boxes.messages')
                @if(p('show.follow.up.box'))
                    @include('admin.user.partials.boxes.follow-up')
                @endif
            </div>
        </div>
    </div>
</div>


{{--todo after testing move this to the layout--}}
@section('header-css')
    @parent
    <style>
        body {
            opacity: 0
        }
    </style>
@endsection

@section('footer-javascript')
    @parent
    <script>
        function manageCollapsible(name) {
            var selector = $('#' + name);
            var boxName = selector.data('boxname');
            var box_sel = $('#' + boxName);

            if (Cookies.get("new-bo-" + boxName) == 0) {
                box_sel.addClass('collapsed-card');
                selector.find("i").removeClass('fa-minus').addClass('fa-plus');
            } else {
                box_sel.removeClass('collapsed-card');
                selector.find("i").removeClass('fa-plus').addClass('fa-minus');
            }

            selector.off('click').on("click", function (e) {
                if (box_sel.hasClass('collapsed-card')) {
                    box_sel.removeClass('collapsed-card');
                    selector.find("i").removeClass('fa-plus').addClass('fa-minus');
                    Cookies.set("new-bo-" + boxName, 1, { expires: 30, path: '/' });
                } else {
                    box_sel.addClass('collapsed-card');
                    selector.find("i").removeClass('fa-minus').addClass('fa-plus');
                    Cookies.set("new-bo-" + boxName, 0, { expires: 30, path: '/' });
                }
            });
        }


        function manageFilteredData(name, last_12_months, labelId = null) {
            var selector = $('#' + name);
            var labelSelector = labelId ? $('#' + labelId) : null;
            var start = moment(); // default: today
            var end = moment();   // default: today

            var ranges = {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 6 Months': [moment().subtract(6, 'months').startOf('day'), moment()]
            };

            if (last_12_months) {
                ranges['Last 12 Months'] = [moment().subtract(12, 'months').startOf('day'), moment()];
            }
            ranges['All Time'] = [moment("{{ $user->register_date }}"), moment()];

            selector.daterangepicker({
                opens: "right",
                alwaysShowCalendars: true,
                showDropdowns: true,
                linkedCalendars: false,
                autoUpdateInput: false,
                locale: {
                    format: 'YYYY-MM-DD',
                    separator: ' - ',
                    firstDay: 1,
                    cancelLabel: last_12_months ? 'Last 12 months' : 'All Time'
                },
                startDate: start,
                endDate: end,
                ranges: ranges
            });

            function hideInitialLabel() {
                if (labelSelector) {
                    labelSelector.remove();
                }
            }

            function sendAjax(start, end, extraData = {}) {
                $.ajax({
                    url: selector.data('url'),
                    type: "POST",
                    data: Object.assign({
                        start_date: start.format('YYYY-MM-DD'),
                        end_date: end.format('YYYY-MM-DD')
                    }, extraData),
                    success: function (response) {
                        $(selector.data('target')).html(response['html']);
                        $('#ajax-container-financial').find('[id$="-box-btn"]').each(function () {
                            manageCollapsible($(this).attr('id'));
                        });
                        $('.multiple-method-btn-tool').each(function () {
                            const $btn = $(this);
                            const $icon = $btn.find('i');
                            const targetSelector = $btn.data('target');
                            const $target = $(targetSelector);

                            if ($target.hasClass('show')) {
                                $icon.removeClass('fa-plus').addClass('fa-minus');
                            } else {
                                $icon.removeClass('fa-minus').addClass('fa-plus');
                            }
                            $btn.off('click').on('click', function (e) {
                                e.preventDefault();
                                $target.collapse('toggle');
                            });

                            $target.off('shown.bs.collapse').on('shown.bs.collapse', function () {
                                $btn.find('i').removeClass('fa-plus').addClass('fa-minus');
                            });

                            $target.off('hidden.bs.collapse').on('hidden.bs.collapse', function () {
                                $btn.find('i').removeClass('fa-minus').addClass('fa-plus');
                            });

                        });
                    },
                    error: function () {
                        alert('AJAX ERROR');
                    }
                });
            }

            selector.on('apply.daterangepicker', function(ev, picker) {
                hideInitialLabel();
                selector.val(
                    picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD')
                );
                sendAjax(picker.startDate, picker.endDate);
            });

            selector.on('cancel.daterangepicker', function(ev, picker) {
                hideInitialLabel();
                const start = last_12_months
                    ? moment().subtract(12, 'months').startOf('day')
                    : moment("{{ $user->register_date }}");
                const end = moment();
                selector.val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
                sendAjax(start, end, { initial_state: 1 });

                const pickerInstance = selector.data('daterangepicker');
                pickerInstance.setStartDate(start);
                pickerInstance.setEndDate(end);
            });

            selector.on('keydown', function (e) {
                hideInitialLabel();
                if (e.key === 'Enter') {
                    e.preventDefault();

                    const val = $(this).val();
                    const parts = val.split(' - ');
                    if (parts.length === 2) {
                        const start = moment(parts[0], 'YYYY-MM-DD', true);
                        const end = moment(parts[1], 'YYYY-MM-DD', true);
                        if (start.isValid() && end.isValid() && start.isSameOrBefore(end)) {
                            const picker = $(this).data('daterangepicker');
                            picker.setStartDate(start);
                            picker.setEndDate(end);
                            sendAjax(start, end);
                        }
                    }
                }
            });

            selector.on('change', function () {
                const val = $(this).val();
                const parts = val.split(' - ');
                if (parts.length === 2) {
                    const start = moment(parts[0], 'YYYY-MM-DD', true);
                    const end = moment(parts[1], 'YYYY-MM-DD', true);
                    if (start.isValid() && end.isValid() && start.isSameOrBefore(end)) {
                        const picker = $(this).data('daterangepicker');
                        picker.setStartDate(start);
                        picker.setEndDate(end);
                        sendAjax(start, end);
                    }
                }
            });
        }

        //todo after testing move this to the layout
        $(window).on('load', function () {
            if (localStorage.getItem('new-bo-scroll') > 0) {
                $(document).scrollTop(localStorage.getItem('new-bo-scroll'));
            }
            $('body').animate({'opacity': '1'}, 100);
        });

        $(function () {
            manageCollapsible('user-information-box-btn');
            manageCollapsible('personal-data-box-btn');
            manageCollapsible('financial-data-box-btn');
            manageCollapsible('other-data-box-btn');
            manageCollapsible('comments-box-btn');
            manageCollapsible('follow-up-box-btn');
            manageCollapsible('issues-box-btn');
            manageCollapsible('forums-box-btn');
            manageCollapsible('promo-marketing-channels-box-btn');

            //todo after testing move this to the layout
            $(document).on('scroll', function () {
                localStorage.setItem('new-bo-scroll', $(window).scrollTop());
            });
        });
    </script>
@endsection














