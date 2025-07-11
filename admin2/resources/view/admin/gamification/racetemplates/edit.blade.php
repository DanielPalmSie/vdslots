@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.gamification.racetemplates.partials.topmenu')

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">Edit Race Template</h3>
                <div style="float: right">
                    <a href="{{ $app['url_generator']->generate('racetemplates.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>
            @include('admin.gamification.racetemplates.partials.racetemplatebox')
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script src="/phive/admin/customization/plugins/bootstrap-toggle/bootstrap-toggle.min.js"></script>

    @stack('extrajavascript')

    @include('admin.gamification.racetemplates.partials.racetemplatesharedjs')

    <script type="text/javascript">

        function saveRaceTemplate(button, callback_on_success) {
            var racetemplate_form = $('#racetemplate-form').serializeArray();

            if (button) {
                button.prop("disabled", true);
            }

            $.ajax({
                url: "{{ $app['url_generator']->generate('racetemplates.edit', ['racetemplate' => $racetemplate->id]) }}",
                type: "POST",
                data: racetemplate_form,
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

                        displayNotifyMessage('success', 'Race Template updated successfully.');

                    } else {
                        console.log(data);
                        displayNotifyMessage('error', 'Race Template update failed.');
                    }
                },
                error: function (jqXHR, text_status, error_thrown) {
                    console.log(error_thrown);
                    displayNotifyMessage('error', 'Race Template update failed. Error: '+error_thrown);
                }
            });
        }

        function deleteTrophyAward(button) {

            if (button) {
                button.prop("disabled", true);
            }

            $.ajax({
                url: "{{ $app['url_generator']->generate('racetemplates.delete', ['racetemplate' => $racetemplate->id]) }}",
                type: "POST",
                data: { 'id': {{ $racetemplate->id }} },
                success: function (data, text_status, jqXHR) {
                    if (data.success == true) {

                        displayNotifyMessage('success', 'Race Template deleted successfully. Loading list of Race Templates...');

                        setTimeout(function() {
                            window.location.replace("{{ $app['url_generator']->generate('racetemplates.index') }}");
                        }, 3000);
                    } else {
                        console.log(data);

                        if (button) {
                            button.prop("disabled", false);
                        }

                        displayNotifyMessage('error', 'Race Template delete failed.');
                    }
                },
                error: function (jqXHR, text_status, error_thrown) {
                    console.log(error_thrown);
                    if (button) {
                        button.prop("disabled", false);
                    }

                    displayNotifyMessage('error', 'Race Template delete failed. Error: '+error_thrown);
                }
            });
        }

        $(document).ready(function() {

            enableSelect2Controllers();

            $('#save-racetemplate-btn').on('click', function(e) {
                e.preventDefault();
                saveRaceTemplate(getAllNonModalButtons(), function() { // Note: Second parameter is a callback and is called if saved successfully.
                });
            });

            $('#save-all-btn').on('click', function(e) {
                e.preventDefault();
                saveRaceTemplate(getAllNonModalButtons(), function() { // Note: Second parameter is a callback and is called if saved successfully.
                });
            });

            $('#save-as-new-racetemplate-btn').on('click', function(e) {
                e.preventDefault();
                createRaceTemplate(getAllNonModalButtons(), function() { // Note: Second parameter is a callback and is called if create failed.
                });
            });

            $('#delete-modalbtn').on('click', function(e) {
                e.preventDefault();
                deleteRaceTemplate(getAllNonModalButtons());
            });

        });
    </script>
@endsection
