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
                    <p>You are about to delete the Race Template. This procedure is irreversible.</p>
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

    <div class="modal fade" id="edit-prizes" tabindex="-1" role="dialog" aria-labelledby="myModalEditPrizesLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalEditPrizesLabel">Edit Prizes</h4>
                </div>

                <div class="modal-body">
                    <div class="">
                        <div>
                            <table id="edit-prizes-table" class="table compact-table text-center">
                                <thead>
                                    <tr>
                                        <th id="prizes-table-column-header">
                                            @if ($racetemplate->prize_type == "award")
                                                Award
                                            @else
                                                Amount
                                            @endif
                                        </th>
                                        <th id="prizes-table-column-header-award_alt">Award Alt</th>
                                        <th>Start Position</th>
                                        <th>End Position</th>
                                        <th>Interaction</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr id="tr-template-edit-prizes">
                                        <td>
                                            <div class="form-group">
                                                    <div class="racetemplate-prize-select2">
                                                        <div class="row">
                                                            <div class="col-sm-12">
                                                                <select name="prize[]" class="form-control select2-class select-racetemplate-award_id" style="width: 100%;" data-placeholder="No Award specified" data-allow-clear="true">
                                                                    <option value=""></option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-sm-12">
                                                            Alias: <span class="span-racetemplate-alias"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <input
                                                        name="prize[]"
                                                        value=""
                                                        class="form-control racetemplate-prize-input"
                                                        type="number"
                                                    />
                                            </div>
                                        </td>
                                        <td class="racetemplate-prize_alt-td">
                                            <div class="form-group">
                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <select name="prize_alt[]" class="form-control select2-class select-racetemplate-award_id_alt" style="width: 100%;" data-placeholder="No Award Alt specified" data-allow-clear="true">
                                                            <option value=""></option>
                                                        </select>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-12">
                                                        Alias: <span class="span-racetemplate-alias"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group">
                                                <input
                                                    name="start_position[]"
                                                    value="1"
                                                    class="form-control"
                                                    type="number"
                                                />
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group">
                                                <input
                                                    name="end_position[]"
                                                    value="1"
                                                    class="form-control"
                                                    type="number"
                                                />
                                            </div>
                                        </td>
                                        <td style="width: 120px;">
                                            <button class="btn btn-sm btn-primary clone-edit-prize-btn"><i class="fa fa-copy"></i></button>
                                            <button class="btn btn-sm btn-default clear-edit-prize-btn"><i class="fa fa-file-o"></i></button>
                                            <button class="btn btn-sm btn-default undo-edit-prize-btn"><i class="fa fa-undo"></i></button>
                                            <button class="btn btn-sm btn-danger remove-edit-prize-btn"><i class="fa fa-remove"></i></button>
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <span id="edit-prizes-error-info" class="text-danger"></span>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button id="apply-edit-prizes-modalbtn" class="btn btn-success" data-dismiss="modal">Apply</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-levels" tabindex="-1" role="dialog" aria-labelledby="myModalEditPrizesLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalEditPrizesLabel">Edit Prizes</h4>
                </div>

                <div class="modal-body">
                    <div class="">
                        <div>
                            <table id="edit-levels-table" class="table compact-table text-center">
                                <thead>
                                    <tr>
                                        <th id="levels-table-column-header">
                                            @if ($racetemplate->race_type == "bigwin")
                                                Threshold
                                            @else
                                                Cents
                                            @endif
                                        </th>
                                        <th>Points</th>
                                        <th>Interaction</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr>
                                        <td class="td-template-edit-levels">
                                            <div class="form-group">
                                                <span class="select-span-wrapper">
                                                    <select name="threshold" class="form-control select2-class select-config_value select2 select-levels_threshold" style="width: 100%;" data-placeholder="No threshold specified" data-allow-clear="false" data-minimum-results-for-search="Infinity">
                                                        @foreach ($racetemplates_all_distinct['levels_threshold'] as $name => $value)
                                                            <option value="{{ $value }}">{{ $name }}</option>
                                                        @endforeach
                                                    </select>
                                                </span>
                                                <input
                                                    name="cents[]"
                                                    value=""
                                                    class="form-control"
                                                    type="number"
                                                />
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group">
                                                <input
                                                    name="points[]"
                                                    value=""
                                                    class="form-control"
                                                    type="number"
                                                />
                                            </div>
                                        </td>
                                        <td style="width: 120px;">
                                            <button class="btn btn-sm btn-primary clone-edit-levels-btn"><i class="fa fa-copy"></i></button>
                                            <button class="btn btn-sm btn-default clear-edit-levels-btn"><i class="fa fa-file-o"></i></button>
                                            <button class="btn btn-sm btn-default undo-edit-levels-btn"><i class="fa fa-undo"></i></button>
                                            <button class="btn btn-sm btn-danger remove-edit-levels-btn"><i class="fa fa-remove"></i></button>
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <span id="edit-levels-error-info" class="text-danger"></span>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button id="apply-edit-levels-modalbtn" class="btn btn-success" data-dismiss="modal">Apply</button>
                </div>
            </div>
        </div>
    </div>


    <!-- TODO: Use getColumnsOrder()? -->
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-6">
            <form id="racetemplate-form" class="" method="post">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                @if($racetemplate)
                    <input name="id" class="form-control" type="hidden" value="{{ $racetemplate->id }}">
                @endif
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="race_type">Race Type</label>
                    <div class="col-sm-9">
                        <select id="select-racetemplates-race_type" name="race_type" class="form-control select2-class select-racetemplates-race_type" style="width: 100%;" data-placeholder="No Race Type specified" data-allow-clear="true">
                            @foreach ($racetemplates_all_distinct['race_type'] as $t)
                                @if ($racetemplate->race_type == $t)
                                    <option value="{{ $t }}" selected="true">{{ $t }}</option>
                                @else
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="display_as">Display As</label>
                    <div class="col-sm-9">
                        <select id="select-racetemplates-display_as" name="display_as" class="form-control select2-class select-racetemplates-display_as" style="width: 100%;" data-placeholder="No Display As specified" data-allow-clear="true">
                            @foreach ($racetemplates_all_distinct['display_as'] as $t)
                                @if ($racetemplate->display_as == $t)
                                    <option value="{{ $t }}" selected="true">{{ $t }}</option>
                                @else
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="levels">Levels</label>
                    <div class="col-sm-9">
                        <div class="input-group">
                        @if($racetemplate)
                        <input id="input-racetemplates-levels" name="levels" class="form-control" type="text" value="{{ $racetemplate->levels }}" readonly>
                        @else
                        <input id="input-racetemplates-levels" name="levels" class="form-control" type="text" value="" readonly>
                        @endif
                            <div class="input-group-btn">
                                <button id="input-racetemplate-edit-levels" class="btn btn-primary btn-flat text-md" type="button" data-toggle="modal" data-target="#edit-levels">Edit Levels...</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="prizes">Prizes</label>
                    <div class="col-sm-9">
                        <div class="input-group">
                        @if($racetemplate)
                            <input id="input-racetemplates-prizes" name="prizes" class="form-control" type="text" value="{{ $racetemplate->prizes }}" readonly >
                        @else
                            <input id="input-racetemplates-prizes" name="prizes" class="form-control" type="text" value="" readonly>
                        @endif
                            <div class="input-group-btn">
                                <button id="input-racetemplate-edit-prices" class="btn btn-primary btn-flat text-md" type="button" data-toggle="modal" data-target="#edit-prizes">Edit Prizes...</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="prize_type">Price Type</label>
                    <div class="col-sm-9">
                        <select id="select-racetemplates-prize_type" name="prize_type" class="form-control select2-class select-racetemplates-prize_type" style="width: 100%;" data-placeholder="No Price Type specified" data-allow-clear="false">
                            @foreach ($racetemplates_all_distinct['prize_type'] as $t)
                                @if ($racetemplate->prize_type == $t)
                                    <option value="{{ $t }}" selected="true">{{ $t }}</option>
                                @else
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="game_categories">Game Categories</label>
                    <div class="col-sm-9">
                        <select id="select-racetemplates-game_categories" name="game_categories[]" multiple="multiple" class="form-control select2-class select-racetemplates-game_categories" style="width: 100%;" data-placeholder="No Game Category specified" data-allow-clear="true">
                            @foreach ($racetemplates_all_distinct['game_categories'] as $t)
                                @if (in_array($t, $racetemplate->game_categories))
                                    <option value="{{ $t }}" selected="true">{{ $t }}</option>
                                @else
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="games">Games</label>
                    <div class="col-sm-9">
                        @if($racetemplate)
                        <input id="input-racetemplates-games" name="games" class="form-control" type="text" value="{{ $racetemplate->games }}">
                        @else
                        <input id="input-racetemplates-games" name="games" class="form-control" type="text" value="">
                        @endif
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="recur_type">Recurrance Type</label>
                    <div class="col-sm-9">
                        <select id="select-racetemplates-recur_type" name="recur_type" class="form-control select2-class select-racetemplates-recur_type" style="width: 100%;" data-placeholder="No Game Category specified" data-allow-clear="true">
                            @foreach ($racetemplates_all_distinct['recur_type'] as $t)
                                @if (in_array($t, $racetemplate->recur_type))
                                    <option value="{{ $t }}" selected="true">{{ $t }}</option>
                                @else
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="start_time">Start Time</label>
                    <div class="col-sm-9">
                        <div class="input-group date" id="start_time_picker" data-target-input="nearest" data-toggle="datetimepicker">
                            <input
                                type="text"
                                id="input-racetemplates-start_time"
                                name="start_time"
                                class="form-control datetimepicker-input"
                                data-target="#start_time_picker"
                                @if ($racetemplate)
                                    value="{{ $racetemplate->start_time }}"
                                @else
                                    value=""
                                @endif
                            />
                            <div class="input-group-append" data-target="#start_time_picker" data-toggle="datetimepicker">
                                <div class="input-group-text">
                                    <i class="fa fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="start_date">Start Date</label>
                    <div class="col-sm-9">
                        <input id="input-racetemplates-start_date" data-provide="datepicker" data-date-format="yyyy-mm-dd" name="start_date" class="form-control" type="text"
                        @if ($racetemplate)
                            value="{{ $racetemplate->start_date }}"
                        @else
                            value=""
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="recurring_days">Recurring Days</label>
                    <div class="col-sm-9">
                        <select id="select-racetemplates-recurring_days" name="recurring_days[]" multiple="multiple" class="form-control select2-class select-racetemplates-prize_type" style="width: 100%;" data-placeholder="No Recurring Day specified" data-allow-clear="true">
                            @foreach ($racetemplates_all_distinct['recurring_days'] as $day => $number)
                                @if (in_array($number, $racetemplate->recurring_days))
                                    <option value="{{ $number }}" selected="true">{{ $day }}</option>
                                @else
                                    <option value="{{ $number }}">{{ $day }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="recurring_end_date">Recurring End Date</label>
                    <div class="col-sm-9">
                        <input id="input-racetemplates-recurring_end_date" name="recurring_end_date" class="form-control datetimepicker" type="text"
                        @if ($racetemplate)
                            value="{{ $racetemplate->recurring_end_date }}"
                        @else
                            value=""
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="duration_minutes">Duration Minutes</label>
                    <div class="col-sm-9">
                        <input id="input-racetemplates-duration_minutes" name="duration_minutes" class="form-control" type="number" step="1"
                        @if ($racetemplate)
                            value="{{ $racetemplate->duration_minutes }}"
                        @else
                            value=""
                        @endif
                        >
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-sm-6 col-sm-offset-3">
                        @if ($buttons['save'])
                            <button class="btn btn-primary" id="save-racetemplate-btn">
                                {{ $buttons['save'] }}
                            </button>
                        @endif
                        @if ($buttons['save-as-new'])
                            &nbsp; | &nbsp;
                            <button class="btn btn-info" id="save-as-new-racetemplate-btn">
                                {{ $buttons['save-as-new'] }}
                            </button>
                        @endif
                        @if ($buttons['delete'])
                            &nbsp; | &nbsp;
                            <button class="btn btn-danger" id="delete-racetemplate-btn" data-toggle="modal" data-target="#confirm-delete">
                                {{ $buttons['delete'] }}
                            </button>
                        @endif
                    </div>
                </div>
            </form>

        </div>

        <div class="col-12 col-sm-6 col-lg-6">

            <div class="row">
                <div class="col-12 col-sm-12 col-lg-12">
                    <h4>Race Template Types:</h4>
                    <p><b>race_type</b> can be <b>spins</b> or <b>bigwin</b>.</p>
                    <p><b>display_as</b> can currently only be <b>race</b>.</p>
                    <p><b>levels</b> is specifying amount in <b>cents</b> that has to be bet, or what type of <b>threshold</b> that has to be met, and how many <b>points</b> that corresponds too. This depends on <b>race_type</b>.</p>
                    <p><b>prizes</b> will depend on <b>prize_type</b>. So, either this is a list, separated by colon (:) specifying cash, or awards (and award alternative, separated with a comma) which is given out at certain positions.</p>
                    <p><b>prize_type</b> can be either <b>cash</b> or <b>award</b>.</p>
                    <p><b>game_categories</b> is what game categories this applies to. Normally it's both <b>slots</b> and <b>videoslots</b>.</p>
                    <p><b>games</b> is not used yet.</p>
                    <p><b>reccurrance_type</b> currently only is <b>weekly</b>.</p>
                    <p><b>start_time</b> is the time when the race should start.</p>
                    <p><b>start_date</b> is the date when the race should start.</p>
                    <p><b>recurring_days</b> is a comma seperated list of what days the race should accur on.</p>
                    <p><b>reccuring_end_date</b> is a the date and time when the race should end.</p>
                    <p><b>duration_minutes</b> is the duration the race should last, specified in minutes.</p>
                </div>

            </div>

        </div>
    </div>

    <div class="row">
        <div class="col-12 col-sm-12 col-lg-12">
            @if ($buttons['save-all'])
                <hr />
                <div class="text-center">
                    <button class="btn btn-primary" id="save-all-btn">{{ $buttons['save-all'] }}</button>
                </div>
            @endif
        </div>
    </div>

    @push('extrajavascript')
    <script type="text/javascript">

        $(document).ready(function() {

        });

    </script>
    @endpush

    @endif

</div>
