<div class="card card-solid card-primary">
    <div class="card-header with-border">
        <h3 class="card-title">Game Overrides</h3>
    </div>

    <!-- /.card-header -->
    <div class="card-body">
        <form method="post" action="{{ $app['url_generator']->generate('games-create-override') }}" id="override_form">
            <input type="hidden" name="token" value="{{$_SESSION['token']}}">
            <input type="hidden" name="game_id" value="{{ $game['id'] }}">
            <input type="hidden" name="id" value="">
            <input type="hidden" name="only_json" value="true">
            <div class="form-group row">
                <label class="col-sm-2 col-form-label" for="name">
                    Country / jurisdiction (ISO2)
                </label>
                <div class="col-sm-3">
                    <input name="country" class="form-control" type="text" value="{{ $jurisdiction }}">
                </div>
                <div class="col-sm-4">
                    The jurisdiction of this override, ie the country of the regulator.
                </div>
            </div>

            @foreach($prepops as $col => $val)
                <div class="form-group row">
                    <label class="col-sm-2 col-form-label" for="name">
                        {{ $labels[$col] }}
                    </label>
                    <div class="col-sm-3">
                        <input name="{{ $col }}" class="form-control" type="text" value="{{ $val }}">
                    </div>
                    <div class="col-sm-4">
                        {{ $descrs[$col] }}.
                    </div>
                </div>
            @endforeach

            <div class="form-group">
                <button class="btn btn-primary float-right submit-form" type="submit">Save</button>
            </div>
        </form>

    </div>

    <div class="card-footer">
        <table class="table table-striped table-bordered">
            <tr>
                <th>Id</th>
                <th>Jurisdiction</th>
                <th>Internal game ID</th>
                <th>Ext game name / id</th>
                <th>Ext launch id / id</th>
                <th>RTP</th>
                <th>RTP Modifier</th>
                <th></th>
            </tr>
            @foreach($overrides as $o)
                <tr data-overrideid="{{$o->id}}">
                    <td class="override-id">{{ $o->id }}</td>
                    <td class="override-country">{{ $o->country }}</td>
                    <td class="override-game_id">{{ $o->game_id }}</td>
                    <td class="override-ext_game_id">{{ $o->ext_game_id }}</td>
                    <td class="override-ext_launch_id">{{ $o->ext_launch_id }}</td>
                    <td class="override-payout_percent">{{ $o->payout_percent }}</td>
                    <td class="override-payout_extra_percent">{{ $o->payout_extra_percent }}</td>
                    <td>
                        <button class="btn btn-warning" onclick="overrides.update('{{ $o->game_id }}','{{ $o->id }}')">Update</button>
                        <button class="btn btn-danger" onclick="overrides.delete('{{ $o->game_id }}','{{ $o->id }}')">Delete</button>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
    <div id="errorModal" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Error</h4>
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
    <div id="modal_confirm" class="modal fade" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Confirmation</h4>
                </div>
                <div class="modal-body">
                    <p></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">YES</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">NO</button>
                </div>
            </div>

        </div>
    </div>

</div>


@section('footer-javascript')
    @parent
    <script>
        $default_form = $("#override_form").clone();

        function modalErrorMessage(message){
            $(".modal-body").text(message);
            $('#errorModal').modal('show');
            return false;
        }

        var overrides = {

            resetForm: function () {
                $("#override_form").html($default_form.html());
                $("#override_form .submit-form").click(overrides.createCallback);
            },

            delete: function (game_id, override_id) {
                $('#modal_confirm .modal-body>p').html('Are you sure you want to delete selected override?');
                $('#modal_confirm .modal-footer>.btn-danger').off('click').click(function () {
                    $.get("{{  $app['url_generator']->generate('games-delete-override') }}", {
                        game_id: game_id,
                        id: override_id
                    })
                        .done(function (data) {
                            $('#modal_confirm').modal('hide');
                            $('[data-overrideid="'+override_id+'"]').remove();
                            window.location.hash = "edit-overrides";
                        });
                });
                $('#modal_confirm').modal('show');

                return false;
            },

            fillInForm: function($override, $override_form) {
                $override_form.find("[name='id']").val($override.find('.override-id').text());
                $override_form.find("[name='country']").val($override.find('.override-country').text());
                $override_form.find("[name='ext_game_id']").val($override.find('.override-ext_game_id').text());
                $override_form.find("[name='ext_launch_id']").val($override.find('.override-ext_launch_id').text());
                $override_form.find("[name='payout_percent']").val($override.find('.override-payout_percent').text());
                $override_form.find("[name='payout_extra_percent']").val($override.find('.override-payout_extra_percent').text());
            },

            update: function (game_id, override_id) {
                var $override_form = $("#override_form");

                overrides.fillInForm($('[data-overrideid="'+override_id+'"]'), $override_form);

                $override_form.attr('action', "{{ $app['url_generator']->generate('games-update-override') }}");
                $override_form.find(".submit-form").attr("data-overrideid", override_id);
                $override_form.find(".submit-form").off('click');
                $override_form.find(".submit-form").click(overrides.updateCallback);

                window.location.hash = "";
                window.location.hash = "edit-overrides";
            },

            updateCallback: function (e) {
                e.preventDefault();
                var url = "{{ $app['url_generator']->generate('games-update-override') }}";
                var $override_form = $("#override_form");
                var $override = $("[data-overrideid='"+$(this).data('overrideid')+"']");
                $.post(url, $override_form.serialize())
                    .done(function (res) {
                        if (!empty(res.error)) {
                            modalErrorMessage(res.error);
                        } else {
                            $override.find('.override-id').text($override_form.find("[name='id']").val());
                            $override.find('.override-country').text($override_form.find("[name='country']").val());
                            $override.find('.override-ext_game_id').text($override_form.find("[name='ext_game_id']").val());
                            $override.find('.override-ext_launch_id').text($override_form.find("[name='ext_launch_id']").val());
                            $override.find('.override-payout_percent').text($override_form.find("[name='payout_percent']").val());
                            $override.find('.override-payout_extra_percent').text($override_form.find("[name='payout_extra_percent']").val());
                        }

                        overrides.resetForm();
                    });
                return false;
            },

            createCallback: function (e) {
                e.preventDefault();
                $.post("{{ $app['url_generator']->generate('games-create-override') }}", $("#override_form").serialize())
                    .done(function (res) {
                        if (!empty(res.error)) {
                            modalErrorMessage(res.error);
                        } else {
                            window.location.hash = "edit-overrides";
                            window.location.reload();
                        }
                    });
                return false;
            }
        };
        $("document").ready(function () {
            $default_form = $("#override_form").clone();

            setTimeout(function () {
                aux_hash = window.location.hash;
                window.location.hash = "";
                window.location.hash = aux_hash;
                overrides.resetForm();
            }, 200);
        })
    </script>
@endsection
