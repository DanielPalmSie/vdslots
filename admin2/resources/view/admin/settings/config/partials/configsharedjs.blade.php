
@include('admin.settings.config.partials.sharedjs')
@include('admin.partials.js')

<script type="text/javascript">

    function enableSelect2Controllers() {
        $(".select2-class").select2({
            tags: true,
            selectOnBlur: true,
        });
    }

    function createConfig(button, callback_on_failure) {
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
            url: "{{ $app['url_generator']->generate('settings.config.new') }}",
            type: "POST",
            data: config_form,
            success: function (data, text_status, jqXHR) {
                if (data.success == true) {

                    displayNotifyMessage('success', 'Config created successfully. Loading new Config...');
                    setTimeout(function() {
                        var redirect_to = "{{ $app['url_generator']->generate('settings.config.edit', ['config' => -1]) }}";
                        redirect_to = redirect_to.replace("-1", data.config.id);
                        window.location.replace(redirect_to);
                    }, 3000);

                } else {
                    console.log(data);

                    if (button) {
                        button.prop( "disabled", false);
                    }

                    if (callback_on_failure && typeof(callback_on_failure) === "function") {
                        callback_on_failure();
                    }

                    var attribute_errors = getAttributeErrors(data);
                    displayNotifyMessage('error', 'Config creation failed. '+attribute_errors+(data.error?data.error:''));
                }
            },
            error: function (jqXHR, text_status, error_thrown) {
                console.log(error_thrown);

                if (button) {
                    button.prop( "disabled", false);
                }

                if (callback_on_failure && typeof(callback_on_failure) === "function") {
                    callback_on_failure();
                }

                displayNotifyMessage('error', 'Config creation failed. Error: '+error_thrown);
            }
        });
    }

    $(document).ready(function() {
        enableSelect2Controllers();
    });

</script>
