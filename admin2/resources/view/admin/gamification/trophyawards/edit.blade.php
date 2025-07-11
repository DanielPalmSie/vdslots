@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.gamification.trophyawards.partials.topmenu')

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">Edit Trophy Award</h3>
                <div style="float: right">
                    <a href="{{ $app['url_generator']->generate('trophyawards.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>
            @include('admin.gamification.trophyawards.partials.trophyawardbox')
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script src="/phive/admin/customization/plugins/bootstrap-toggle/bootstrap-toggle.min.js"></script>

    @stack('extrajavascript')

    @include('admin.gamification.trophyawards.partials.trophyawardsharedjs')

    <script type="text/javascript">

        function saveTrophyAward(button, callback_on_success) {
            var trophyaward_form = $('#trophyaward-form').serializeArray();

            if (button) {
                button.prop("disabled", true);
            }

            $.ajax({
                url: "{{ $app['url_generator']->generate('trophyawards.edit', ['trophyaward' => $trophyaward->id]) }}",
                type: "POST",
                data: trophyaward_form,
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

                        displayNotifyMessage('success', 'Trophy Award updated successfully.');

                    } else {
                        console.log(data);
                        displayNotifyMessage('error', 'Trophy Award update failed.');
                    }
                },
                error: function (jqXHR, text_status, error_thrown) {
                    console.log(error_thrown);
                    displayNotifyMessage('error', 'Trophy Award update failed. Error: '+error_thrown);
                }
            });
        }

        function deleteTrophyAward(button) {

            if (button) {
                button.prop("disabled", true);
            }

            $.ajax({
                url: "{{ $app['url_generator']->generate('trophyawards.delete', ['trophyaward' => $trophyaward->id]) }}",
                type: "POST",
                data: { 'id': {{ $trophyaward->id }} },
                success: function (data, text_status, jqXHR) {
                    if (data.success == true) {

                        displayNotifyMessage('success', 'Trophy Award deleted successfully. Loading list of Trophy Awards...');

                        setTimeout(function() {
                            window.location.replace("{{ $app['url_generator']->generate('trophyawards.index') }}");
                        }, 3000);
                    } else {
                        console.log(data);

                        if (button) {
                            button.prop("disabled", false);
                        }

                        displayNotifyMessage('error', 'Trophy Award delete failed.');
                    }
                },
                error: function (jqXHR, text_status, error_thrown) {
                    console.log(error_thrown);
                    if (button) {
                        button.prop("disabled", false);
                    }

                    displayNotifyMessage('error', 'Trophy Award delete failed. Error: '+error_thrown);
                }
            });
        }

        $(document).ready(function() {

            enableSelect2Controllers();
            enableDropZone();

            $('#save-trophyaward-btn').on('click', function(e) {
                e.preventDefault();
                saveTrophyAward(getAllNonModalButtons(), function() { // Note: Second parameter is a callback and is called if saved successfully.
                });
            });

            $('#save-all-btn').on('click', function(e) {
                e.preventDefault();
                saveTrophyAward(getAllNonModalButtons(), function() { // Note: Second parameter is a callback and is called if saved successfully.
                });
            });

            $('#save-as-new-trophyaward-btn').on('click', function(e) {
                e.preventDefault();
                createTrophyAward(getAllNonModalButtons(), function() { // Note: Second parameter is a callback and is called if create failed.
                });
            });

            $('#delete-modalbtn').on('click', function(e) {
                e.preventDefault();
                deleteTrophyAward(getAllNonModalButtons());
            });

        });
    </script>
@endsection
