@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.gamification.partials.topmenu')

        <div id="myModal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title font-weight-normal">Bonus Type Wizard - Step 1</h5>
                            <p><b>Deposit </b>&nbsp;<i class="fa fa-caret-right" aria-hidden="true"></i> Do something&nbsp;<i class="fa fa-caret-right" aria-hidden="true"></i> Finished!</p>
                        </div>
                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <h4 class="text-center font-weight-normal">Is there a deposit?</h4>
                            <!--
                            <div class="form-group">
                                <label for="expire_time">Is there a deposit?</label>
                                <input id="input-deposit" name="deposit" class="form-control" data-on="Yes" data-off="No" data-toggle="toggle" data-onstyle="success" data-offstyle="danger" type="checkbox"
                                        value=""
                                >
                            </div>
                            -->
                            <!--
                            <div class="form-group">
                                <label for="formGroupExampleInput2">Another label</label>
                                <input type="text" class="form-control" id="formGroupExampleInput2" placeholder="Another input">
                            </div>
                            -->
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger">No&nbsp;<i class="fa fa-angle-right" aria-hidden="true"></i></button>
                        <button type="button" class="btn btn-success">Yes&nbsp;<i class="fa fa-angle-right" aria-hidden="true"></i></button>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">Bonus Type Wizard <button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#myModal">Start Wizard</button></h3>
                <div class="float-right">
                    <a href="{{ $app['url_generator']->generate('bonustypes.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-sm-12 col-lg-12">
                        <div class="row">
                            <div class="col-12 col-md-6 col-sm-5 col-lg-6">

                                @if (count($bonustype_wizard_types) == 0)
                                    <h4 class="text-center">No config with the tag "bonus-types-wizard-types" has been created to specify what bonues types there are.</h4>
                                @else
                                    @foreach ($bonustype_wizard_types as $type)
                                        <h4 class="text-center"><a id="{{ $type }}" class="bonus-type" data-type="{{ $type }}" href="#">{{ ucwords(str_replace("-", " ", $type)) }}</a></h4>
                                    @endforeach
                                @endif

                                <hr/>

                                <div id="wizard-form-container">
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-sm-7 col-lg-6">
                                <h4>Bonus Type Information:</h4>
                                <p><strong>Bonus Name:</strong> The name of the bonus.</p>
                                <p><strong>Expire Time:</strong> The expire date of the bonus.</p>
                                <p><strong>Deposit Limit (cents):</strong> Capped value of a deposit with regards to further bonus calculations.</p>
                                <p><strong>Deposit Multiplier (float, ex: 0.5):</strong> Will be applied before the deposit limit value, if the deposit amount times this multiplier is higher than the limit then the considered value will be the limit. Ex: a deposit of 100000 cents gets reduced by a multiplier of 0.5 to 50000 cents but if the deposit limit is set to 10000 the considered value will be 10000.</p>
                                <p><strong>Bonus Code:</strong> Controls if the bonus should apply to a given player by way of which affiliate the user is tagged to, the bonus code must match a bonus code of the affiliate in question.</p>
                                <p><strong>Reload Code:</strong> This code needs to be entered properly by the player during the deposit process, if the entered code matches the reload code the reload bonus will be activated and the deposited amount used in further calculations.</p>
                                <p><strong>Loyalty Percent (float):</strong> Set to for instance 0.5 if you want the bonus to generate 50% of the wager turnover towards the normal cashback, result: X wager * 0.5 * 0.01 = actual cashback.</p>
                                <p><strong>Free Spin Games:</strong> Pick the games that should apply in a freespin bonus.</p>
                                <p><strong>Reward (cents):</strong> The amount of money the cash bonus balance starts with OR the amount of free spins a in free spin bonus activated via voucher.</p>
                                <p><strong>Voucher Code:</strong> A code the player needs to enter to activate the bonus in question.</p>
                                <p><strong>Number of Vouchers:</strong> The amount of vouchers in the series, once they're all used up further activations become impossible.</p>
                                <p><strong>Username of Affiliate (optional):</strong> If entered, it will tag players who activate the voucher to that affiliate. Only works on untagged newly registered players.</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 col-md-6 col-sm-5 col-lg-6">
                                <a href="#" class="btn btn-info expand">Show Trophy Award <span class="fa fa-chevron-right"></span></a>
                                <a href="#" class="btn btn-info collapse">Hide Trophy Award <span class="fa fa-chevron-down"></span></a>
                            </div>
                            <div class="col-12 col-md-6 col-sm-7 col-lg-6">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="trophyawardbox-content">
                <hr />
                @include('admin.gamification.trophyawards.partials.trophyawardbox')
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script src="/phive/admin/customization/plugins/bootstrap-toggle/bootstrap-toggle.min.js"></script>

    @stack('extrajavascript')

    @include('admin.gamification.bonustypes.partials.bonustypesharedjs')
    @include('admin.gamification.trophyawards.partials.trophyawardsharedjs')

    <script type="text/javascript">

        var globalGame = null;
        var globalBonusType = null;
        var globalonusTypeDefaults = null;

        function capitalizeWords(str) {
            return str.replace(/\w\S*/g, function(txt) {
                return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
            });
        }

        function clickBonusType() {
        }

        function getBonusTypeDefaults() {
            @foreach ($bonustype_wizard_types as $bonus_type)
                if (globalBonusType == "{{ $bonus_type }}") {
                    return {!! json_encode($wizard_data[$bonus_type]['bonus-types-wizard-static-defaults']) !!};
                }
            @endforeach
        }


        function createBonusTypeAndTrophyAward(button, callback_on_failure = null, callback_on_success = null, disable_redirect_on_success = false) {

            var form_data = {};
            form_data['bonustype_form'] = $('#bonustype-form').serializeArray();
            form_data['trophyaward_form'] = $('#trophyaward-form').serializeArray();

            console.log(form_data);

            if (button) {
                button.prop("disabled", true);
            }

            $.ajax({
                url: "{{ $app['url_generator']->generate('bonustypes.newcombo') }}",
                type: "POST",
                data: form_data,
                success: function (data, text_status, jqXHR) {
                    if (data.success == true) {

                        var message = 'Bonus Type and Trophy Award created successfully.';

                        if (!disable_redirect_on_success) {
                            message += ' Loading new Bonus Type...';
                        }

                        displayNotifyMessage('success', message);

                        if (callback_on_success && typeof(callback_on_success) === "function") {
                            callback_on_success(data);
                        }

                        if (!disable_redirect_on_success) {
                            setTimeout(function() {
                                var redirect_to = "{{ $app['url_generator']->generate('bonustypes.edit', ['bonustype' => -1]) }}";
                                redirect_to = redirect_to.replace("-1", data.bonustype.id);
                                window.location.replace(redirect_to);
                            }, 3000);
                        }

                    } else {
                        console.log(data);

                        if (button) {
                            button.prop("disabled", false);
                        }

                        if (callback_on_failure && typeof(callback_on_failure) === "function") {
                            callback_on_failure();
                        }

                        var attribute_errors = getAttributeErrors(data);
                        displayNotifyMessage('error', 'Bonus Type and Trophy Award creation failed. '+attribute_errors+(data.error?data.error:''));
                    }
                },
                error: function (jqXHR, text_status, error_thrown) {
                    console.log(error_thrown);

                    if (button) {
                        button.prop("disabled", false);
                    }

                    if (callback_on_failure && typeof(callback_on_failure) === "function") {
                        callback_on_failure();
                    }

                    displayNotifyMessage('error', 'Bonus Type creation failed. Error: '+error_thrown);
                }
            });
        }


        function updateFormData() {
            if (globalGame) {
                @foreach ($bonustype_wizard_types as $bonus_type)
                    if (globalBonusType == "{{ $bonus_type }}") {
                        @foreach ($wizard_data[$bonus_type]['bonus-types-wizard-expressions'] as $expression)
                            $("#input-{{ $expression['visibility'] }}").val({!! $expression['value'] !!});
                        @endforeach
                    }
                @endforeach
            }
            updateTrophyAward();
        }

        function updateTrophyAward() {
            if (globalGame) {
                var alias = $('#input-game_name').val().trim().toLowerCase().replace(/\s+/g, '_') + "-" + $('#input-reward').val().trim() + "_freespins";
                var description = $('#input-game_name').val() + " - " + $('#input-reward').val() + " Free Spins";
                $('#input-trophyawards-alias').val(alias);
                $('#input-trophyawards-description').val(description);
                $('#input-trophyawards-amount').val($('#input-reward').val());
            }
        }

        function updateBonusType() {
            var default_bonus_types = getBonusTypeDefaults();

            if (default_bonus_types != null) {
                var selected_group = $('#select-group').val();

                // Clear all first.
                default_bonus_types.forEach(function(element_row) {
                    $('#input-'+element_row['name']).val("");
                });
                default_bonus_types.forEach(function(element_row) {
                    //console.log(element_row);
                    if (element_row['group'] == selected_group) {
                        $('#input-'+element_row['name']).val(element_row['value']);
                    }
                });
            }
        }

        $(document).ready(function() {

            enableSelect2Controllers();
            enableDropZone();

            $('.select-trophyawards-award_id').each(function(index) {
                $(this).prop("disabled", true);
                //$(this).parent().text("This is set automatically from the created Bonus Type.");
            });
            $('#span-trophyawards-bonus_id_bonus_type').each(function(index) {
                $(this).parent().text("");
            });


          	$('.collapse').hide();
            $('#trophyawardbox-content').hide();
                $('.expand, .collapse').on('click', function(event) {
                event.preventDefault();

                $('.collapse').toggle();
                $('.expand').toggle();
                $('#trophyawardbox-content').slideToggle();
            });

            //$('#myModal').modal();
            $("#wizard-form-container").on("click", "#create-bonus-type-btn", function(event) {
                event.preventDefault();
                createBonusType(getAllNonModalButtons());
            });

            $("#wizard-form-container").on("click", "#create-bonus-type-trophy-award-btn", function(event) {
                event.preventDefault();
                createBonusTypeAndTrophyAward(getAllNonModalButtons(), null, function(bonus_type_and_trophy_award_data) {
                    console.log(bonus_type_and_trophy_award_data);

                    var redirect_to_bonus_type = "{{ $app['url_generator']->generate('bonustypes.edit', ['bonustype' => -1]) }}";
                    redirect_to_bonus_type = redirect_to_bonus_type.replace("-1", bonus_type_and_trophy_award_data.bonustype.id);
                    $('#create-bonus-type-btn').parent().html('<a href="'+redirect_to_bonus_type+'">New Bonus Type</a> and ');

                    var redirect_to_trophy_award = "{{ $app['url_generator']->generate('trophyawards.edit', ['trophyaward' => -1]) }}";
                    redirect_to_trophy_award = redirect_to_trophy_award.replace("-1", bonus_type_and_trophy_award_data.trophyaward.id);
                    $('#create-bonus-type-trophy-award-btn').parent().html('<a href="'+redirect_to_trophy_award+'">New Trophy Award</a>');
                }, true);
            });

            $('#wizard-form-container').on('keyup change', "#input-reward", function(event) {
                event.preventDefault();
                updateFormData();
            });

            $(".bonus-type").on("click", function(event) {
                event.preventDefault();

                globalBonusType = $(this).data('type');

                $.ajax({
                    url: "{{ $app['url_generator']->generate('bonustypes.wizardajax') }}",
                    type: "GET",
                    data: {'bonustype': globalBonusType},
                    complete: function() {
                    },
                    success: function(data, text_status, jqXHR) {
                        //console.log(data);

                        //$("#select-game_id").select2().val(btObj['game_id']).trigger("change");

                        $('#wizard-form-container').html(data);
                        $("#select-group").select2();
                        $("#select-ext_ids").select2();

                        var group_select2 = $("#select-group").select2();
                        group_select2.on("select2:select", function(e) {
                            updateBonusType();
                            updateFormData();
                        })

                        updateBonusType();

                        var game_id_select2 = $("#select-game_id").select2();
                        game_id_select2.on("select2:select", function(e) {
                            $.ajax({
                                url: "{{ $app['url_generator']->generate('game.getbyid') }}",
                                type: "GET",
                                data: {'game_id': game_id_select2.val()[0]},
                                complete: function() {
                                },
                                success: function(data, text_status, jqXHR) {
                                    globalGame = JSON.parse(data);
                                    $("#select--ext_ids").val(globalGame.ext_game_name).trigger('change');
                                    updateFormData();
                                },
                                error: function(jqXHR, text_status, error_thrown) {
                                    console.log(error_thrown);
                                    displayNotifyMessage('error', 'Unable to get Game. Error: '+error_thrown);
                                }
                            });
                        });

                        clickBonusType();

                        displayNotifyMessage('success', 'Wizard Form updated to "'+capitalizeWords(globalBonusType.replace(/\-/g, ' '))+'"');
                    },
                    error: function(jqXHR, text_status, error_thrown) {
                        console.log(error_thrown);
                        displayNotifyMessage('error', 'Wizard Form update failed. Error: '+error_thrown);
                    }
                });
            });
        });
    </script>

@endsection
