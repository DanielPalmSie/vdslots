<div class="card-body">

@if ($app)

    @if ($buttons['delete'])
    <div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalDeleteLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalDeleteLabel">Confirm Delete</h4>
                </div>

                <div class="modal-body">
                    <p>You are about to delete the Tournament Template. This procedure is irreversible.</p>
                    <p>Do you want to proceed?</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button id="delete-modalbtn" class="btn btn-danger" data-dismiss="modal">Delete</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-12 col-sm-6 col-lg-6">

            <form id="tournament-template-form" class="" method="post">
                <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                @if($tournament_template)
                    <input name="id" class="form-control" type="hidden" value="{{ $tournament_template->id }}">
                @endif
                <div class="form-group row">
                    <label class="col-sm-4 col-form-label" for="tournament_name">Tournament Template Name <span data-toggle="tooltip" title="Easy to read and sumarized template name that users will see." style="opacity:0.3;" class="badge bg-lightblue">?</span></label>
                    <div class="col-sm-8">
                        @if($tournament_template)
                            <input id="input-tournament_name" data-uniqueid="{{ $tournament_template->id }}" name="tournament_name" class="form-control" type="text" value="{{ $tournament_template->tournament_name }}">
                        @else
                            <input id="input-tournament_name" data-uniqueid="" name="tournament_name" class="form-control" type="text" value="">
                        @endif
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-4 col-form-label" for="game_ref">Game Ref <span data-toggle="tooltip" title="The game this tournament template references." style="opacity:0.3;" class="badge bg-lightblue">?</span></label>
                    <div class="col-sm-8">
                        <div class="row">
                            <div class="col-sm-12">
                                <select id="input-game_ref" name="game_ref" class="form-control select2-class select-game_ref" style="width: 100%;" data-placeholder="No Game Reference specified" data-allow-clear="true"> <!-- TODO: Allow to clear? -->
                                    @if ($game)
                                        <option value="{{ $game->ext_game_name }}">{{ $game->ext_game_name }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                            Game Name: <span id="span-game_ref_game_name">@if($game){{ $game->game_name }}@endif</span>
                            </div>
                        </div>
                    </div>
                </div>

                @foreach ($columns_order as $co)
                    <div class="form-group row">
                        <label class="col-sm-4 col-form-label" for="{{ $co['column'] }}">{{ ucwords(str_replace('_', ' ', $co['column'])) }} @if(isset($co['tooltip']))<span data-html="true" data-toggle="tooltip" title="{{ $co['tooltip'] }}" style="opacity:0.3;" class="badge bg-lightblue">?</span>@endif
                            @if(isset($co['help-block']))
                                <button class="btn btn-xs help-btn">Learn more...</button>
                            @endif
                        </label>
                        <div class="col-sm-8">
                            @if ($co['type'] == "select2")
                                <select id="select-{{ $co['column'] }}"
                                    @if($co['readonly'])
                                        readonly
                                    @endif
                                    name="{{ $co['column'] }}" class="form-control select2-class select-{{ $co['column'] }}" style="width: 100%;" data-placeholder="No {{ ucwords(str_replace('_', ' ', $co['column'])) }} specified" data-allow-clear="true">
                                    @foreach ($all_distinct[$co['column']] as $c)
                                        @if ($tournament_template->{$co['column']} == $c)
                                            <option value="{{ $c }}" selected="true">{{ $c }}</option>
                                        @else
                                            <option value="{{ $c }}">{{ $c }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            @elseif ($co['type'] == "select2-multi")
                                <select id="select-{{ $co['column'] }}"
                                    @if($co['readonly']) readonly @endif
                                    name="{{ $co['column'] . '[]' }}" class="form-control select2-class select-{{ $co['column'] }}" style="width: 100%;" data-placeholder="No {{ ucwords(str_replace('_', ' ', $co['column'])) }} specified" data-allow-clear="true">
                                    @foreach ($all_distinct[$co['column']] as $c)
                                        <option value="{{ $c['id'] }}">{{ $c['text'] }}</option>
                                    @endforeach
                                </select>
                                <script>
                                    $(document).ready(function () {
                                        $(".select-{{ $co['column'] }}").select2({
                                            selectOnBlur: true,
                                            multiple: true,
                                            tags: true,
                                            allowClear: true,
                                        });
                                        $(".select-{{ $co['column'] }}").val({!! json_encode($tournament_template->{$co['column']} ?? []) !!});
                                        $(".select-{{ $co['column'] }}").change();
                                    })
                                </script>
                            @elseif ($co['type'] == "select2_filter")
                                <select id="select-{{ $co['column'] }}"
                                    @if($co['readonly'])
                                        readonly
                                    @endif
                                    name="{{ $co['column'] }}" class="form-control select2-class select-{{ $co['column'] }}" style="width: 100%;" data-placeholder="No {{ ucwords(str_replace('_', ' ', $co['column'])) }} specified" data-allow-clear="true">
                                    @if (strlen($tournament_template->{$co['column']}) > 0)
                                        <option value="{{ $tournament_template->{$co['column']} }}" selected="true">{{ $tournament_template->{$co['column']} }}</option>
                                    @endif
                                </select>
                            @elseif ($co['type'] == "date")
                                <input id="input-{{ $co['column'] }}" data-provide="datepicker" data-date-format="yyyy-mm-dd"
                                @if($co['readonly'])
                                    readonly
                                @endif
                                    name="{{ $co['column'] }}" class="form-control" type="text"
                                @if ($tournament_template)
                                    value="{{ $tournament_template->{$co['column']} }}"
                                @else
                                    value=""
                                @endif
                                >
                            @elseif ($co['type'] == "boolean")
                                <input id="input-{{ $co['column'] }}"
                                @if($co['readonly'])
                                    readonly
                                @endif
                                    name="{{ $co['column'] }}" class="form-control" data-on="Yes" data-off="No" data-toggle="toggle" data-onstyle="success" data-offstyle="danger" type="checkbox"
                                @if ($tournament_template)
                                    value="{{ $tournament_template->{$co['column']} }}" @if ($tournament_template->{$co['column']}) checked @endif
                                @else
                                    value=""
                                @endif
                                >
                            @else
                                <input id="input-{{ $co['column'] }}"
                                data-d="{{ $co['readonly'] }}"
                                @if($co['readonly'])
                                    readonly
                                @endif
                                @if($co['placeholder'])
                                    placeholder="{{ $co['placeholder'] }}"
                                @endif
                                    name="{{ $co['column'] }}" class="form-control" type="{{ $co['type'] }}"
                                @if ($tournament_template)
                                    value="{{ $tournament_template->{$co['column']} }}"
                                @else
                                    value=""
                                @endif
                                >
                            @endif

                            @if(isset($co['help-block']))
                                <p class="collapse help-block">{!! nl2br($co['help-block']) !!}</p>
                            @endif
                        </div>
                    </div>
                @endforeach

            </form>

        </div>

        <div id="button-footer">
            <div class="form-group row">
                <!--<div class="col-sm-6 col-sm-offset-4">-->
                <div class="col-sm-12 text-center">
                    @if ($buttons['save'])
                        <button id="save-tournament-template-btn" class="btn btn-primary">
                            {{ $buttons['save'] }}
                        </button>
                    @endif
                    @if ($buttons['save-as-new'])
                        &nbsp; | &nbsp;
                        <button id="save-as-new-tournament-template-btn" class="btn btn-info">
                            {{ $buttons['save-as-new'] }}
                        </button>
                    @endif
                    @if ($buttons['delete'])
                        &nbsp; | &nbsp;
                        <button id="delete-tournament-template-btn" class="btn btn-danger" data-toggle="modal" data-target="#confirm-delete">
                            {{ $buttons['delete'] }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-6">
            <div id="dropzone">
                <form  id="tournament-template-image-upload" action="{{ $app['url_generator']->generate('tournamenttemplates.fileupload') }}" class="dropzone">
                    <div class="dz-message">
                        Drop image file here or click to select file to upload.<br/>
                        <span class="note"><small>(Filename should match the Game Ref name.)</small></span>
                    </div>
                </form>
            </div>

            <div class="card follow-scroll">
                <div class="card-heading text-center">
                    <div class="col-12 col-sm-12 col-lg-12">
                        @if($tournament_template)
                            <img class="img-responsive img-thumbnail" src="{{ $tournament_template->img }}" title="{{ $tournament_template->img }}" />
                        @else
                            <img class="img-responsive img-thumbnail" src="{{ getMediaServiceUrl() }}/file_uploads/tournaments/noname.jpg" title="" />
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="col-12 col-sm-12 col-lg-12">
                        <ul class="nav nav-tabs">
                            <li class="nav-item"><a class="nav-link active" href="#tab_overview" data-toggle="tab">Overview</a></li>
                            <li class="nav-item"><a class="nav-link" href="#tab_award_ladder" data-toggle="tab">Award Ladder</a></li>
                        </ul>

                        <div class="tab-content clearfix">
                            <div class="tab-pane active" id="tab_overview">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <td>Registration Show</td>
                                            <td id="example-registration_show"></td>
                                        </tr>
                                        @if(!empty($tournament_template_example['registration_opens']))
                                        <tr>
                                            <td>Registration Opens</td>
                                            <td id="example-registration_opens">{{ $tournament_template_example['registration_opens'] }}</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td>Start</td>
                                            <td id="example-tournament_start">{!! $tournament_template_example['start'] !!}</td>
                                        </tr>
                                        <tr>
                                            <td>End</td>
                                            <td id="example-tournament_end">-</td>
                                        </tr>
                                        <tr>
                                            <td>Min # of Players</td>
                                            <td id="example-min_players">{{ $tournament_template_example['min_players'] }}</td>
                                        </tr>
                                        <tr>
                                            <td>Max # of Players</td>
                                            <td id="example-max_players">{{ $tournament_template_example['max_players'] }}</td>
                                        </tr>
                                        <tr>
                                            <td>Buy-in</td>
                                            <td id="example-buy_in">{{ $tournament_template_example['buy_in'] }}</td>
                                        </tr>
                                        @if(!empty($tournament_template_example['pot_cost']))
                                        <tr>
                                            <td>Pot Cost {{(empty($tournament_template['free_pot_cost']) ? '' : ' ('.$tournament_template['free'].')')}}</td>
                                            <td id="example-pot_cost">{{ $tournament_template_example['pot_cost'] }}</td>
                                        </tr>
                                        @endif
                                        @if(!empty($tournament_template_example['guaranteed_prize_amount']))
                                        <tr>
                                            <td>Guranteed Prize Pool</td>
                                            <td id="example-guaranteed_prize_amount">{{ $tournament_template_example['guaranteed_prize_amount'] }}</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td>Duration</td>
                                            <td id="example-duration_minutes">{{ $tournament_template_example['duration_minutes'] }}</td>
                                        </tr>
                                        @if($tournament_template['start_format'] == 'mtt')
                                        <tr>
                                            <td>Mtt Late Reg Duration</td>
                                            <td id="example-mtt_late_reg_duration_minutes">{{ $tournament_template['mtt_late_reg_duration_minutes'] }} minutes</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td>Spins</td>
                                            <td id="example-spins">{{ $tournament_template_example['spins'] }}</td>
                                        </tr>
                                        <tr>
                                            <td>Bet Amount Interval</td>
                                            <td id="example-bet_levels">{{ $tournament_template_example['bet_levels'] }}</td>
                                        </tr>
                                        <tr>
                                            <td>Player Balance</td>
                                            <td id="example-player_balance"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-pane" id="tab_award_ladder">
                                @include('admin.gamification.tournamenttemplates.partials.awardladder')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <button id="update-example-btn" class="btn btn-default">Update</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-sm-12 col-lg-12">
            @if ($buttons['save-all'])
            <hr />
            <div class="text-center">
                <button id="save-all-btn" class="btn btn-primary">{{ $buttons['save-all'] }}</button>
            </div>
            @endif
        </div>
    </div>

    @push('extrajavascript')
    <script type="text/javascript" src="/phive/js/mg_casino.js"></script>
    <script type="text/javascript">

        var disableTotalCostUpdate = false;

        function updateCost() {
            var xspin_info = parseInt($('#input-xspin_info').val());
            var min_bet    = parseInt($('#input-min_bet').val());
            $('#input-cost').val(xspin_info*min_bet);

            //updateHouseFee();
            updateTotalCost();
            updatePlayerBalance();
        }

        function updateHouseFee() {
            var cost = parseInt($('#input-cost').val(), 10);
            $('#input-house_fee').val(cost*0.1);
        }

        function updateTotalCost() {

            if (disableTotalCostUpdate) {
                return;
            }

            var cost            = parseInt($('#input-cost').val(), 10);
            var house_fee       = parseInt($('#input-house_fee').val(), 10);
            var rebuy_house_fee = parseInt($('#input-rebuy_house_fee').val(), 10);
            var rebuy_cost      = parseInt($('#input-rebuy_cost').val(), 10);
            var rebuy_times     = parseInt($('#input-rebuy_times').val(), 10);

            $('#input-total_cost').val(cost+house_fee+((rebuy_house_fee+rebuy_cost)*rebuy_times));
        }

        function updatePlayerBalance() {
            var cost           = parseInt($('#input-cost').val(), 10);
            var spin_m         = parseInt($('#input-spin_m').val(), 10);
            var player_balance = ((cost * spin_m)/100).toFixed(2);
            $('#example-player_balance').html("&euro; "+player_balance);
        }

        function handleDurationMinutesChange(e) {
            var duration_minutes              = parseInt($('#input-duration_minutes').val(), 10);
            var mtt_late_reg_duration_minutes = parseInt($('#input-mtt_late_reg_duration_minutes').val(), 10);

            $('#example-duration_minutes').text(duration_minutes+" minutes");

            if (mtt_late_reg_duration_minutes >= duration_minutes) {
                mtt_late_reg_duration_minutes = duration_minutes-1;
            }

            $('#input-mtt_late_reg_duration_minutes').val(mtt_late_reg_duration_minutes);
            $('#example-mtt_late_reg_duration_minutes').text((mtt_late_reg_duration_minutes)+" minutes");
        }

        function updateRegistrationShow() {
            var format                    = "MMM DD HH:mm";
            var registration_opens        = $('#example-registration_opens').text();
            var mtt_show_hours_before     = parseInt($('#input-mtt_show_hours_before').val(), 10);
            var registration_opens_moment = moment(registration_opens, format);
            registration_opens_moment = registration_opens_moment.subtract(mtt_show_hours_before, 'hours')
            $('#example-registration_show').text(registration_opens_moment.format(format));
        }

        function getStartTimeMoment() {
            var format                 = "MMM DD HH:mm";
            var tournament_start       = $('#example-tournament_start').text();
            var $mtt_start_time_moment = moment(tournament_start, format);

            return $mtt_start_time_moment;
        }

        function updateTournamentEnd() {
            var out_format             = "MMM DD HH:mm";
            var $mtt_start_time_moment = getStartTimeMoment();

            // If date is not parsed properly, assume it's today.
            if (!$mtt_start_time_moment.isValid()) {
                var format             = "HH:mm:ss";
                var out_format         = "MMM DD HH:mm";
                var mtt_start_time     = $('#input-mtt_start_time').val();
                $mtt_start_time_moment = moment(mtt_start_time, format);
            }

            var duration_minutes   = parseInt($('#input-duration_minutes').val(), 10);
            $mtt_start_time_moment = $mtt_start_time_moment.add(duration_minutes, 'minutes')
            $('#example-tournament_end').text($mtt_start_time_moment.format(out_format));
        }

        function updateExampleFromInput(field) {
            var value = parseInt($('#input-'+field).val(), 10);
            $('#example-'+field).text(value);
        }

        function updateExamplePotCostFromInput() {
            var value = parseInt($('#input-pot_cost').val(), 10);
            $('#example-pot_cost').html("&euro; "+value);
        }

        function formModified() {
            updateCost();
            updatePlayerBalance();
        }

        $(document).ready(function() {

            //updateCost();
            updateRegistrationShow();
            updateTournamentEnd();

            $("#input-duration_minutes").change(handleDurationMinutesChange);
            $("#input-mtt_late_reg_duration_minutes").change(handleDurationMinutesChange);

            $(document).on('change', 'select', function() {
                formModified();
            });

            $(document).on('change keypress', 'input', function(e) {
                if (e.target.id == "input-total_cost" && !disableTotalCostUpdate) {
                    disableTotalCostUpdate = true;
                    $("#input-total_cost").css("background-color", "#ffffdd");
                }
                formModified();
            });

            /*
            $("#input-xspin_info").change(updateCost);
            $("#input-min_bet").change(updateCost);
            $("#input-house_fee").change(updateCost);
            $("#input-rebuy_house_fee").change(updateCost);
            $("#input-rebuy_cost").change(updateCost);
            $("#input-rebuy_times").change(updateCost);

            $("#input-spin_m").change(updatePlayerBalance);
            $("#input-cost").change(updatePlayerBalance);
            */

            $("#input-pot_cost").change(function(){
                updatePlayerBalance();
                updateExamplePotCostFromInput();
            });

            $("#input-mtt_show_hours_before").change(updateRegistrationShow);

            $("#input-duration_minutes").change(updateTournamentEnd);

            $("#input-min_players").change(function() { updateExampleFromInput('min_players'); });
            $("#input-max_players").change(function() { updateExampleFromInput('max_players'); });

            var timeOut = null;
            var countDown = null;
            var timeOutMS = 2000;

            function countDownTo0() {
                if (timeOutMS > 0) {
                    $('#update-example-btn').text('Update ['+(timeOutMS/1000)+'s]');
                    timeOutMS -= 1000;
                    countDown = setTimeout(countDownTo0, 1000);
                } else {
                    $('#update-example-btn').text('Update');
                }
            }

            $('#input-mtt_start_time,#input-mtt_recur_days,#select-category,#input-xspin_info,#input-min_bet,#input-max_bet,#input-spin_m,#input-bet_levels').change(function() {
                clearTimeout(countDown);
                timeOutMS = 2000;
                countDownTo0();

                clearTimeout(timeOut);
                timeOut = setTimeout(function() {
                    timeOut = null;
                    updateTournamentTemplateExample(getAllNonModalButtons());
                }, 2000);
            });

            $('.help-btn').on('click', function(e) {
                e.preventDefault();
                var $this = $(this);
                var $collapse = $this.closest('.form-group').find('.help-block');
                $collapse.collapse('toggle');
            });

            $('#delete-tournament-template-btn').on('click', function(e) {
                e.preventDefault();
            });
        });
    </script>
    @endpush

@endif

</div>