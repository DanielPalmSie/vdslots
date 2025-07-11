<div class="card card-solid card-primary">
    <div class="card-header">
        <h3 class="card-title">Edit Game Features</h3>
    </div>

    <form role="form" id="frm_page">
        <div class="card-body">
            <div class="row">
                <div class="col-md-1" style="padding: 20px;">
                    <a class="btn btn-danger" onclick="gameFeatures.loadDefaults({{ $game->id }});return false;">Load
                        defaults</a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Name</label>
                        <p class="help-block">Only allowed "a-z%-" characters</p>
                    </div>
                </div>
                <div class="col-sm-1">
                    <div class="form-group row">
                        <label>Type</label>
                    </div>
                </div>
                <div class="col-sm-1">
                    <div class="form-group">
                        <label for="i-sub_type">Sub Type</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group row">
                        <label>Value</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <button class="btn btn-danger" id="remove-selected"> Remove selected</button>
                    </div>
                </div>
            </div>
            @foreach($features as $row)
                <div class="row" data-featureid="{{$row->id}}">
                    <div class="col-md-2">
                        <div class="form-group">
                            <input type="text" readonly="readonly" class="form-control" name="name[{{ $row->id }}]"
                                   placeholder="Name" value="{{ str_replace('_', ' ', $row->name) }}">
                        </div>
                    </div>
                    <div class="col-sm-1">
                        <div class="form-group row">
                            <select name="type[{{ $row->id }}]" class="form-control">
                                @foreach(\App\Models\GameFeatures::$types as $id => $val)
                                    <option @if($id == $row->type)selected="selected"
                                            @endif value="{{ $id }}">{{ $val }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-1">
                        <div class="form-group">
                            <select name="sub_type[{{ $row->id }}]" class="form-control">
                                @foreach(\App\Models\GameFeatures::$sub_types as $id => $val)
                                    <option @if($id == $row->sub_type)selected="selected"
                                            @endif value="{{ $id }}">{{ $val }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group row">
                            <input type="text" class="form-control" name="value[{{ $row->id }}]" placeholder="Value"
                                   value="{{ $row->value }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <a href="#" onclick="gameFeatures.remove({{ $row->id }});return false;"
                               class="btn btn-danger">Remove</a>
                            <input type="checkbox" name="remove_list" value="{{$row->id}}">Select to be removed
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <input type="text" class="form-control" name="new_name" id="new_name" placeholder="Name"
                               value="">
                        <i class="help-block" style="color:red;" id="name_error"></i>
                    </div>
                </div>
                <div class="col-sm-1">
                    <div class="form-group row">
                        <select name="new_type" id="new_type" class="form-control">
                            @foreach(\App\Models\GameFeatures::$types as $id => $val)
                                <option value="{{ $id }}">{{ $val }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-1">
                    <div class="form-group">
                        <select name="new_sub_type" id="new_sub_type" class="form-control">
                            @foreach(\App\Models\GameFeatures::$sub_types as $id => $val)
                                <option value="{{ $id }}">{{ $val }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group row">
                        <input type="text" class="form-control" name="new_value" id="new_value" placeholder="Value"
                               value="">
                        <i class="help-block" style="color:red;" id="value_error"></i>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <a href="#" onclick="gameFeatures.add({{ $game->id }});return false;" class="btn btn-primary">Add</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal -->
<div id="modal_confirm" class="modal fade" role="dialog">
    <div class="modal-dialog">

        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Confirmation</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
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


@section('footer-javascript')
    @parent
    <script>

        var gameFeatures = {

            save: function (id) {

                $.post('{{ $app['url_generator']->generate('settings.games.features.save') }}?id=' + id, $('#frm_page').serialize())
                    .done(function (res) {
                        //location.href='../edit/?id='+res.id;
                        //location.reload();
                    })
                    .fail(function (xhr, textStatus, errorThrown) {
                        if (xhr.responseJSON) {
                            $.each(xhr.responseJSON, function (idx, val) {
                                //alert(idx+': '+val);
                                var obj;
                                if (obj = $('#' + idx + '_error')) {
                                    obj.show();
                                    obj.html(val[0]);
                                    setTimeout(function () {
                                        obj.fadeOut(1000, function () {
                                            obj.html('').css('opacity', 100);
                                        });
                                    }, 3000);
                                }
                            });
                        }
                        window.location.hash = "edit-features";
                    });

                return false;
            },

            add: function (gameId) {

                $.post('{{ $app['url_generator']->generate('settings.games.features.add') }}', {
                    game_id: gameId,
                    name: $('#new_name').val(),
                    type: $('#new_type').val(),
                    sub_type: $('#new_sub_type').val(),
                    value: $('#new_value').val()
                })
                    .done(function (res) {
                        window.location.hash = "edit-features";
                        location.reload();
                    })
                    .fail(function (xhr, textStatus, errorThrown) {
                        if (xhr.responseJSON) {
                            $.each(xhr.responseJSON, function (idx, val) {
                                //alert(idx+': '+val);
                                var obj;
                                if (obj = $('#' + idx + '_error')) {
                                    obj.show();
                                    obj.html(val[0]);
                                    setTimeout(function () {
                                        obj.fadeOut(1000, function () {
                                            obj.html('').css('opacity', 100);
                                        });
                                    }, 3000);
                                }
                            });
                        }
                    });
            },

            remove: function (id) {

                $('#modal_confirm .modal-body>p').html('Are you sure you want to delete selected feature?');
                $('#modal_confirm .modal-footer>.btn-danger').off('click').click(function () {
                    $.post('{{ $app['url_generator']->generate('settings.games.features.remove') }}', {id: id})
                        .done(function (data) {
                            $('#modal_confirm').modal('hide');
                            $('[data-featureid="'+id+'"]').remove();
                            window.location.hash = "edit-features";
                        });
                });
                $('#modal_confirm').modal('show');
            },

            removeSelected: function(e) {
                e.preventDefault();
                $('#modal_confirm .modal-body>p').html('Are you sure you want to delete the selected features?');
                $('#modal_confirm .modal-footer>.btn-danger').off('click').click(function () {
                    var items = $("[name='remove_list']")
                        .filter(function() {
                            return $(this).is(':checked');
                        })
                        .map(function() {
                            return $(this).val();
                        })
                        .toArray();

                    $.post('{{ $app['url_generator']->generate('settings.games.features.remove') }}', {id: 0, items: items})
                        .done(function (data) {
                            $('#modal_confirm').modal('hide');

                            data.items.forEach(function(id) {
                                $('[data-featureid="'+id+'"]').remove();
                            });

                            window.location.hash = "edit-features";
                        });
                });
                $('#modal_confirm').modal('show');
            },

            loadDefaults: function (id) {
                confirm.yes = function () {

                    confirm.close();

                    $.post('{{ $app['url_generator']->generate('settings.games.features.load-defaults') }}', {game_id: id}, function (data) {
                        window.location.hash = "edit-features";
                        location.reload();
                    });
                };
                confirm.dlg('Load Defaults', 'Are you sure you want to add default values to existing list?<br><br><i>Already existing values will not be overwritten.</i>');
            }

        };

        $(document).ready(function() {
            $("#remove-selected").click(gameFeatures.removeSelected)
        })

    </script>
@endsection
