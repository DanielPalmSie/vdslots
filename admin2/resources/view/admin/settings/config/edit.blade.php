@extends('admin.layout')

@section('header-css')
    @parent
    <link rel="stylesheet" type="text/css" href="/phive/admin/plugins/bootstrap4-duallistbox/bootstrap-duallistbox.css">
    <link rel="stylesheet" type="text/css" href="/phive/admin/customization/plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css">
@endsection

@section('content')
    <div class="container-fluid">

        @include('admin.settings.config.partials.topmenu')

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">Edit Config</h3>
                <div class="float-right">
                    <a href="{{ $app['url_generator']->generate('settings.config.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>
            @include('admin.settings.config.partials.configbox')
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent

    <script src="/phive/admin/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js"></script>
    <script src="/phive/admin/plugins/customization/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js"></script>

    @stack('extrajavascript')

    @include('admin.settings.config.partials.configsharedjs')

    <script type="text/javascript">

        function saveConfig(button, callback_on_success) {
            var config_form = $('#config-form').serializeArray();

            if (button) {
                button.prop("disabled", true);
            }

            var config_values = $('.select-config_value').val();
            if (config_values instanceof Array) {
                for (var i = 0; i < config_values.length; i++) {
                    var key = 'config_value['+i+']';
                    var d = {'name': 'config_value['+i+']', 'value': config_values[i]};
                    config_form.push(d);
                }
            }

            $.ajax({
                url: "{{ $app['url_generator']->generate('settings.config.edit', ['config' => $config->id]) }}",
                type: "POST",
                data: config_form,
                complete: function() {
                    if (button) {
                        button.prop("disabled", false);
                    }
                },
                success: function (data, text_status, jqXHR) {
                    if (data.success == true) {

                        if (callback_on_success && typeof(callback_on_success) === "function") {
                            callback_on_success();
                        }

                        displayNotifyMessage('success', 'Config updated successfully.');

                    } else {
                        console.log(data);
                        var attribute_errors = getAttributeErrors(data);
                        displayNotifyMessage('error', 'Config updated failed. '+attribute_errors+(data.error?data.error:''));
                    }
                },
                error: function (jqXHR, text_status, error_thrown) {
                    console.log(error_thrown);
                    displayNotifyMessage('error', 'Config update failed. Error: '+error_thrown);
                }
            });
        }

        function deleteConfig(button) {

            if (button) {
                button.prop("disabled", true);
            }

            $.ajax({
                url: "{{ $app['url_generator']->generate('settings.config.delete', ['config' => $config->id]) }}",
                type: "POST",
                data: { 'id': {{ $config->id }} },
                success: function (data, text_status, jqXHR) {
                    if (data.success == true) {

                        displayNotifyMessage('success', 'Config deleted successfully.');

                        setTimeout(function() {
                            window.location.replace("{{ $app['url_generator']->generate('settings.config.index') }}");
                        }, 3000);
                    } else {
                        console.log(data);

                        if (button) {
                            button.prop("disabled", false);
                        }

                        displayNotifyMessage('error', 'Config delete failed.');
                    }
                },
                error: function (jqXHR, text_status, error_thrown) {
                    console.log(error_thrown);
                    if (button) {
                        button.prop("disabled", false);
                    }

                    displayNotifyMessage('error', 'Config delete failed. Error: '+error_thrown);
                }
            });
        }

        $(document).ready(function() {

            enableSelect2Controllers();

            $('#save-config-btn').on('click', function(e) {
                e.preventDefault();
                saveConfig(getAllNonModalButtons());
            });

            $('#save-all-btn').on('click', function(e) {
                e.preventDefault();
                saveConfig(getAllNonModalButtons(), function() { // Note: Second parameter is a callback and is called if saved successfully.
                });
            });

            $('#save-as-new-config-btn').on('click', function(e) {
                e.preventDefault();
                createConfig(getAllNonModalButtons(), function() { // Note: Second parameter is a callback and is called if create failed.
                });
            });

            $('#delete-modalbtn').on('click', function(e) {
                e.preventDefault();
                deleteConfig(getAllNonModalButtons());
            });

// TODO: Add class or something else to do this on.
@if (in_array($config_type_json['type'], ['ISO2','iso2']))
            /* Bootstrap Duallist */
            var bootstrapDualListbox = $('body .select-config_value').bootstrapDualListbox({
                nonSelectedListLabel: 'Available',
                selectedListLabel: 'Selected',
                moveOnSelect: false,
            });

            $('body .select-config_value').select2().on('change', function() {
                $('body .select-config_value').bootstrapDualListbox('refresh');
            }).trigger('change');
@endif

        });
    </script>
@endsection
