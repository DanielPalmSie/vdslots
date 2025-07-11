<div class="card border-top border-top-3">
    <div class="card-header">
        <h3 class="card-title">Action buttons</h3>
    </div>
    <div class="card-body" id="rg-monitoring-buttons">
        <div class="row align-items-end">
            <div class="col-12 btn-group-md">
                @if(p('rg.monitoring.contacted-via-phone'))
                    <a class="btn btn-primary mt-1" data-rg-action="Player has been called to"
                       data-rg-action-type="intervention-call" data-follow-up="Yes"
                       href="{{$app['url_generator']->generate('admin.contacted-via-phone', ["user" => $user->id])}}"
                       data-modalbody="Are you sure you want to perform action <b>Player has been called to</b> on user <b>{{ $user->id }}</b>?"
                    >
                        <i class="fa fa-phone"></i> Player has been called to
                    </a>
                @endif
                @if(p('rg.monitoring.contacted-via-email'))
                    <a class="btn btn-primary mt-1" data-rg-action="Message has been sent to the player"
                       data-rg-action-type="intervention-message" data-follow-up="Yes"
                       href="{{$app['url_generator']->generate('admin.contacted-via-email', ["user" => $user->id])}}"
                       data-modalbody="Are you sure you want to perform action <b>Message has been sent to the player</b> on user <b>{{ $user->id }}</b>?"
                    >
                        <i class="fa fa-envelope"></i> Message has been sent to the player
                    </a>
                @endif
                @if(p('rg.monitoring.force-self-assessment-test'))
                    <a class="btn btn-primary mt-1" data-rg-action="Force Self-assessment test"
                       data-rg-action-type="force-self-assessment" data-follow-up="Yes"
                       href="{{$app['url_generator']->generate('admin.user-force-self-assessment-test', ["user" => $user->id])}}"
                       data-modalbody="Are you sure you want to perform action <b>Force Self-assessment test</b> on user <b>{{ $user->id }}</b>?"
                    >
                        <i class="fa fa-certificate"></i> Force Self-assessment test
                    </a>
                @endif
                @if(p('rg.monitoring.force-deposit-limit'))
                    <a class="btn btn-primary mt-1" data-rg-action="Force to set deposit limit"
                       data-rg-action-type="force-set-deposit-limit" data-follow-up="Yes"
                       href="{{$app['url_generator']->generate('admin.user-force-deposit-limit', ["user" => $user->id])}}"
                       data-modalbody="Are you sure you want to perform action <b>Force to set deposit limit</b> on user <b>{{ $user->id }}</b>?"
                    >
                        <i class="fas fa-money-bill"></i> Force to set deposit limit
                    </a>
                @endif
                {{--TODO check if we can migrate old settings into the foreach with a common handler for the button /Paolo --}}
                @foreach(['ask_play_too_long', 'ask_bet_too_high', 'ask_gamble_too_much'] as $setting)
                    @if(p('rg.monitoring.'.$setting))
                        @include('admin.rg.partials.monitoring-action-button', [
                            'action'  => dfh()::getRgMonitoringActions($setting)['label'],
                            'message' => "Are you sure you want to perform action <b>".dfh()::getRgMonitoringActions($setting)['message']."</b> on user <b>$user->id</b>?",
                            'setting' => $setting,
                        ])
                    @endif
                @endforeach

                @if(p('rg.monitoring.review'))
                    <a class="btn btn-primary mt-1" data-rg-action="Review" data-follow-up="No"
                       href="{{$app['url_generator']->generate('admin.rg-review', ["user" => $user->id])}}"
                       data-modalbody="Are you sure you want to perform action <b>Review</b> on user <b>{{ $user->id }}</b>?"
                    >
                        <i class="fa fa-eye"></i> Review
                    </a>
                @endif
                @if(p('rg.monitoring.force-self-exclusion'))
                    <a class="btn btn-primary mt-1" data-rg-action="Forced Self-exclusion for 6 months"
                       data-rg-action-type="force-self-exclusion-6-months" data-follow-up="Yes"
                       href="{{$app['url_generator']->generate('admin.rg-force-self-exlusion', ["user" => $user->id])}}"
                       data-modalbody="Are you sure you want to perform action <b>Forced Self-exclusion for 6 months</b> on user <b>{{ $user->id }}</b>?"
                    >
                        <i class="fa fa-lock"></i> Forced Self-exclusion for 6 months
                    </a>
                @endif
                @if(p('rg.monitoring.vulnerability-check') && lic('showVulnerabilityCheck', [], $user->id))
                    <button class="btn btn-primary mt-1" id='vulnerability_check' data-rg-action="Vulnerability Check"
                            data-follow-up="Yes">

                        <i class="fas fa-file-alt"></i> Vulnerability Check
                    </button>
                @endif
                @if(p('rg.monitoring.affordability-check') && lic('showAffordabilityCheck', [], $user->id))
                    <button class="btn btn-primary mt-1" id='affordability_check' data-rg-action="Affordability Check"
                            data-follow-up="Yes">
                        <i class="fas fa-file-invoice-dollar"></i>Affordability Check
                    </button>
                @endif
                @if(licSetting('show_user_extra_fields_in_admin', $user->id)['ask_rg_tools'] && p('rg.monitoring.ask-rg-tools'))
                    <a class="btn btn-primary mt-1" data-rg-action="Ask: rg tools" data-rg-action-type="ask-rg-tools"
                       data-follow-up="Yes"
                       href="{{$app['url_generator']->generate('admin.ask-rg-tools', ["user" => $user->id])}}"
                       data-modalbody="Are you sure you want to perform action <b>Ask RG Tools</b> on user <b>{{ $user->id }}</b>?"
                    >
                        <i class="fa fa-wrench"></i> Ask: RG tools
                    </a>
                @endif
                    @if(p('rg.monitoring.daily-action'))
                        <a class="btn btn-primary mt-1" data-rg-action="Daily" data-follow-up="No"
                           href="{{$app['url_generator']->generate('admin.rg-daily-action', ["user" => $user->id])}}"
                           data-modalbody="Are you sure you want to perform action <b>Daily</b> on user <b>{{ $user->id }}</b>?"
                        >
                            <i class="fa fa-eye"></i> Daily
                        </a>
                    @endif
                    @if(p('rg.monitoring.follow-up-action'))
                        <a class="btn btn-primary mt-1" data-rg-action="Follow Up" data-follow-up="No"
                           href="{{$app['url_generator']->generate('admin.rg-follow-up-action', ["user" => $user->id])}}"
                           data-modalbody="Are you sure you want to perform action <b>Follow Up</b> on user <b>{{ $user->id }}</b>?"
                        >
                            <i class="fa fa-eye"></i>Follow Up
                        </a>
                    @endif
                    @if(p('rg.monitoring.escalation-action'))
                        <a class="btn btn-primary mt-1" data-rg-action="Escalations" data-follow-up="No"
                           href="{{$app['url_generator']->generate('admin.rg-escalation-action', ["user" => $user->id])}}"
                           data-modalbody="Are you sure you want to perform action <b>Escalations</b> on user <b>{{ $user->id }}</b>?"
                        >
                            <i class="fa fa-eye"></i>Escalations
                        </a>
                    @endif
            </div>
        </div>
    </div>
</div>

<div id="intervention_call" class="d-none">
    <div class="form-group">
        <p><b>Type of phone call</b></p>
        <label>
            <input type="radio" name="intervention_type" value="forced-action-call"> Forced action call
        </label>
        <label>
            <input type="radio" name="intervention_type" value="proactive-call"> Proactive call
        </label>
    </div>
</div>

<div id="intervention_message" class="d-none">
    <div class="form-group">
        <p><b>Type of message</b></p>
        <label>
            <input type="radio" name="intervention_type" value="forced-action-message"> Forced action message
        </label>
        <label>
            <input type="radio" name="intervention_type" value="proactive-message"> Proactive message
        </label>
    </div>
</div>

<div id="intervention_cause_dropdown" class="d-none">
    <div class="form-group">
        <label for="intervention_cause">Intervention cause</label><br/>
        <select id="intervention_cause" class="form-control" style="width: 100%;" data-placeholder="Select one">
            <option value="">Select one</option>
            @foreach(\App\Helpers\DataFormatHelper::getInterventionCauses() as $key => $text)
                <option value="{{ $key }}">{{ $text }}</option>
            @endforeach
        </select>
    </div>
</div>

<div id="rg-modal-form" class="d-none">
    <div class="row align-items-end">
        <div class="col-12">
            <div id="rg-action-additional-info">
            </div>
        </div>
    </div>
    <div class="row align-items-end">
        <div class="col-12">
            <div class="form-group">
                <textarea id="rg-buttons-comment" type="text" name="comment" placeholder="User comment"
                          class="form-control"></textarea>
            </div>
        </div>
    </div>
    <div class="form-group checkbox" id="rg-follow-up-checkboxes">
        @php
            $follow = $user->settings_repo->getFollowUpData();
        @endphp
        @foreach(\App\Helpers\DataFormatHelper::getFollowUpOptions() as $key => $text)
            <div class="form-check-inline">
                <input class="form-check-input" name="rg-{{$key}}-follow-up" type="checkbox"
                       value="1" {{ $follow['rg'][$key] == 1 ? 'checked' : ''}}>
                <label class="form-check-label">
                    {{ $text }}
                </label>
            </div>
        @endforeach
    </div>
    <div class="form-group">
        <label for="select-risk-group-rg">Risk group</label>
        <select id="rg-buttons-risk-group" class="form-control" style="width: 100%;"
                data-placeholder="No risk group" data-allow-clear="true" data-default="{{$selected_option}}">
            <option value="all" {{$selected_option == 'all' || empty($selected_option) ? 'selected' : ''}}>None</option>
            @foreach(\App\Helpers\DataFormatHelper::getFollowUpGroups() as $key => $text)
                <option value="{{ $key }}" {{$selected_option === $key ? 'selected' : ''}}>{{ $text }}</option>
            @endforeach
        </select>
    </div>
</div>

<div id="affordability-success" class="d-none">
    <div class="table-responsive-sm">
        <table id="" class="table table-striped table-bordered dt-responsive"
               cellspacing="0" width="100%">
            <thead>
            <tr>
                <th>Date</th>
                <th>Score</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td id="col-date">test</td>
                <td id="col-score"></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<div id="vulnerability-success" class="d-none">
    <div class="table-responsive">
        <table id="" class="table table-striped table-bordered dt-responsive"
               cellspacing="0" width="100%">
            <thead>
            <tr>
                <th>Date</th>
                <th>Score</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td id="col-date">test</td>
                <td id="col-score"></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>


@section('footer-javascript')
    @parent
    <script>
        $(function () {
            function updateAdditionalInfo($rg_action_button, $risk_group_dropdown, $follow_up_checkboxes, $rg_string_div) {
                @php
                    $jurisdiction = (new \App\Repositories\UserRepository($user))->getJurisdiction();
                    $flags = implode(' ',\App\Repositories\RgRepository::getFlags($user->id));
                    $flags = empty($flags) ? 'not triggered' : $flags;
                @endphp
                var follow_up_array = [];
                $follow_up_checkboxes.each(function (i) {
                    if ($(this).is(':checked')) {
                        follow_up_array.push($(this).closest('label').text().trim());
                    }
                });
                var follow_up_string = follow_up_array.join(', ') || 'no';
                var rg_string = $rg_action_button.data('rg-action') + ' | ' +
                    'Flags: {{ $flags }} | ' +
                    'Risk Group: ' + $risk_group_dropdown.val() + ' | ' +
                    'Follow up: ' + follow_up_string + ' | ';

                $rg_string_div.html(rg_string);
            }

            function getInterventionTypeHtml(intervention_type) {

                @if (!lic('showInterventionTypes', [], $user->id))
                    return '';
                @endif

                if (intervention_type === 'intervention-call') {
                    return $('#intervention_call').html();
                }

                if (intervention_type === 'intervention-message') {
                    return $('#intervention_message').html();
                }

                return '';
            }

            const INTERVENTION_TYPE_TO_SHOW = [
                'intervention-call',
                'intervention-message',
                'force-self-assessment',
                'force-set-deposit-limit',
                'force-self-exclusion-6-months',
                'ask-rg-tools'
            ];

            function getInterventionCauseHtml(intervention_type) {

                @if (!lic('showInterventionTypes', [], $user->id))
                    return '';
                @endif

                if (INTERVENTION_TYPE_TO_SHOW.includes(intervention_type)) {
                    return $('#intervention_cause_dropdown').html();
                }
                return '';
            }

            $('#rg-monitoring-buttons a').on('click', function (e) {
                e.preventDefault();
                @php
                    $risk_group_string = $user->getSetting('rg-risk-group');
                    $risk_group_string = empty($risk_group_string) ? 'None' : $risk_group_string;
                    $profile_score_rating = \App\Repositories\RiskProfileRatingRepository::getProfileScoreRating($user->id);
                    $flags = implode(' ',\App\Repositories\RgRepository::getFlags($user->id));
                    $flags = empty($flags) ? '-' : $flags;
                @endphp
                var $clicked_button = $(this);

                // Don't show the popup when the button is disabled
                if ($clicked_button.attr('disabled')) {
                    return;
                }

                var rg_action_type = $(this).data('rg-action-type');
                var message = '<div class="row align-items-end"><div class="col-12">' +
                    $(this).data('modalbody') +
                    '<br><br><p>Please provide additional information:</p>' +
                    getInterventionTypeHtml(rg_action_type) +
                    $('#rg-modal-form').html() +
                    getInterventionCauseHtml(rg_action_type)
                '</div></div>';

                Swal.fire({
                    title: '<h3 class="card-title">Responsible Gaming Action<h3>',
                    html: message,
                    position: 'top',
                    width: 600,
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-check"></i> Confirm',
                    cancelButtonText: '<i class="fas fa-times"></i> Cancel',
                    customClass: {
                        popup: "card card-primary",
                        confirmButton: "btn btn-sm btn-primary w-15",
                        cancelButton: "btn btn-sm btn-default w-15 ml-3",
                        title: "card-header rg-header-custom",
                        htmlContainer: "card-body text-left text-sm p-3 m-0 text-dark-custom",
                        actions: "d-flex w-100 justify-content-end pr-3",
                    },
                    buttonsStyling: false,
                    willOpen: function () {
                        Swal.getPopup().id = 'rg-action-modal'
                    },
                    didOpen: function (modal) {
                        $(modal).off('click')
                        $(modal).on('select2:open', function() {
                            $('.select2-container--open').css('z-index', '99999');
                        });

                        inputValidationHandler($(modal).find('#rg-buttons-comment'));
                        interventionCauseValidationHandler();
                        radioInputValidationHandler('intervention_type');

                        var $rg_buttons_risk_group = $(modal).find("#rg-buttons-risk-group");

                        var $rg_string_div = $(modal).find("#rg-action-additional-info");
                        var $rg_follow_up_checkboxes = $(modal).find("#rg-buttons-risk-group");
                        var $rg_intervention_cause = $(modal).find("#intervention_cause");

                        $rg_buttons_risk_group.val('{{ $user->getSetting('rg-risk-group') }}');
                        $rg_buttons_risk_group.on('change', function () {
                            updateAdditionalInfo($clicked_button, $rg_buttons_risk_group, $rg_follow_up_checkboxes, $rg_string_div)
                        });

                        $rg_follow_up_checkboxes.on('change', function () {
                            updateAdditionalInfo($clicked_button, $rg_buttons_risk_group, $rg_follow_up_checkboxes, $rg_string_div)
                        });

                        $rg_buttons_risk_group.select2().trigger('change');

                        $rg_intervention_cause.select2().trigger('change');

                        var $confirm_button = $(Swal.getConfirmButton());

                        $confirm_button.on('click mouseover', function() {
                            $(modal).find("#intervention_cause").trigger('change');
                            $(modal).find("#rg-buttons-comment").trigger('change');
                            $(modal).find("input[name=intervention_type]").trigger('change');
                            if ($(modal).find('.has-error').length > 0) {
                                $confirm_button.attr('disabled', 'disabled');
                            } else {
                                $confirm_button.removeAttr('disabled');
                            }
                        });

                    },
                    preConfirm: function() {
                        return new Promise(function (resolve, reject) {
                            var modal =  $(Swal.getPopup())

                            var follow_up_data = {
                                'form_id': 'follow-up-settings',
                                'token': '{{ $_SESSION['token'] }}',
                                'user_id': '{{ $user->id }}',
                                'comment':
                                    modal.find('#rg-action-additional-info').html() + ' ' +
                                    modal.find("#rg-buttons-comment").val() + ' // {{cu()->getUsername()}}',
                                'rg-risk-group': modal.find("#rg-buttons-risk-group").val(),
                                'category': 'rg'
                            };

                            modal.find("input[type='checkbox']:checked").each(function () {
                                follow_up_data[$(this).attr('name')] = $(this).val();
                            });

                            var interventionData = {};
                            @if (lic('showInterventionTypes', [], $user->id))
                            if (INTERVENTION_TYPE_TO_SHOW.includes(rg_action_type)) {
                                var intervention_type = dialogRef.$modalBody.find('input[name=intervention_type]:checked').val();
                                if (intervention_type === undefined) {
                                    intervention_type = rg_action_type;
                                }
                                interventionData = {
                                    'intervention_type': intervention_type,
                                    'intervention_cause': dialogRef.$modalBody.find('#intervention_cause').val()
                                };
                            }
                            @endif

                            $.ajax({
                                method: 'POST',
                                url: "{{ $app['url_generator']->generate('admin.user-update-follow-up', ['user' => $user->id]) }}", //$clicked_button.attr('href'),
                                data: follow_up_data
                            }).done(function (follow_up_comment_result) {
                                $.ajax({
                                    method: 'GET',
                                    url: $clicked_button.attr('href'),
                                    data: interventionData,
                                    dataType: 'json'
                                }).done(function (rg_action_result) {
                                    displayNotifyMessage('success', rg_action_result['message']);
                                    resolve();
                                    location.reload();
                                }).fail(function(e) {
                                    console.warn(e);
                                    Swal.showValidationMessage(`${e.status} ${e.statusText}`);
                                    Swal.getCancelButton().disabled = false;
                                    reject()
                                });
                            }).fail(function(e) {
                                console.warn(e);
                                Swal.showValidationMessage(`${e.status} ${e.statusText}`);
                                Swal.getCancelButton().disabled = false;
                                reject()
                            });
                        })
                    }
                })
            });

            $('#affordability_check').on('click', function (e) {
                $.ajax({
                    url: "{{ $app['url_generator']->generate('user.account.affordability.check', ['user' => $user->id]) }}",
                    type: "POST",
                }).then(function (textStatus) {
                    var message = '<div class="row align-items-end"><div class="col-12">' +
                        $('#affordability-success').html() +
                        '</div></div>';
                    var d = new Date();

                    var month = d.getMonth() + 1;
                    var day = d.getDate();

                    var output = d.getFullYear() + '/' +
                        (month < 10 ? '0' : '') + month + '/' +
                        (day < 10 ? '0' : '') + day;
                    var score = textStatus.score;
                    Swal.fire({
                        title: '<h3 class="card-title">Affordability Check<h3>',
                        html: message,
                        position: 'top',
                        showCancelButton: false,
                        showConfirmButton: false,
                        showCloseButton: true,
                        customClass: {
                            closeButton: 'close shadow-none pt-3 pr-2',
                            htmlContainer: 'm-0 p-2',
                            popup: "card card-primary",
                            title: "card-header",
                            htmlContainer: "card-body text-left text-sm p-3 m-0",
                        },
                        willOpen: function () {
                            Swal.getPopup().id = 'rg-action-modal'
                        },
                        didOpen: function (modal) {
                            $(modal).off('click');
                            $(modal).find('#col-date').text(output)
                            $(modal).find('#col-score').text(score)
                        }
                    }).then(function(result) {
                        if (result.dismiss === Swal.DismissReason.close) {
                            location.reload();
                        }
                    });
                }).fail(function (jqXHR) {
                    displayNotifyMessage('error', jqXHR['statusText']);
                    location.reload();
                });
            });

            $('#vulnerability_check').on('click', function (e) {
                $.ajax({
                    url: "{{ $app['url_generator']->generate('user.account.vulnerability.check', ['user' => $user->id]) }}",
                    type: "POST",
                }).then(function (textStatus) {
                    var message = '<div class="row align-items-end"><div class="col-12">' +
                        $('#vulnerability-success').html() +
                        '</div></div>';
                    var d = new Date();

                    var month = d.getMonth()+1;
                    var day = d.getDate();

                    var output = d.getFullYear() + '/' +
                        (month<10 ? '0' : '') + month + '/' +
                        (day<10 ? '0' : '') + day;
                    var score = textStatus.score;
                    Swal.fire({
                        title: '<h3 class="card-title">Vulnerability Check<h3>',
                        html: message,
                        position: 'top',
                        showCancelButton: false,
                        showConfirmButton: false,
                        showCloseButton: true,
                        customClass: {
                            closeButton: 'close shadow-none pt-3 pr-2',
                            htmlContainer: 'm-0 p-2',
                            popup: "card card-primary",
                            title: "card-header",
                            htmlContainer: "card-body text-left text-sm p-3 m-0",
                        },
                        willOpen: function () {
                            Swal.getPopup().id = 'rg-action-modal'
                        },
                        didOpen: function (modal) {
                            $(modal).off('click');
                            $(modal).find('#col-date').text(output)
                            $(modal).find('#col-score').text(score)
                        }
                    }).then(function(result) {
                        if (result.dismiss === Swal.DismissReason.close) {
                            location.reload();
                        }
                    });
                }).fail(function (jqXHR) {
                    displayNotifyMessage('error', jqXHR['statusText']);
                    location.reload();
                });
            });
        });

        function inputValidationHandler($input) {
            $input.on('change keyup', function () {
                if ($input.val().length === 0) {
                    $input.closest('.form-group').addClass('has-error');
                } else {
                    $input.closest('.form-group').removeClass('has-error');
                }
            });
        }

        function radioInputValidationHandler(input_name) {
            $(document).off('change.rgaction').on('change.rgaction', '#rg-action-modal input[name="' + input_name +'"]', function(){
                $(this).closest('.form-group').toggleClass('has-error', ($('input[name=' + input_name + ']:checked').length === 0));
            });
        }

        function interventionCauseValidationHandler() {
            $(document).off('change.intervention_cause').on('change.intervention_cause', '#rg-action-modal #intervention_cause', function(){
                $(this).closest('.form-group').toggleClass('has-error', ($(this).val() === ''));
            });
        }

    </script>
@endsection
