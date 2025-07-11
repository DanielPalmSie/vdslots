@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.gamification.bonustypes.partials.topmenu')

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">Edit Bonus Type</h3>
                <div style="float: right">
                    <a href="{{ $app['url_generator']->generate('bonustypes.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>
            @include('admin.gamification.bonustypes.partials.bonustypebox')
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script src="/phive/admin/customization/plugins/bootstrap-toggle/bootstrap-toggle.min.js"></script>

    @stack('extrajavascript')

    @include('admin.gamification.bonustypes.partials.bonustypesharedjs')

    <script type="text/javascript">

        function saveBonusType(button, callback_on_success) {
            var bonustype_form = $('#bonustype-form').serializeArray();

            if (button) {
                button.prop("disabled", true);
            }

            $.ajax({
                url: "{{ $app['url_generator']->generate('bonustypes.edit', ['bonustype' => $bonustype->id]) }}",
                type: "POST",
                data: bonustype_form,
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

                        displayNotifyMessage('success', 'Bonus Type updated successfully.');

                    } else {
                        console.log(data);

                        var attribute_errors = getAttributeErrors(data);

                        displayNotifyMessage('error', 'Bonus Type updated failed. ' + attribute_errors);
                    }
                },
                error: function (jqXHR, text_status, error_thrown) {
                    console.log(error_thrown);
                    displayNotifyMessage('error', 'Bonus Type update failed. Error: '+error_thrown);
                }
            });
        }

        function deleteBonusType(button) {

            if (button) {
                button.prop("disabled", true);
            }

            $.ajax({
                url: "{{ $app['url_generator']->generate('bonustypes.delete', ['bonustype' => $bonustype->id]) }}",
                type: "POST",
                data: { 'id': {{ $bonustype->id }} },
                success: function (data, text_status, jqXHR) {
                    if (data.success == true) {

                        displayNotifyMessage('success', 'Bonus Type deleted successfully.');

                        setTimeout(function() {
                            window.location.replace("{{ $app['url_generator']->generate('bonustypes.index') }}");
                        }, 3000);
                    } else {
                        console.log(data);

                        if (button) {
                            button.prop("disabled", false);
                        }

                        displayNotifyMessage('error', 'Bonus Type delete failed.');
                    }
                },
                error: function (jqXHR, text_status, error_thrown) {
                    console.log(error_thrown);
                    if (button) {
                        button.prop("disabled", false);
                    }

                    displayNotifyMessage('error', 'Bonus Type delete failed. Error: '+error_thrown);
                }
            });
        }

        $(document).ready(function() {

            enableSelect2Controllers();

            $('.timepicker').datetimepicker({
                format: 'HH:mm',
                icons: {
                    time: 'fa fa-clock',
                    date: 'fa fa-calendar',
                    up: 'fa fa-chevron-up',
                    down: 'fa fa-chevron-down',
                }
            });

            $('#save-bonustype-btn').on('click', function(e) {
                e.preventDefault();
                saveBonusType(getAllNonModalButtons());
            });

            $('#save-as-new-bonustype-btn').on('click', function(e) {
                e.preventDefault();

                createBonusType(getAllNonModalButtons());

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
                                createBonusType(getAllNonModalButtons());
                            }
                        }
                    ]
                });
                */
            });

            $('#delete-modalbtn').on('click', function(e) {
                e.preventDefault();
                deleteBonusType(getAllNonModalButtons());
            });
        });
    </script>
@endsection
