@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.messaging.partials.topmenu')
        @include('admin.messaging.bonus.partials.bonus-details-modal')

        <div class="card">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.bonus.list') }}">List bonus code templates</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.vouchers.list') }}">List voucher code templates</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ $app['url_generator']->generate('messaging.bonus.create-template', ['step' => 1]) }}"><i class="fa fa-plus-square"></i> Create bonus code template</a></li>
                    <li class="nav-item"><a class="nav-link active"><i class="fa fa-plus-square"></i>
                            @if($voucher_template->exists)
                                Edit voucher code template
                            @else
                                Create voucher code template
                            @endif
                            </a>
                    </li>
                </ul>
                <div class="tab-content p-3">
                    <div class="tab-pane active">
                        <div class="card">
                            <div class="card-header with-border">
                                <h3 class="card-title">Template form</h3>
                            </div>
                            <div class="card-body">
                                <form id="sms-template-form" method="post">
                                    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                                    <div class="row">
                                        <div class="col-12 col-md-6 col-fhd-4 col-rt-3">
                                            <div class="form-group">
                                                <label for="template-name-input">Template name</label>
                                                <input name="template_name" id="template-name-input" class="form-control" type="text" required
                                                    placeholder="Insert voucher template name" value="{{ $voucher_template->template_name }}">
                                            </div>
                                            <div class="form-group">
                                                <label for="voucher-name-input">Series name (<a href="javascript:modalErrorMessage('Format example: VS@{{date|dmy}} generates names like VS120417')">See example</a>)</label>
                                                <input name="voucher_name" id="voucher-name-input" class="form-control" type="text" required
                                                    placeholder="Example: VS100@{{date}}" value="{{ $voucher_template->voucher_name }}">
                                            </div>
                                            <div class="form-group">
                                                <label for="voucher_code">Password/Code (<a href="javascript:modalErrorMessage('Format example: VS@{{date|dmy}} generates codes like VS120417')">See example</a>)</label>
                                                <input name="voucher_code" class="form-control" type="text"
                                                    placeholder="Leave empty for automatic generation which is unique for each voucher" value="{{ $voucher_template->voucher_code }}">
                                            </div>
                                            <div class="form-group">
                                                <label for="count">Number of vouchers</label>
                                                <input name="count" class="form-control" type="number" min="1" required
                                                    placeholder="Insert a number" value="{{ $voucher_template->count }}">
                                            </div>
                                            <div class="form-group">
                                                <label for="expire_time">Expire time</label>
                                                <input name="expire_time" class="form-control" type="text"
                                                    placeholder="Insert time" value="{{ empty($voucher_template->expire_time) ? '+7 day' : $voucher_template->expire_time }}">
                                            </div>
                                            <div class="form-group">
                                                <label for="deposit_amount">Deposit amount</label>
                                                <input name="deposit_amount" class="form-control" type="number" min="1"
                                                    placeholder="Insert an amount" value="{{ $voucher_template->deposit_amount == 0 ? '' : $voucher_template->deposit_amount }}">
                                            </div>
                                            <div class="form-group">
                                                <label for="deposit_period">Deposit period</label>
                                                <input name="deposit_period" class="form-control" type="text"
                                                    placeholder="Insert a period" value="{{ empty($voucher_template->deposit_start) || empty($voucher_template->deposit_start) ? '' : $voucher_template->deposit_start.' - '.$voucher_template->deposit_end }}">
                                            </div>
                                            <div class="form-group">
                                                <label for="deposit_method">Deposit method</label>
                                                <select name="deposit_method[]" id="select-deposit-method" class="form-control select2-class" data-placeholder="Select deposit method(s)" data-allow-clear="true" multiple="multiple">
                                                    @foreach(\App\Repositories\TransactionsRepository::getDepositMethods() as $type)
                                                        <option value="{{ $type->dep_type }}">{{ ucwords($type->dep_type) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 col-fhd-4 col-rt-3">
                                            <div class="form-group">
                                                <label for="wager_amount">Wager amount</label>
                                                <input name="wager_amount" class="form-control" type="number" min="1"
                                                    placeholder="Insert amount" value="{{ $voucher_template->wager_amount == 0 ? '' : $voucher_template->wager_amount }}">
                                            </div>
                                            <div class="form-group">
                                                <label for="wager_period">Wager period</label>
                                                <input name="wager_period" class="form-control" type="text"
                                                    placeholder="Insert a period" value="{{ empty($voucher_template->wager_start) || empty($voucher_template->wager_end) ? '' : $voucher_template->wager_start.' - '.$voucher_template->wager_end }}">
                                            </div>
                                            <div class="form-group">
                                                <label for="games">Game</label>
                                                <select name="games[]" id="select-game" class="form-control select2-class" data-placeholder="Select game(s)" data-allow-clear="true" multiple="multiple">
                                                    @foreach(\App\Repositories\GameRepository::getGameList() as $game)
                                                        <option value="{{ $game->game_id }}">{{ $game->game_id }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="game_operators">Game operator</label>
                                                <select name="game_operators[]" id="select-game-operator" class="form-control select2-class" data-placeholder="Select game operator(s)" data-allow-clear="true" multiple="multiple">
                                                    @foreach(\App\Repositories\GameRepository::getOperatorList() as $operator)
                                                        <option value="{{ $operator }}">{{ $operator }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="game_operators">Has username on popular forums</label>
                                                <select name="user_on_forums[]" id="select-forums" class="form-control select2-class" data-placeholder="Select forum(s)" data-allow-clear="true" multiple="multiple">
                                                    @foreach(\App\Helpers\DataFormatHelper::getPopularForums() as $key => $forum)
                                                    <option value="{{ $key }}">{{ $forum }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="select-exclusive">Exclusive</label>
                                                <select name="exclusive" id="select-exclusive" class="form-control select2-class"
                                                        data-placeholder="Set to 0 to allow players to activate several times" data-allow-clear="true">
                                                    <option></option>
                                                    <option value="0">0</option>
                                                    <option value="1">1</option>
                                                    <option value="2">2</option>
                                                    <option value="3">3</option>
                                                    <option value="4">4</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="bonus-desc-input">Bonus</label>
                                                <div class="input-group">
                                                    <input type="text" id="bonus-desc-input" class="form-control" placeholder="No bonus linked to this voucher" name="bonus_type_template_name"
                                                        value="{{ $bonus->bonus_name }}" disabled>
                                                    <input type="hidden" name="bonus_type_template_id" value="{{ $voucher_template->bonus_type_template_id }}" id="bonus-id-input">
                                                    <div class="input-group-btn">
                                                        <button type="button" id="see-bonus-link" class="btn btn-flat btn-info text-md">Show bonus list</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="award-desc-input">Reward</label>
                                                <div class="input-group">
                                                    <input type="text" id="award-desc-input" class="form-control" placeholder="No reward linked to this voucher" name="trophy_award_name"
                                                        value="{{ $reward->description }}" disabled>
                                                    <input type="hidden" name="trophy_award_id" value="{{ $voucher_template->trophy_award_id }}" id="award-id-input">
                                                    <div class="input-group-btn">
                                                        <button type="button" id="see-rewards-link" class="btn btn-flat btn-info text-md">Show rewards list</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <button class="btn btn-flat btn-block btn-info mt-4 text-md" id="save-voucher-btn">Save</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div id="voucher-template-ajax-box">

                        </div>
                    </div>
                </div>
                <div id="errorModal" class="modal fade">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">Info</h4>
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                            </div>
                            <div class="modal-body">

                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">

        function setRewardField(reward_id, reward_name) {
            $("#award-desc-input").val(reward_name);
            $("#award-id-input").val(reward_id);
            $("#bonus-desc-input").val('');
            $("#bonus-id-input").val('');
        };

        var bonusTemplates = {

            setBonusField: function(bonus_id, bonus_name) {
                $("#bonus-desc-input").val(bonus_name);
                $("#bonus-id-input").val(bonus_id);
                $("#award-desc-input").val('');
                $("#award-id-input").val('');
            },

            viewDetails:  function viewDetails(id) {
                console.log(id);
                $.ajax({
                    url: "{{ $app['url_generator']->generate('messaging.bonus.get-bonus-type-template-details') }}",
                    type: "POST",
                    data: {bonus: id},
                    success: function (response, textStatus, jqXHR) {
                        $("#detail_modal_body_bonus").html(response['html']);
                        $('#detail-view-modal_bonus').modal('show');
                        return false;
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            }
        };

        $(document).ready(function() {
            $("#select-exclusive").select2().val("{{ $app['request_stack']->getCurrentRequest()->get('exclusive', 1) }}").change();


            $("#bonus-desc-input").val("{{ $bonus_name }}");
            $("#bonus-id-input").val("{{ $voucher_template->bonus_type_template_id }}");
            $("#award-desc-input").val("{{ $reward_name }}");
            $("#award-id-input").val("{{ $voucher_template->trophy_award_id }}");

            $("#select-deposit-method").select2();
            $("#select-game").select2();
            $("#select-game-operator").select2();
            $("#select-forums").select2();

            @if(!empty($voucher_template))
                var voucherObj = {!! $voucher_template !!};
                if (voucherObj['deposit_method'].length > 0) {
                    $("#select-deposit-method").select2().val(voucherObj['deposit_method']).trigger("change");
                }
                if (voucherObj['games'].length > 0) {
                    $("#select-game").select2().val(voucherObj['games']).trigger("change");
                }
                if (voucherObj['game_operators'].length > 0) {
                    $("#select-game-operator").select2().val(voucherObj['game_operators']).trigger("change");
                }
                if (voucherObj['user_on_forums'].length > 0) {
                    $("#select-forums").select2().val(voucherObj['user_on_forums']).trigger("change");
                }

            @endif

            $("#see-rewards-link").on('click', function(e) {
                e.preventDefault();
                var self = $(this);
                $.ajax({
                    url: "{{ $app['url_generator']->generate('messaging.rewards.list') }}",
                    type: "POST",
                    success: function (response, textStatus, jqXHR) {
                        //self.html('Remove');
                        $("#voucher-template-ajax-box").html(response['html']);
                        $("#award-list-databable").DataTable({
                            "pageLength": 25,
                            "language": {
                                "emptyTable": "No results found.",
                                "lengthMenu": "Display _MENU_ records per page"
                            },
                            "order": [[0, "desc"]],
                            "columnDefs": [{"targets": 4, "orderable": false, "searchable": false}]
                        });
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });

            $("#see-bonus-link").on('click', function(e) {
                e.preventDefault();
                var self = $(this);
                $.ajax({
                    url: "{{ $app['url_generator']->generate('messaging.bonus.list', ['voucher-form' => 1]) }}",
                    type: "POST",
                    success: function (response, textStatus, jqXHR) {
                        $("#voucher-template-ajax-box").html(response['html']);
                        $("#bonus-template-list-databable").DataTable({
                            "pageLength": 25,
                            "language": {
                                "emptyTable": "No results found.",
                                "lengthMenu": "Display _MENU_ records per page"
                            },
                            "order": [[0, "desc"]],
                            "columnDefs": [{"targets": 4, "orderable": false, "searchable": false}]
                        });
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });

            setTimePickerForInput($('input[name="deposit_period"]'));
            setTimePickerForInput($('input[name="wager_period"]'));

        });

        function setTimePickerForInput(date_picker_input)
        {
            date_picker_input.daterangepicker({
                startDate: new Date("{{ \Carbon\Carbon::now()->toDateTimeString() }}"),
                autoUpdateInput: false,
                timePicker: true,
                timePicker24Hour: true,
                timePickerSeconds: true,
                locale: {
                    format: 'YYYY-MM-DD HH:mm:ss',
                    cancelLabel: 'Clear',
                    firstDay: 1
                }
            });

            date_picker_input.on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD hh:mm:ss') + ' - ' + picker.endDate.format('YYYY-MM-DD hh:mm:ss'));
            }).on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });
        }

        function modalErrorMessage(message) {
            $(".modal-body").text(message);
            $('#errorModal').modal('show');
            return false;
        }

    </script>
@endsection
