@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.gamification.trophies.partials.topmenu')

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">Edit Trophy</h3>
                <div style="float: right">
                    <a href="{{ $app['url_generator']->generate('trophies.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>
            @include('admin.gamification.trophies.partials.trophybox')
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script src="/phive/admin/customization/plugins/bootstrap-toggle/bootstrap-toggle.min.js"></script>

    @stack('extrajavascript')

    @include('admin.gamification.trophies.partials.trophysharedjs')

    <script type="text/javascript">

        function saveTrophy(button, callback_on_success) {
            var trophy_form = $('#trophy-form').serializeArray();

            if (button) {
                button.prop("disabled", true);
            }

            $.ajax({
                url: "{{ $app['url_generator']->generate('trophies.edit', ['trophy' => $trophy->id]) }}",
                type: "POST",
                data: trophy_form,
                complete: function() {
                    if (button) {
                        button.prop("disabled", false);
                    }
                },
                success: function (data, text_status, jqXHR) {
                    if (data.success == true) {

                        updateTrophyMain();
                        synchronizeLocalizedStringsWithAlias();

                        if (callback_on_success && typeof(callback_on_success) === "function") {
                            callback_on_success();
                        }

                        displayNotifyMessage('success', 'Trophy updated successfully.');

                    } else {
                        console.log(data);

                        var attribute_errors = getAttributeErrors(data);
                        displayNotifyMessage('error', 'Trophy updated failed. '+attribute_errors+(data.error?data.error:''));
                    }
                },
                error: function (jqXHR, text_status, error_thrown) {
                    console.log(error_thrown);
                    displayNotifyMessage('error', 'Trophy update failed. Error: '+error_thrown);
                }
            });
        }

        function saveLocalizedString(button) {
            var language_form = $('#trophy-language-form').serializeArray();

            if (button) {
                button.prop("disabled", true);
            }

            $.ajax({
                url: "{{ $app['url_generator']->generate('localizedstrings.update') }}",
                type: "POST",
                data: language_form,
                complete: function() {
                    if (button) {
                        button.prop("disabled", false);
                    }
                },
                success: function (data, text_status, jqXHR) {
                    if (data.success == true) {
                        displayNotifyMessage('success', 'Localized Strings saved successfully.');
                    } else {
                        console.log(data);

                        var attribute_errors = getAttributeErrors(data);
                        displayNotifyMessage('error', 'Localized Strings save failed. '+attribute_errors+(data.error?data.error:''));
                    }
                },
                error: function (jqXHR, text_status, error_thrown) {
                    console.log(error_thrown);
                    displayNotifyMessage('error', 'Localized Strings save failed. Error: '+error_thrown);
                }
            });
        }

        function deleteTrophy(button) {

            if (button) {
                button.prop("disabled", true);
            }

            $.ajax({
                url: "{{ $app['url_generator']->generate('trophies.delete', ['trophy' => $trophy->id]) }}",
                type: "POST",
                data: { 'id': {{ $trophy->id }} },
                success: function (data, text_status, jqXHR) {
                    if (data.success == true) {

                        displayNotifyMessage('success', 'Trophy deleted successfully.');

                        setTimeout(function() {
                            window.location.replace("{{ $app['url_generator']->generate('trophies.index') }}");
                        }, 3000);
                    } else {
                        console.log(data);

                        if (button) {
                            button.prop("disabled", false);
                        }

                        displayNotifyMessage('error', 'Trophy delete failed.');
                    }
                },
                error: function (jqXHR, text_status, error_thrown) {
                    console.log(error_thrown);
                    if (button) {
                        button.prop("disabled", false);
                    }

                    displayNotifyMessage('error', 'Trophy delete failed. Error: '+error_thrown);
                }
            });
        }

        // TODO: Could probably be moved to the sharedjs file.
        function doUpdatedAliasUpdates() {
            var input_alias = $("#input-alias");
            var uniqueid    = input_alias.data('uniqueid');
            var new_alias   = input_alias.val();

            updateTrophyImages(uniqueid, new_alias);
        }

        function updateTrophyMain() {
            var new_alias = $("#input-alias").val();
            $(".trophy_main").each(function() {
                var trophy_main = new_alias.split('_')[0];
                $(this).text(trophy_main);
            });
        }

        $(document).ready(function() {

            enableSelect2Controllers();
            enableDropZone();

            $('#save-trophy-btn').on('click', function(e) {
                e.preventDefault();
                saveTrophy(getAllNonModalButtons());
            });

            $('#save-language-btn').on('click', function(e) {
                e.preventDefault();
                saveLocalizedString($(this));
            });

            $('#save-all-btn').on('click', function(e) {
                e.preventDefault();
                saveTrophy(getAllNonModalButtons(), function() { // Note: Second parameter is a callback and is called if saved successfully.
                    saveLocalizedString();
                });
            });

            $('#save-as-new-trophy-btn').on('click', function(e) {
                e.preventDefault();
                var old_alias = synchronizeLocalizedStringsWithAlias(); // Save old alias in case the save will fail so we can revert Localized Strings.
                createTrophy(getAllNonModalButtons(), function() { // Note: Second parameter is a callback and is called if create failed.
                    synchronizeLocalizedStringsWithAlias(old_alias);
                });
            });

            $('#delete-modalbtn').on('click', function(e) {
                e.preventDefault();
                deleteTrophy(getAllNonModalButtons());
            });

            // TODO: Could probably be moved to the sharedjs file.
            $("#input-alias").keyup(function(e) {
                doUpdatedAliasUpdates();
            });

            $("#input-alias").change(function(e) {
                doUpdatedAliasUpdates();
            });

        });
    </script>
@endsection
