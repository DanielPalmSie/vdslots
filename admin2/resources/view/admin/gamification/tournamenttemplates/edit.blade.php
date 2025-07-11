@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.gamification.tournamenttemplates.partials.topmenu')

        <p>
            <div class="btn btn-primary" onclick="clearQueue('{{ $tournament_template->id }}')">
                Clear Queue for this Template
            </div>
            <span id="queue-clear-result"></span>
        </p>

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">Edit Tournament Template</h3>
                <div style="float: right">
                    <a href="{{ $app['url_generator']->generate('tournamenttemplates.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>
            @include('admin.gamification.tournamenttemplates.partials.tournamenttemplatebox')
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script src="/phive/admin/customization/plugins/bootstrap-toggle/bootstrap-toggle.min.js"></script>

    @stack('extrajavascript')

    @include('admin.gamification.tournamenttemplates.partials.tournamenttemplatesharedjs')

    <script type="text/javascript">

     function clearQueue(tid){
         $('#queue-clear-result').html('');

         $.ajax({
             url: "{{ $app['url_generator']->generate('tournamenttemplates.clearQueue') }}",
             type: "POST",
             data: {'tpl_id': tid},
             success: function (data, textStatus, jqXHR) {
                 $('#queue-clear-result').html(data);
             },
             error: function (jqXHR, textStatus, errorThrown) {
                 console.log(textStatus);
                 console.log(errorThrown);
                 displayNotifyMessage('error', textStatus);
             }
         });
     }


        function saveTournamentTemplate(button, callback_on_success) {
            var tournamenttemplate_form = $('#tournament-template-form').serializeArray();

            if (button) {
                button.prop("disabled", true);
            }

            $.ajax({
                url: "{{ $app['url_generator']->generate('tournamenttemplates.edit', ['tournament_template' => $tournament_template->id]) }}",
                type: "POST",
                data: tournamenttemplate_form,
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

                        displayNotifyMessage('success', 'Tournament Template updated successfully.');

                    } else {
                        console.log(data);

                        var attribute_errors = getAttributeErrors(data);

                        displayNotifyMessage('error', 'Tournament Template updated failed. ' + attribute_errors);
                    }
                },
                error: function (jqXHR, text_status, error_thrown) {
                    console.log(error_thrown);
                    displayNotifyMessage('error', 'Tournament Template update failed. Error: '+error_thrown);
                }
            });
        }

        function deleteTournamentTemplate(button) {

            if (button) {
                button.prop("disabled", true);
            }

            $.ajax({
                url: "{{ $app['url_generator']->generate('tournamenttemplates.delete', ['tournament_template' => $tournament_template->id]) }}",
                type: "POST",
                data: { 'id': {{ $tournament_template->id }} },
                success: function (data, text_status, jqXHR) {
                    if (data.success == true) {

                        displayNotifyMessage('success', 'Tournament Template deleted successfully.');

                        setTimeout(function() {
                            window.location.replace("{{ $app['url_generator']->generate('tournamenttemplates.index') }}");
                        }, 3000);
                    } else {
                        console.log(data);

                        if (button) {
                            button.prop("disabled", false);
                        }

                        displayNotifyMessage('error', 'Tournament Template delete failed.');
                    }
                },
                error: function (jqXHR, text_status, error_thrown) {
                    console.log(error_thrown);
                    if (button) {
                        button.prop("disabled", false);
                    }

                    displayNotifyMessage('error', 'Tournament Template delete failed. Error: '+error_thrown);
                }
            });
        }

        $(document).ready(function() {

            enableSelect2Controllers();
            enableDropZone();

            $('#save-tournament-template-btn').on('click', function(e) {
                e.preventDefault();
                saveTournamentTemplate(getAllNonModalButtons());
            });

            $('#save-as-new-tournament-template-btn').on('click', function(e) {
                e.preventDefault();
                createTournamentTemplate(getAllNonModalButtons());

                /*
                BootstrapDialog.show({
                    title: 'CONFIRMATION',
                    message: 'Are you sure you want to create a new template out of this one?',
                    type: BootstrapDialog.TYPE_PRIMARY,
                    size: BootstrapDialog.SIZE_LARGE,
                    closable: true,
                    draggable: true,
                    buttons: [
                        {
                            label: 'Cancel',
                            cssClass: 'btn-default',
                            action: function(dialogRef) {
                                dialogRef.close();
                            }
                        },
                        {
                            label: 'Yes',
                            cssClass: 'btn-primary',
                            action: function(dialogRef) {
                                dialogRef.close();
                                createTournamentTemplate(getAllNonModalButtons());
                            }
                        }
                    ]
                });
                */
            });

            $('#delete-modalbtn').on('click', function(e) {
                e.preventDefault();
                deleteTournamentTemplate(getAllNonModalButtons());
            });
        });
    </script>
@endsection
