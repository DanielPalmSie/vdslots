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
        <div class="col-12 col-sm-12 col-lg-12">
            <div class="row">
                <div class="col-12 col-md-5 col-sm-5 col-lg-5">
                    <h4>Types:</h4>
                    <p><b>Deposit bonus</b> - Set Cost and Reward to 0, they will be calculated later as per the information above, don't forget to set <b>Rake percent</b> (see above).</p>
                    <p><b>Bust bonus</b> - The only difference between a bonus to be used as a bust bonus and a deposit bonus is that the bust lacks as deposit limit. The deposit treshold is multiplied with the Deposit multiplier to generate the initial bonus balance and reward. The Cost is generated from the reward/balance amount and the Rake percent value.</p>
                    <p><b>Custom bonus</b> - <b>Both Cost and Reward</b> needs to be set for this one to work properly. Note that setting <b>Bonus code</b> for this one doesn't make sense since <b>only an administrator can currently activate this type of bonus manually</b>.</p>
                    <p><b>Casino Wager</b> - Set bonus type to <b>casinowager</b> (but keep type as casino) to make a bonus that works like a normal deposit bonus but without the bonus balance. Set stagger percent to achieve a staggered payout of the reward (see Stagger percent above). Set Stagger percent to 0 for a one time full payout of the reward when the turnover goal has been reached.</p>
                </div>
                <div class="col-12 col-md-7 col-sm-7 col-lg-7">
                    <h4>Free Spins:</h4>
                    <p><b>Ext ids for Betsoft Gaming:</b> - Can be specific, ex: 125|456|321 or 456, note the separation by pipes, these are Ext game names. It can also be tags separated by commas, ex: videoslots,videopoker or videoslots. Cobinations are <b>not</b> possible atm, ex: 125|145,videoslots,videopoker.</p>
                    <p><b>Ext ids for Sheriff:</b> - Sheriff game id 1|sheriff game id 2| ... :coinvalue|winlines|linebet:spins. <b>Example</b>: 4|24:10|20|1:20 which would give 20 spins in Magoo and French Cuisine with a coinvalue of 10 cents with 20 winlines and a line bet of 1.</p>
                    <p><b>Ext ids for NetEnt:</b> - The promo code followed by a pipe and the bonus id in NetEnt's system, ex: starburst-welcome|2.</p>
                    <p><b>Ext ids for MicroGaming:</b> - The bonus code that can be found in Vanguard Admin.</p>
                    <p><b>Rake percent</b> - Works the same as in other bonuses but applies to potential wins made through the free spins in question.</p>
                    <p><b>Bonus tag</b> - Must be set, otherwise there will be fatal errors. <b><u>Very important!</u></b>. Currently <b>bsg</b> (short for Betsoft Gaming), <b>netent</b> and <b>microgaming</b> are possible.</p>
                    <p><b>Reward</b> - Will be the number of free spins in case the bonus is activated through a voucher or added as a normal bonus by admin. Deposit multiplier and Deposit limit work in a similar fashion to normal but will generate the amount of free spins instead. If the multiplier is set to 1 and the limit is set to 10000 then a deposit of 100 EUR would generate 100 free spins.</p>
                </div>
            </div>
        </div>
    </div>

    <hr />

    <div class="col-12 col-sm-12 col-lg-12">
        <form id="bonustype-form" class="" method="post">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            @if($bonustype)
                <input name="id" class="form-control" type="hidden" value="{{ $bonustype->id }}">
            @endif

            @foreach ($columns_order as $index => $co)
                @if ($index % 3 == 0)
                <div class="form-group row">
                @endif
                    <div class="col-sm-4">
                        <label class="col-form-label" for="{{ $co['column'] }}">
                            {{ ucwords(str_replace('_', ' ', $co['column'])) }}
                            @if(isset($co['tooltip']))
                            <span data-html="true" data-toggle="tooltip" title="{{ $co['tooltip'] }}" style="opacity:0.3;" class="badge bg-light-blue">?</span>
                            @endif
                            @if(isset($co['help-block']))
                                <button class="btn btn-xs help-btn">Learn more...</button>
                            @endif
                        </label>

                        @if ($co['type'] == "select2")
                            <select id="select-{{ $co['column'] }}"
                                @if($co['readonly'])
                                    readonly
                                @endif
                                name="{{ $co['column'] }}"
                                class="form-control select2-class select-{{ $co['column'] }}" style="width: 100%;"
                                data-placeholder="No {{ ucwords(str_replace('_', ' ', $co['column'])) }} specified"
                                data-allow-clear="true"
                            >
                                @foreach ($all_distinct[$co['column']] as $c)
                                    <option class="0" value="{{ is_array($c) && isset($c['key']) ? $c['key'] : $c }}"
                                            @if ($bonustype->{$co['column']} !== null && $bonustype->{$co['column']} == (is_array($c) && isset($c['key']) ? $c['key'] : $c))
                                                selected="true"
                                            @endif>{{ is_array($c) && isset($c['name']) ? $c['name'] : $c }}
                                    </option>
                                @endforeach
                            </select>
                        @elseif ($co['type'] == "select2_filter")
                            <select id="select-{{ $co['column'] }}"
                                @if($co['readonly'])
                                    readonly
                                @endif
                                 name="{{ $co['column'] }}" class="form-control select2-class select-{{ $co['column'] }}" style="width: 100%;" data-placeholder="No {{ ucwords(str_replace('_', ' ', $co['column'])) }} specified" data-allow-clear="true">
                                @if (strlen($bonustype->{$co['column']}) > 0)
                                    <option value="{{ $bonustype->{$co['column']} }}" selected="true">{{ $bonustype->{$co['column']} }}</option>
                                @endif
                            </select>
                        @elseif ($co['type'] == "select2_multiselect")
                            <select id="select-{{ $co['column'] }}"
                                @if($co['readonly'])
                                    readonly
                                @endif
                                 name="{{ $co['column'] }}[]" class="form-control select2-class select-{{ $co['column'] }}" style="width: 100%;" data-placeholder="No {{ ucwords(str_replace('_', ' ', $co['column'])) }} specified" data-allow-clear="true" multiple="multiple">
                                @if (strlen($bonustype->{$co['column']}) > 0)
                                    @foreach (explode($co['split_with'], $bonustype->{$co['column']}) as $a)
                                        <option value="{{ $a }}" selected="true">{{ $a }}</option>
                                    @endforeach
                                @endif
                                @if ($co['column'] == 'ext_ids')
                                    @foreach(\App\Helpers\DataFormatHelper::getExternalGameNameList() as $ext_id)
                                        <option value="{{ $ext_id['ext_game_name'] }}"> {{ $ext_id['ext_game_name'] }} </option>
                                    @endforeach
                                @elseif ($co['column'] == 'game_id')
                                    @foreach(\App\Helpers\DataFormatHelper::getGameIdList() as $game_id)
                                        <option value="{{ $game_id['game_id'] }}"> {{ $game_id['game_id'] }} </option>
                                    @endforeach
                                @endif
                            </select>
                        @elseif ($co['type'] == "date")
                            <input id="input-{{ $co['column'] }}" data-provide="datepicker" data-date-format="yyyy-mm-dd"
                            @if($co['readonly'])
                                readonly
                            @endif
                                name="{{ $co['column'] }}" class="form-control" type="text"
                            @if ($bonustype)
                                value="{{ $bonustype->{$co['column']} }}"
                            @else
                                value=""
                            @endif
                            >
                        @elseif ($co['type'] == "boolean")
                            <input id="input-{{ $co['column'] }}"
                            @if($co['readonly'])
                                readonly
                            @endif
                            @if($co['on_label'])
                                data-on="{{ $bonustype->{$co['on_label']} }}"
                            @else
                                data-on="Visible"
                            @endif
                            @if($co['off_label'])
                                data-off="{{ $bonustype->{$co['off_label']} }}"
                            @else
                                data-off="Hidden"
                            @endif
                                name="{{ $co['column'] }}" class="form-control" data-toggle="toggle" data-onstyle="success" data-offstyle="danger" type="checkbox"
                                @if(isset($co['checked']) && $co['checked'] == 1)
                                    checked
                                @endif
                            @if ($bonustype)
                                value="{{ $bonustype->{$co['column']} }}" @if ($bonustype->{$co['column']}) checked @endif
                            @else
                                value=""
                            @endif
                            >
                            @elseif ($co['type'] == "boolean_on_off")
                                <input id="input-{{ $co['column'] }}"
                                @if($co['readonly'])
                                    readonly
                                @endif
                                    name="{{ $co['column'] }}" class="form-control" data-on="Enable" data-off="Disable" data-toggle="toggle" data-onstyle="success" data-offstyle="danger" type="checkbox"
                                @if ($bonustype)
                                    value="{{ $bonustype->{$co['column']} }}" @if ($bonustype->{$co['column']}) checked @endif
                                @else
                                    value=""
                                @endif
                            >
                        @elseif ($co['type'] == "modal")
                            <div class="input-group">
                                <input id="input-{{ $co['column'] }}"
                                data-d="{{ $co['readonly'] }}"
                                    readonly
                                    name="{{ $co['column'] }}" class="form-control" type="{{ $co['type'] }}"
                                @if ($bonustype)
                                    value="{{ $bonustype->{'ext_ids'} }}"
                                @else
                                    value=""
                                @endif
                                >
                                <div class="input-group-btn">
                                    <button id="input-{{ $co['column'] }}-edit" class="btn btn-primary" type="button" data-toggle="modal" data-target="#edit-{{ $co['column'] }}">Edit {{ $co['column'] }}...</button>
                                </div>
                            </div>

                        @else
                            <input id="input-{{ $co['column'] }}"
                            data-d="{{ $co['readonly'] }}"
                            @if($co['readonly'])
                                readonly
                            @endif
                                name="{{ $co['column'] }}" class="form-control" type="{{ $co['type'] }}"
                            @if ($bonustype)
                                value="{{ $bonustype->{$co['column']} }}"
                            @else
                                @if ($co['column'] == 'reward')
                                    value="5,10,15"
                                @endif
                            @endif
                            >
                        @endif

                        @if(isset($co['help-block']))
                            <p class="collapse help-block">{!! nl2br($co['help-block']) !!}</p>
                        @endif
                    </div>
                @if ($index % 3 == 2)
                </div>
                @endif
            @endforeach
                <div class="col-sm-4">
                    <label class="col-form-label" for="auto_activate_bonus_send_out_time">Send Out Time (GMT)</label>
                    <div class="input-group bootstrap-timepicker timepicker date" id="start_time_picker" data-target-input="nearest" data-toggle="datetimepicker">
                        <input
                            id="input-racetemplates-start_time"
                            name="auto_activate_bonus_send_out_time"
                            class="form-control datetimepicker-input"
                            data-target="#start_time_picker"
                            type="text"
                               @if ($bonustype->auto_activate_bonus_send_out_time)
                                   value="{{ $bonustype->auto_activate_bonus_send_out_time }}"
                               @else
                                   value=""
                                @endif
                        >
                        <div class="input-group-append" data-target="#start_time_picker" data-toggle="datetimepicker">
                            <div class="input-group-text">
                                <i class="fa fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>

            @if (count($columns_order)-1 % 3 != 2) <!-- Add last missing closing tag, if needed. -->
                </div>
            @endif
        </form>
    </div>

    <div id="button-footer">
        <div class="form-group row">
            <!--<div class="col-sm-6 col-sm-offset-4">-->
            <div class="col-sm-12 text-center">
                @if ($buttons['save'])
                    <button id="save-bonustype-btn" class="btn btn-primary">
                        {{ $buttons['save'] }}
                    </button>
                @endif
                @if ($buttons['save-as-new'])
                    &nbsp; | &nbsp;
                    <button id="save-as-new-bonustype-btn" class="btn btn-info">
                        {{ $buttons['save-as-new'] }}
                    </button>
                @endif
                @if ($buttons['delete'])
                    &nbsp; | &nbsp;
                    <button id="delete-bonustype-btn" class="btn btn-danger" data-toggle="modal" data-target="#confirm-delete">
                        {{ $buttons['delete'] }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-12 col-lg-12">
        @if ($buttons['save-all'])
        <hr />
        <div class="text-center">
            <button id="save-all-btn" class="btn btn-primary">{{ $buttons['save-all'] }}</button>
        </div>
        @endif
    </div>

    <div class="modal fade" id="edit-ext_ids_override" tabindex="-1" role="dialog" aria-labelledby="myModalEditPrizesLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalEditPrizesLabel">Edit Ext Ids</h4>
                </div>

                <div class="modal-body">
                    <div class="">
                        <div>
                            <table id="edit-extids-table" class="table compact-table text-center">
                                <thead>
                                    <tr>
                                        <th id="levels-table-column-header">
                                            Country
                                        </th>
                                        <th>Bonus Id</th>
                                        <th>Interaction</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <tr>
                                        <td class="td-template-edit-levels">
                                            <div class="form-group">
                                                <span class="select-span-wrapper">
                                                    <select name="countries[]" class="form-control select2-class select-config_value select2 select-extid-countries" style="width: 100%;" data-placeholder="No country specified" data-allow-clear="false" data-minimum-results-for-search="Infinity">
                                                        @foreach (array_merge(phive('Licensed')->getSetting('licensed_countries'), ['ROW' => 'ROW']) as $country)
                                                            <option value="{{ $country }}">{{ $country }}</option>
                                                        @endforeach
                                                    </select>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="form-group">
                                                <input
                                                    name="bonuses[]"
                                                    value=""
                                                    class="form-control input-ext-bonus"
                                                    type="text"
                                                />
                                            </div>
                                        </td>
                                        <td style="width: 120px;">
                                            <button class="btn btn-sm btn-primary clone-edit-levels-btn clone-extid-entry-btn"><i class="fa fa-copy"></i></button>
                                            <button class="btn btn-sm btn-danger remove-edit-levels-btn remove-extid-entry-btn"><i class="fa fa-remove"></i></button>
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
                    <button id="apply-extids-modalbtn" class="btn btn-success" data-dismiss="modal">Apply</button>
                </div>
            </div>
        </div>
    </div>
@endif

</div>

@section('footer-javascript')
    @parent
    @include('admin.gamification.trophyawards.partials.trophyawardsharedjs')

    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {


            $('#excluded_countries').select2({
                selectOnBlur: true,
                multiple: true,
                tags: true,
                allowClear: true,
            });
            $('#excluded_countries').val({!! json_encode($trophyaward->excluded_countries ?? ($new_trophy ? ['SE', 'DK'] : [])) !!});
            $('#excluded_countries').change();

            $('#delete-trophyaward-btn').on('click', function(e) {
                e.preventDefault();
            });
        });

        $('#input-reward').on('input', function() {
            var value = $(this).val();
            $(this).val(value.replace(/[^0-9,]/g, ''));
            toggleSaveButton();
        });

        function toggleSaveButton() {
            console.log("ASDASD");
            var value = $('#input-reward').val();
            if (value.indexOf(',') !== -1) {
                $('#save-bonustype-btn').prop('disabled', true);
            } else {
                $('#save-bonustype-btn').prop('disabled', false);
            }
        }

    </script>
@endsection
