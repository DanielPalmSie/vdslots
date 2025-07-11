@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.messaging.partials.topmenu')

        <style>
            .select2,
            .select2-class {
                width: 100% !important;
            }

            .hide-soft {
                display: none;
            }
        </style>
        <div class="card">
            <div class="nav-tabs-custom">
            @includeIf("admin.messaging.partials.submenu")
            <div class="tab-content">
                <div class="tab-pane active">
            <div class="card-body">
                <form id="schedule-form" method="post" action="{{ $app['request_stack']->getCurrentRequest()->getRequestUri() }}">
                    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                    <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-fhd-6">
                        <div class="card card-default">
                            <div class="card-header with-border">
                                <h3 class="card-title">Configure the schedule</h3>
                            </div>
                            <div class="card-body">
                        <div class="form-group" id="template-group">
                            <label for="select-template">{{ $c_type->getName() }} template</label>
                            <select name="template_id" id="select-template" class="form-control select2-class" data-placeholder="Select the {{$c_type->getName()}} template" data-allow-clear="true">
                                <option></option>
                                @foreach($templates_list as $template)
                                    <option value="{{ $template->id  }}">{{ $template->template_name }} [{{ strtoupper($template->language) }}] </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                        <label for="select-named-search">Contacts filter</label>
                            <select name="named_search_id" id="select-named-search" class="form-control select2-class" data-placeholder="Select a contact filter" data-allow-clear="true">
                                <option></option>
                                        @foreach($named_searches as $ns)
                                            <option value="{{ $ns->id  }}">{{ $ns->name }} ({{ $ns->language }})</option>
                                        @endforeach
                                </select>
                                </div>
                        <div class="form-group">
                            <label for="select-type">Promotion type</label>
                            <select id="select-type" class="form-control select2-class" data-placeholder="Select type">
                                <option value="no">No promotion</option>
                                <option value="bonus">Bonus</option>
                                <option value="voucher">Voucher</option>
                            </select>
                        </div>
                        <div class="form-group hide-soft" id="voucher-group">
                            <label for="select-voucher-template">Voucher template</label>
                            <select name="voucher_template_id" id="select-voucher-template" class="form-control select2-class" data-placeholder="Select a voucher template" data-allow-clear="true">
                                <option value="0" selected="selected"></option>
                                @foreach($voucher_templates as $template)
                                    <option value="{{ $template->id  }}">{{ empty($template->template_name) ? "Template #{$template->id}" : $template->template_name }} // {{ $template->voucher_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group hide-soft" id="bonus-group">
                            <label for="select-bonus-template">Bonus template</label>
                            <select name="bonus_template_id" id="select-bonus-template" class="form-control select2-class" data-placeholder="Select a bonus template" data-allow-clear="true">
                                <option></option>
                                @foreach($bonus_templates as $template)
                                    <option value="{{ $template->id  }}">{{ empty($template->template_name) ? "Template #{$template->id}" : $template->template_name }} // {{ $template->bonus_name }}</option>
                                @endforeach
                            </select>
                        </div>
                                <div class="form-group">
                                    <label for="select-recurring-type">Recurring type</label>
                                    <select name="recurring_type" id="select-recurring-type" class="form-control select2-class" data-placeholder="" data-allow-clear="true">
                                        <option value="one">One time only</option>
                                        <option value="day">Day</option>
                                        <option value="week">Week</option>
                                        <option value="month">Month</option>
                                    </select>
                                </div>
                        <div id="one-time-only" class="form-group">
                            <label for="scheduled_time">Schedule time</label>
                            <input type="text" class="form-control" name="scheduled_time" value="@if($campaign_template) {{ $campaign_template->generateScheduledTime() }} @endif" placeholder="Select a date (UTC)">
                        </div>
                        <div class="hide-soft" id="recurring-group">
                            <div class="form-group">
                                <label for="start-time">Start Time</label>
                                <div class="input-group date" id="start-time-picker" data-target-input="nearest" data-toggle="datetimepicker">
                                    <input
                                        type="text"
                                        id="start-time"
                                        name="start_time"
                                        class="form-control datetimepicker-input"
                                        data-target="#start-time-picker"
                                        value="{{ $campaign_template->start_time }}"
                                        placeholder="Numeric on type day, time on type week or month."
                                    />
                                    <div class="input-group-append" data-target="#start-time-picker" data-toggle="datetimepicker">
                                        <div class="input-group-text"><i class="fa fa-clock"></i></div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group" id="recurring-days-group">
                                <label for="recurring-days">Recurring days</label>
                                <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle"
                                title="Multiple values can be selected."></i>
                                {{--name="recurring_days"  will be set to the select from js--}}
                                <input id="recurring-days" type="hidden" class="form-control" value="{{ json_encode($campaign_template->recurring_days) }}" placeholder="Only valid on week and month type, see more at info tooltip">
                            </div>
                            <div class="form-group">
                                <label for="recurring-end-date">Recurring end date</label>
                                <i data-toggle="tooltip" data-placement="right" class="fa fa-info-circle" title="When the campaign should stop recurring."></i>
                                <input type="text" id="recurring-end-date" class="form-control" name="recurring_end_date" value="{{ $campaign_template->recurring_end_date }}" placeholder="Select a date (UTC)">
                            </div>
                            <input type="hidden" name="template_type" value="{{ empty($campaign_template) ? $type_id : $campaign_template->template_type  }}">
                        </div>

                            @if (!empty($from_email))
                                <div class="form-group">
                                    <label for="sender_name">Sender name</label>
                                    <select name="is_newsletter" id="sender_name" class="form-control select2-class">
                                        @foreach($from_email as $i => $name)
                                            <option value={{ $i }}> {{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-flat btn-primary">Save</button>
                                <button id="campaign-test-btn" type="submit" class="btn btn-flat btn-info" title="{{ !empty($template_obj) ? '' : 'You must choose template' }}" {{ !empty($template_obj) ? '' : 'disabled' }} >
                                Test
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="template-content-box">
                    @if (!empty($template_obj))
                            @include('admin.messaging.sms.partials.template-show', compact('c_type', 'template_obj'))
                    @endif
                    </div>
                </form>
            </div>
            </div>
        </div>

        <div id="campaign-test-modal" class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Campaign test</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">
                        <form id="test-schedule-form">
                            <div class="form-group">
                                <label for="select-test-list">Test list</label>
                                <select name="test_list_id" id="select-test-list" class="form-control select2-class" data-placeholder="Select a test contacts list" data-allow-clear="true">
                                    <option></option>
                                    @foreach($named_searches as $ns)
                                        <option value="{{ $ns->id  }}">{{ $ns->name }} ({{ $ns->language }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Test username (comma separated values)</label>
                                <input type="text" class="form-control" name="test_username" value="" placeholder="">
                            </div>
                        </form>
                        <div id="modal-result-msg"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary btn-flat send-test-campaign-btn" disabled data-action="show" data-issms={{$c_type->isSMS()}}>Show message only</button>
                        <button type="button" class="btn btn-primary btn-flat send-test-campaign-btn" disabled data-action="contact">Send to test contact list</button>
                        <button type="button" class="btn btn-primary btn-flat send-test-campaign-btn" disabled data-action="username">Send to test username</button>
                        <button type="button" class="btn btn-danger btn-flat" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="check-send-modal" class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Info</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">
                        <div id="modal-alert-msg"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary btn-flat btn-ok" data-action="show">Yes</button>
                        <button type="button" class="btn btn-danger btn-flat btn-close" data-dismiss="modal">Close</button>
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

        function generateContactList(named_search_list, response) {
            named_search_list.empty();
            named_search_list.select2('val', '');
            named_search_list.append("<option></option>");
            $.each(response['contacts_list'], function (item, element) {
                named_search_list.append("<option value='" + element['id'] + "'>" + element['name'] + "[" + element['language'] + "]</option>");
            });
            named_search_list.trigger("change");
            named_search_list.data('placeholder', 'List fully loaded. Select a contact filter');
            named_search_list.select2();
        }

        $(document).ready(function() {
            $('#select-bonus-template').select2().val("{{ $campaign_template->bonus_template_id }}").change();
            $('#select-voucher-template').select2().val("{{ $campaign_template->voucher_template_id }}").change();

            $('#sender_name').select2().val("{{ empty($campaign_template) ? 1 : $campaign_template->is_newsletter}}").change();

            var contact_test_list = $('#select-test-list').select2().change().on('change', function (e) {
                $('.send-test-campaign-btn[data-action="contact"]').prop('disabled', $(this).val() == '');
                $('.send-test-campaign-btn[data-action="show"]').prop('disabled', $(this).val() == '' && $("[name='test_username']").val() == '');
            });
            var named_search_list = $('#select-named-search');
            $("[name='test_username']").on('change', function(e) {
                $('.send-test-campaign-btn[data-action="username"]').prop('disabled', $(this).val() == '');
                $('.send-test-campaign-btn[data-action="show"]').prop('disabled', $(this).val() == '' && $('#select-test-list').val() == '');
            });

            $('#select-template').select2().val("{{ empty($campaign_template) ? $template_obj->id : $campaign_template->template_id }}").change().on('change', function(e) {
                if ($(this).val()=='') {
                    $('#campaign-test-btn').prop('disabled', true).prop('title', 'You must choose template');
                } else {
                    $('#campaign-test-btn').prop('disabled', false).prop('title', '');
                }
                $('#campaign-test-btn').prop('disabled', $(this).val()=='');
                console.log("Contact list loaded from Ajax call");
                $.ajax({
                    url: "{{ $app['url_generator']->generate("messaging.{$c_type->getName(true)}-templates.show") }}",
                    type: "POST",
                    data: {'template' : this.value},
                    success: function (response, textStatus, jqXHR) {
                        $('#template-content-box').html(response['html']);
                        generateContactList(named_search_list, response);
                        generateContactList(contact_test_list, response);
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('AJAX ERROR');
                    }
                });
            });

            named_search_list.select2().val("{{ $campaign_template->named_search_id }}").change();

            $('#campaign-test-btn').on('click', function (e) {
                e.preventDefault();
                $('#modal-result-msg').html();
                $('#send-test-campaign-btn').show();
                $('.send-test-campaign-btn').prop('disabled', true);
                $('#modal-result-msg').html('');
                $("[name='test_username']").val('');
                contact_test_list.val('').change();
                $('#campaign-test-modal').modal('show');
            });
            $('.send-test-campaign-btn').on('click', function (e) {
                e.preventDefault();

                var self = $(this);
                var previous_content = self.html();
                self.html('Processing...');
                self.prop('disabled', true);

                var modal_alert = $('#modal-alert-msg');
                if(self.data('action') == 'show') {
                    modal_alert.html('<p><b>Are you sure you want to generate the message?</b></p> <p>This option will not send any ' + (self.data('issms') ? 'SMS' : 'E-mail') + '.</p>');
                } else {
                    modal_alert.html('<p><b>Are you sure you want to send the test?</b></p> <p>This will be a real test and the message will be sent to recipients.</p>');
                }

                $('#check-send-modal').modal('show').one('click','.btn-ok', function(){
                    $('#check-send-modal').modal('hide');

                    $.ajax({
                        url: "{{ $app['url_generator']->generate('messaging.campaign-test') }}",
                        type: "POST",
                        data: {username: $("[name='test_username']").val(), action: self.data('action'), form: $('#schedule-form').serializeArray(), test_list: $("[name='test_list_id']").val(),
                            type: "{{ $c_type->getName(true) }}", raw_type: "{{ $c_type->getRawType() }}"},
                        success: function (response, textStatus, jqXHR) {
                            self.prop('disabled', false);
                            self.html(previous_content);
                            if (response['success'] == true) {
                                $('#modal-result-msg').html(response['msg']);
                            } else {
                                if (typeof response['exception'] == 'undefined') {
                                    console.log("Message: " + response);
                                    $('#modal-result-msg').html(response);
                                } else {
                                    $('#modal-result-msg').html("<div class='warning'>EXCEPTION: " + response['exception'] + "</div>");
                                }
                            }
                            return false;
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            alert('AJAX ERROR');
                        }
                    });

                }).one('click','.btn-close',function(){
                    self.prop('disabled', false);
                    self.html(previous_content);
                });
            });

            var date_picker_input = $('input[name="scheduled_time"]');

            date_picker_input.daterangepicker({
                minDate: new Date("{{ \Carbon\Carbon::now()->toDateTimeString() }}"),
                startDate: new Date("{{ \Carbon\Carbon::now()->toDateTimeString() }}"),
                autoUpdateInput: false,
                singleDatePicker: true,
                timePicker: true,
                timePicker24Hour: true,
                timePickerSeconds: true,
                locale: {
                    format: 'YYYY-MM-DD HH:mm:ss',
                    cancelLabel: 'Clear',
                    firstDay: 1
                }
            });

            @if($c_time)
                date_picker_input.val(picker.startDate.format("{{ $c_time }}"));
            @endif

            date_picker_input.on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm:ss'));
            }).on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

            var end_date_picker_input = $('#recurring-end-date');

            end_date_picker_input.daterangepicker({
                minDate: new Date("{{ \Carbon\Carbon::now()->toDateTimeString() }}"),
                startDate: new Date("{{ \Carbon\Carbon::now()->toDateTimeString() }}"),
                autoUpdateInput: false,
                singleDatePicker: true,
                timePicker: true,
                timePicker24Hour: true,
                timePickerSeconds: true,
                locale: {
                    format: 'YYYY-MM-DD HH:mm:ss',
                    cancelLabel: 'Clear',
                    firstDay: 1
                }
            });

            end_date_picker_input.on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD hh:mm:ss'));
            }).on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

            var bonus_type = "{{ $campaign_template->bonus_template_id }}";
            var voucher_type = "{{ $campaign_template->voucher_template_id }}";

            var type = 'no';
            if (bonus_type > 0) {
                type = 'bonus';
                $('#bonus-group').show();
            } else if (voucher_type > 0) {
                type = 'voucher';
                $('#voucher-group').show();
            }

            $('#sender_name').select2({
                minimumResultsForSearch: -1
            });

            $('#select-type').select2({
                minimumResultsForSearch: -1
            }).val(type).change().on('change', function(e) {
                if (this.value == 'no') {
                    $('#select-voucher-template').val('').change();
                    $('#select-bonus-template').val('').change();
                    $('#bonus-group').hide();
                    $('#voucher-group').hide();
                }
                if(this.value == 'bonus'){
                    $('#select-voucher-template').val('').change();
                    $('#bonus-group').show();
                    $('#voucher-group').hide();
                }
                if(this.value == 'voucher'){
                    $('#voucher-group').show();
                    $('#bonus-group').hide();
                    $('#select-bonus-template').val('').change().hide();
                }
            });

            $('#start-time-picker').datetimepicker({
                format: 'HH:mm:ss',
                useCurrent: false,
                icons: {
                    time: 'fa fa-clock',
                    date: 'fa fa-calendar',
                    up: 'fa fa-chevron-up',
                    down: 'fa fa-chevron-down',
                }
            });

            var recurring_type = "{{ !empty($campaign_template) ? $campaign_template->recurring_type : 'one' }}";

            if (recurring_type == 'one') {
                $('#one-time-only').show();
                $('#recurring-group').hide();
            } else {
                $('#one-time-only').hide();
                $('#recurring-group').show();
                if (recurring_type == 'day') {
                    $('#recurring-days-group').hide();
                } else {
                    $('#recurring-days-group').show();
                }
            }

            var map_recurring_days = {
                week: {
                    '1': 'Monday',
                    '2': 'Tuesday',
                    '3': 'Wednesday',
                    '4': 'Thursday',
                    '5': 'Friday',
                    '6': 'Saturday',
                    '7': 'Sunday'
                },
                month: function() {
                    var items = {};
                    for (i=1; i<=31; i++) {
                        if (i < 10) {
                            items['0'+i] = i;
                        } else {
                            items[''+i] = i;
                        }
                    }
                    return items;
                }()
            };
            var setupRecurringDays = function(type) {

                if (['month', 'week'].indexOf(type) > -1) {
                    $select = $("<select name='recurring_days[]' id='select-recurring_days' class='form-control select2-class' multiple='multiple' data-placeholder='' data-allow-clear='true' ></select>");

                    var value = JSON.parse($("#recurring-days").attr('value')); // todo: set the value on edit
                    var map = map_recurring_days[type];


                    Object.keys(map).sort().forEach(function($el) {
                        $select.append("<option value='" +$el+ "'>" +map[$el]+ "</option>");
                    });

                    $("#select-recurring_days").remove();
                    $("#recurring-days").parent().find('.select2').remove();
                    $("#recurring-days").parent().append($select);

                    value = value && recurring_type == type ? value : [];

                    $("#select-recurring_days").select2().val(value).change();
                }
            };
            setupRecurringDays(recurring_type);

            $('#select-recurring-type').select2({
                minimumResultsForSearch: -1
            }).val(recurring_type).change().on('change', function(e) {
                setupRecurringDays(this.value);

                if (this.value == 'one') {
                    $('#one-time-only').show();
                    $('#recurring-group').hide();
                } else {
                    $('#one-time-only').hide();
                    $('#recurring-group').show();
                    if (this.value == 'day') {
                        $('#recurring-days-group').hide();
                    } else {
                        $('#recurring-days-group').show();
                    }
                }
            });
        });
    </script>
@endsection
