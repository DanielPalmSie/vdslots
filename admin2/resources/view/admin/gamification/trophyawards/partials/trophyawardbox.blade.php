<?php $new_trophy = empty($trophy->id); ?>

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
                    <p>You are about to delete the Trophie Award. This procedure is irreversible.</p>
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
        <form id="trophyaward-form" class="" method="post">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            @if($trophyaward)
                <input name="id" class="form-control" type="hidden" value="{{ $trophyaward->id }}">
            @endif
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="alias">Alias  <span data-toggle="tooltip" title="Need to be unique." class="badge bg-lightblue">?</span></label>
                <div class="col-sm-9">
                    @if($trophyaward)
                    <input id="input-trophyawards-alias" data-uniqueid="{{ $trophyaward->id }}" name="alias" class="form-control" type="text" value="{{ $trophyaward->alias }}">
                    @else
                    <input id="input-trophyawards-alias" data-uniqueid="" name="alias" class="form-control" type="text" value="">
                    @endif
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="description">Description <span data-toggle="tooltip" title="Used in drop downs." class="badge bg-lightblue">?</span></label>
                <div class="col-sm-9">
                    @if($trophyaward)
                    <input id="input-trophyawards-description" name="description" class="form-control" type="text" value="{{ $trophyaward->description }}">
                    @else
                    <input id="input-trophyawards-description" name="description" class="form-control" type="text" value="">
                    @endif
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="type">Type</label>
                <div class="col-sm-9">
                    <select id="select-trophyawards-type" name="type" class="form-control select-trophyawards-type" style="width: 100%;" data-placeholder="No Type specified" data-allow-clear="true">
                        @foreach ($trophyawards_all_distinct['type'] as $t)
                            @if ($trophyaward->type == $t)
                                <option value="{{ $t }}" selected="true">{{ $t }}</option>
                            @else
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="multiplicator">Multiplicator</label>
                <div class="col-sm-9">
                    <input id="input-trophyawards-multiplicator" name="multiplicator" class="form-control" type="number" step=".01"
                    @if ($trophyaward)
                        value="{{ $trophyaward->multiplicator }}"
                    @else
                        value=""
                    @endif
                    >
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="amount">Amount</label>
                <div class="col-sm-9">
                    <input id="input-trophyawards-amount" name="amount" class="form-control" type="number"
                    @if ($trophyaward)
                        value="{{ $trophyaward->amount }}"
                    @else
                        value=""
                    @endif
                    >
                </div>
            </div>
            <div class="form-group row" id="bonus_parent">
                <label class="col-sm-3 col-form-label" for="bonus_id">Bonus Id</label>
                <div class="col-sm-9">
                    <div class="row">
                        <div class="col-sm-12">
                            <select id="select-trophyawards-award_id" name="bonus_id" class="form-control select2-class select-trophyawards-award_id" style="width: 100%;" data-placeholder="No Bonus specified" data-allow-clear="true">
                                @if ($trophyaward_bonus)
                                <option value="{{ $trophyaward_bonus->id }}">{{ $trophyaward_bonus->bonus_name }}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            Bonus Type: <span id="span-trophyawards-bonus_id_bonus_type">@if($trophyaward_bonus){{ $trophyaward_bonus->bonus_type }}@else[no bonus type]@endif</span>
                                &nbsp; | &nbsp;
                            Bonus Code: <span id="span-trophyawards-bonus_id_bonus_code">@if($trophyaward_bonus){{ $trophyaward_bonus->bonus_code }}@else[no bonus code]@endif</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group row" id="template_parent" style="display: none;">
                <label class="col-sm-3 col-form-label" for="bonus_id">Tournament Template Id</label>
                <div class="col-sm-9">
                    <div class="row">
                        <div class="col-sm-12">
                            <select id="" name="bonus_id" class="form-control" style="width: 100%;">
                                @if(!empty($tournament))
                                    <option value="{{$tournament['id']}}">{{$tournament['text']}}</option>
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="valid_days">Valid Days <span data-toggle="tooltip" title="Controls the amount of time the award can be owned without being activated before it expires and the amount of time the award is active while being owned." class="badge bg-lightblue">?</span></label>
                <div class="col-sm-9">
                    <input id="input-trophyawards-valid_days" name="valid_days" class="form-control" type="number"
                    @if ($trophyaward)
                        value="{{ $trophyaward->valid_days }}"
                    @else
                        value=""
                    @endif
                    >
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="own_valid_days">Own Valid Days <span data-toggle="tooltip" title="Own valid days currently applies to race-multiply and Weekend Booster-multiply awards." class="badge bg-lightblue">?</span></label>
                <div class="col-sm-9">
                    <input id="input-trophyawards-own_valid_days" name="own_valid_days" class="form-control" type="number"
                    @if ($trophyaward)
                        value="{{ $trophyaward->own_valid_days }}"
                    @else
                        value=""
                    @endif
                    >
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="created_at">Created At</label>
                <div class="col-sm-9">
                    <input id="input-trophyawards-created_at" data-provide="datepicker" data-date-format="yyyy-mm-dd" name="created_at" class="form-control" type="text" disabled
                    @if ($trophyaward)
                        value="{{ $trophyaward->created_at }}"
                    @else
                        value=""
                    @endif
                    >
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="action">Action</label>
                <div class="col-sm-9">
                    <select id="select-trophyawards-action" name="action" id="select-action" class="form-control select2-class select-trophyawards-action" style="width: 100%;" data-placeholder="No Action specified" data-allow-clear="true">
                        @foreach ($trophyawards_all_distinct['action'] as $t)
                            @if ($trophyaward->action == $t)
                                <option value="{{ $t }}" selected="true">{{ $t }}</option>
                            @else
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="bonus_code">Bonus Code</label>
                <div class="col-sm-9">
                    <input id="input-trophyawards-bonus_code" name="bonus_code" class="form-control" type="text"
                    @if ($trophyaward)
                        value="{{ $trophyaward->bonus_code }}"
                    @else
                        value=""
                    @endif
                    >
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="mobile_show">Mobile Show</label>
                <div class="col-sm-9">
                    <input id="input-trophyawards-mobile_show" name="mobile_show" class="form-control" data-on="Yes" data-off="No" data-toggle="toggle" data-onstyle="success" data-offstyle="danger" type="checkbox"
                    @if ($trophyaward)
                        value="{{ $trophyaward->mobile_show }}" @if ($trophyaward->mobile_show) checked @endif
                    @else
                        value=""
                    @endif
                    >
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="jackpots_id">Jackpot Id / Jackpot Wheel Id <span data-toggle="tooltip" title="Needs to contain the JP Wheel Id (or empty, in that case see the detailed description to the right) for 'wheel-of-jackpots' type or the Jackpot Id in case of 'jackpot' type, otherwise leave it empty." class="badge bg-lightblue">?</span></label>
                <div class="col-sm-9">
                    <input name="jackpots_id" class="form-control" type="text"
                    @if ($trophyaward)
                        value="{{ $trophyaward->jackpots_id }}"
                    @else
                        value=""
                    @endif
                    >
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label" for="excluded_countries">Excluded countries<span data-toggle="tooltip" title="Excluded countries" class="badge bg-lightblue">?</span></label>
                <div class="col-sm-9">
                    <select class="form-control select2-class" name="excluded_countries[]" id="excluded_countries">
                        @foreach ($trophyawards_all_distinct['excluded_countries'] as $c)
                            <option value="{{ $c['id'] }}">{{ $c['text'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-6 col-sm-offset-3">
                    @if ($buttons['save'])
                        <button class="btn btn-primary" id="save-trophyaward-btn">
                            {{ $buttons['save'] }}
                        </button>
                    @endif
                    @if ($buttons['save-as-new'])
                        &nbsp; | &nbsp;
                        <button class="btn btn-info" id="save-as-new-trophyaward-btn">
                            {{ $buttons['save-as-new'] }}
                        </button>
                    @endif
                    @if ($buttons['delete'])
                        &nbsp; | &nbsp;
                        <button class="btn btn-danger" id="delete-trophyaward-btn" data-toggle="modal" data-target="#confirm-delete">
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
                    <h4>Trophy Award Types:</h4>
                    <p><b>top-up</b> is free cash being awarded on next deposit. Example: A multiplicator value of 1.5 tops up a 20 EUR deposit with an extra 10 EUR. The <i>amount</i> value is used to set a cap on how much money that can be topped up. Example: Setting it to 500 will cap the top up to 5 EUR or 50 SEK.</p>
                    <p><b>race-spins</b> adds the value in amount to the player's spins in all active races the player is participating in.</p>
                    <p><b>race-multiply</b> <i>amount</i> is the amount of spins being multiplied, after that many spins have been spun the multiplicator stops multiplying. Multiplicator is the multiplicator, setting it to 2 results in 2 spins being recorded for a 25 cent bet.</p>
                    <p><b>cashback-multiply</b> is the same as <b>race-multiply</b>, but applies to the amount of Weekend Booster being given for X number of spins.</p>
                    <p><b>xp-multiply</b> multiplies the amount of xp given by the value in <i>multiplicator</i>, is permanent but unique which means that if one award sets the value to 1.5 and a subsequent award sets it to 2 the resulting XP multiplication that the player enjoys is 2, not 3.5.</p>
                    <p><b>deposit</b>, if deposit bonus, the bonus id field needs to be set.</p>
                    <p><b>freespin</b>, if freespin bonus, the bonus id field needs to be set.</p>
                    <p><b>jackpot</b>, if jackpot, the Jackpot Id / Jackpot Wheel Id field needs to be set with the ID of the jackpot.</p>
                    <p><b>wheel-of-jackpots</b>, the wheel id needs to be set in the Jackpot Id / Jackpot Wheel Id field in order to force a custom wheel. <strong>If the wheel id field is left empty</strong> the logic will work as follows:<br><br>
                        1.) We first look for a wheel which matches the player's country and use that wheel if it is found.<br>
                        <br>
                        2.) If no wheel could be found based on country we default to the wheel with the country ALL. <strong>It is therefore extremely important that this wheel exists!</strong><br>
                        <br>
                        With that in mind an award with an empty wheel id of the type wheel-of-jackpots <strong>must be created</strong> and used with trophies in order to use this default behaviour to generate proper wheels that work for different countries (ie FRBs in games blocked for Canadians should not be a part of a wheel displayed for a Canadian).<br>
                        <br>
                    </p>
                </div>
            </div>

            <hr />

            <div class="row">
                <div class="col-12 col-sm-6 col-lg-3">
                    @if ($trophyaward)
                        <img class="img-responsive img-thumbnail alias_thumbnail_{{ $trophyaward->id }}" src="{{ $trophyaward->img }}" title="{{ $trophyaward->img }}">
                    @else
                        <img class="img-responsive img-thumbnail alias_thumbnail_{{ $trophyaward->id }}" src="{{ getMediaServiceUrl() }}/file_uploads/events/noname.png" title="">
                    @endif
                </div>
                <div class="col-12 col-sm-6 col-lg-9">
                    <div id="dropzone">
                        <form action="{{ $app['url_generator']->generate('trophyawards.fileupload') }}" class="dropzone" id="trophyawards-images-upload">
                            <div class="dz-message">
                                Drop image file here or click to select file to upload.<br/>
                            </div>
                        </form>
                    </div>
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
    @endif
</div>
@section('footer-javascript')
    @parent
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

            var is_mp_ticket = false;
            var ticket_type = {!! json_encode(\App\Models\TrophyAwards::TICKET_TYPES) !!};

            $('#select-trophyawards-type').on('change', function () {
                if (ticket_type.indexOf($(this).val()) > -1) {
                    is_mp_ticket = true;
                    $("#bonus_parent").hide();
                    $("#template_parent").show();
                } else {
                    is_mp_ticket = false;
                    $("#bonus_parent").show();
                    $("#template_parent").hide();
                }
            }).change();

            function formatOption (el) {
                return el.text;
            }
            $('#template_parent select').select2({
                ajax: {
                    url: '{{ $app['url_generator']->generate('trophyawards.search.tournament-templates') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term, // search term
                            search: params.term, // search term
                            page: params.page
                        };
                    },
                    processResults: function (data, params) {
                        return {
                            results: $.map(data, function(data) {
                                return { id: data.id, text: data.text };
                            })
                        };
                    },
                    cache: true
                },
                allowClear: true,
                placeholder: 'Input a tournament template Id',
                minimumInputLength: 1,
                multiple: false,
                templateResult: formatOption,
                templateSelection: formatOption
            });

            $("form").submit(function (e) {
                if (is_mp_ticket) {
                    $("#bonus_parent").remove();
                } else {
                    $("#template_parent").remove();
                }
            })
        });
    </script>
@endsection
