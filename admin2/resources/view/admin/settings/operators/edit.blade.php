@extends('admin.layout')

@section('header-css')
    @parent
    <link rel="stylesheet" href="/phive/admin/customization/styles/css/tokenize2.min.css">
@endsection

@section('content')
    <div class="container-fluid">

        @include('admin.settings.games.partials.topmenu')

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">@if($item->id) Edit @else Add @endif Operator</h3>
                <div class="float-right">
                    <a href="{{ $app['url_generator']->generate('settings.operators.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>

            <form role="form" id="frm_page">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="i-network">Network *</label>
                                <select id="i-network" name="network" class="form-control apply-select2">
                                    <option></option>
                                    @foreach($networks as $row)
                                        <option @if($row == $item->network)selected="selected"@endif>{{ $row }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="i-name">Operator Name *</label>
                                <input type="text" class="form-control" id="i-name" name="name" placeholder="Enter name" value="{{ $item->name }}">
                                <i class="help-block text-danger" id="name_error"></i>
                            </div>
                        </div>
                        <div class="row"></div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="i-branded_op_fee">Branded Op.Fee</label>
                                @if(!p('settings.games.section.op_fee'))
                                    <input type="hidden" class="form-control" id="i-branded_op_fee-placeholder" name="branded_op_fee" placeholder="Operational fee" value="{{ $item->branded_op_fee }}">
                                    <input type="text" class="form-control" id="i-branded_op_fee" name="branded_op_fee" placeholder="Operational fee" disabled value="{{ $item->branded_op_fee }}">
                                    <button class="btn btn-default unlock_op_fee_branded">Unlock</button>
                                @else
                                    <input type="text" class="form-control" id="i-branded_op_fee" name="branded_op_fee" placeholder="Operational fee" value="{{ $item->branded_op_fee }}">
                                @endif
                            </div>
                            <i class="help-block text-danger" id="branded_op_fee_error"></i>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="i-non_branded_op_fee">Non Branded Op.Fee</label>
                                @if(!p('settings.games.section.op_fee'))
                                    <input type="hidden" class="form-control" id="i-non_branded_op_fee-placeholder" name="non_branded_op_fee" placeholder="Operational fee" value="{{ $item->non_branded_op_fee }}">
                                    <input type="text" class="form-control" id="i-non_branded_op_fee" name="non_branded_op_fee" placeholder="Operational fee" disabled value="{{ $item->non_branded_op_fee }}">
                                    <button class="btn btn-default unlock_op_fee">Unlock</button>
                                @else
                                    <input type="text" class="form-control" id="i-non_branded_op_fee" name="non_branded_op_fee" placeholder="Operational fee" value="{{ $item->non_branded_op_fee }}">
                                @endif
                            </div>
                            <i class="help-block text-danger" id="non_branded_op_fee_error"></i>
                        </div>
                        <div class="row"></div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="i-blocked_countries">Blocked countries - Branded</label>
                                <select id="i-blocked_countries" multiple="multiple" class="form-control">
                                    @foreach($blocked_countries as $id => $row)
                                        <option value="{{ $id }}" selected="selected">{{ $row }}</option>
                                    @endforeach
                                </select>
                                <input style="display:none" class="form-control" type="text" id="i-blocked_countries_man"
                                    name="blocked_countries" placeholder="Blocked Countries" value="{{ $item->blocked_countries }}">
                                <p class="help-block">Example: PG UG PA BG GY</p>
                                <i class="help-block text-danger" id="blocked_countries_error"></i>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="i-blocked_countries_non_branded">Blocked countries - Non Branded </label>
                                <select id="i-blocked_countries_non_branded" multiple="multiple" class="form-control">
                                    @foreach($blocked_countries_non_branded as $id => $row)
                                        <option value="{{ $id }}" selected="selected">{{ $row }}</option>
                                    @endforeach
                                </select>
                                <input style="display:none" class="form-control" type="text" id="i-blocked_countries_non_branded_man"
                                    name="blocked_countries_non_branded" placeholder="Blocked countries - Non Branded" value="{{ $item->blocked_countries_non_branded }}">
                                <p class="help-block">Example: PG UG PA BG GY</p>
                                <i class="help-block text-danger" id="blocked_countries_non_branded_error"></i>
                            </div>
                        </div>
                        <div class="row"></div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="i-blocked_countries_jackpot">Blocked countries jackpot</label>
                                <select id="i-blocked_countries_jackpot" multiple="multiple" class="form-control">
                                    @foreach($blocked_countries_jackpot as $id => $row)
                                        <option value="{{ $id }}" selected="selected">{{ $row }}</option>
                                    @endforeach
                                </select>
                                <input style="display:none" class="form-control" type="text" id="i-blocked_countries_jackpot_man"
                                    name="blocked_countries_jackpot" placeholder="Blocked ountries jackpot" value="{{ $item->blocked_countries_jackpot }}">
                                <i class="help-block text-danger" id="blocked_countries_jackpot_error"></i>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="switch-format-button" style="color: #fff;">Switch format</label>
                                <input type="button" value="Switch format"  class="form-control btn btn-danger" id="switch-format-button" onclick="operator.switchFormat();return false;">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.card-body -->

                <div class="card-footer">
                    <a onclick="operator.save('@if($item->id){{ $item->id }}@else{{0}}@endif');return false;" class="btn btn-primary float-right">Save</a>

                    @if($item->id)
                        <a class="btn btn-danger float-right" onclick="operator.massUpdate('{{ $item->id }}');return false;" style="margin-left: 10px;margin-right: 10px;">Update All Games</a>
                    @endif

                    @if(false && $item->id)
                        <a class="btn btn-danger" onclick="operator.remove('{{ $item->id }}');return false;">Delete</a>
                    @endif

                    <a href="{{ $app['url_generator']->generate('settings.operators.index') }}" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>

        <div style="display:none" id="confirmation-block">
            <b>Are you sure you want to update all operator related games?</b>

            Update will affect:
            <ul>
                <li>Branded games fees for selected operator</li>
                <li>Non branded games fees for selected operator</li>
                <li>Blocked countries list for all operator games</li>
            </ul>
            Will not affect:
            <ul>
                <li>Fees for games without "branded" status</li>
            </ul>
        </div>

        <!-- Modal -->
        <div id="modal_settings" class="modal fade" role="dialog">
            <div class="modal-dialog">

                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Box Settings</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Loading...</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script src="/phive/admin/customization/scripts/tokenize2.min.js"></script>
    <script>
        $(".unlock_op_fee_branded").click(function () {
            $("#i-branded_op_fee-placeholder").remove();
            $("#i-branded_op_fee").removeAttr('disabled');
            $(this).remove();
        });
        $(".unlock_op_fee").click(function () {
            $("#i-non_branded_op_fee-placeholder").remove();
            $("#i-non_branded_op_fee").removeAttr('disabled');
            $(this).remove();
        });
        /* todo: reuse switchFormat from game/edit.blade.php */
        function tokenizeElements() {
            $('#i-blocked_countries,#i-blocked_countries_non_branded,#i-blocked_countries_jackpot').tokenize2({
                tokensMaxItems: 167,
                tokensAllowCustom: true,
                dataSource: '<?php echo e($app['url_generator']->generate('settings.games.search-country', [])); ?>'
            });
        }

        $(document).ready(function(){
            tokenizeElements();
        });

        $("#i-network").select2({
            tags: true
        });

        var operator = {

            manual: false,

            switchFormat: function () {
                var blocked_countries = "#i-blocked_countries_man";
                var blocked_countries_jackpot_man = "#i-blocked_countries_jackpot_man";
                var blocked_countries_non_branded_man = "#i-blocked_countries_non_branded_man";

                if (this.manual) {
                    this.manual = false;

                    $
                        .post(
                            '{{ $app['url_generator']->generate('settings.operators.countries.formatted') }}',
                            {
                                'blocked_countries': $(blocked_countries).val(),
                                'blocked_countries_jackpot_man': $(blocked_countries_jackpot_man).val(),
                                'blocked_countries_non_branded_man': $(blocked_countries_non_branded_man).val()
                            }
                        )
                        .done(function (res) {

                            for(key in res) {
                                var elements = res[key];
                                var cache = [];
                                var $target = $("#i-" + key.replace('_man', ''));
                                $target.html('');

                                for (iso in elements) {
                                    $target.append("<option value='"+iso+"'>+elements[iso]+</option>");
                                    cache.push(iso);
                                    $target.tokenize2().trigger('tokenize:tokens:add', [iso, elements[iso], true]);
                                }
                                $target.val(cache);
                            }


                            $('.tokenize').show();
                            $('#i-blocked_countries_man,#i-blocked_countries_non_branded_man,#i-blocked_countries_jackpot_man').hide();
                            tokenizeElements();
                        })
                        .fail(function (xhr) {
                            if (xhr.responseJSON) {
                                alert(val[0]);
                            }
                        });
                } else {
                    this.manual = true;

                    var blocked_countries_man = $("#i-blocked_countries").val();
                    if (blocked_countries_man == null) {
                        blocked_countries_man = [];
                    }
                    var blocked_countries_jackpot = $("#i-blocked_countries_jackpot").val();
                    if (blocked_countries_jackpot == null) {
                        blocked_countries_jackpot = [];
                    }

                    var blocked_countries_non_branded = $("#i-blocked_countries_non_branded").val();
                    if (blocked_countries_non_branded == null) {
                        blocked_countries_non_branded = [];
                    }

                    $('.tokenize').hide();
                    $("#i-blocked_countries_man").val(blocked_countries_man.join(' '));
                    $("#i-blocked_countries_jackpot_man").val(blocked_countries_jackpot.join(' '));
                    $("#i-blocked_countries_non_branded_man").val(blocked_countries_non_branded.join(' '));
                    $('#i-blocked_countries_man,#i-blocked_countries_non_branded_man,#i-blocked_countries_jackpot_man').show();
                }

            },

            save: function(id, cb) {
                var blocked_c = (this.manual) ? $('#i-blocked_countries_man').val() : $('#i-blocked_countries').val();
                if(blocked_c == null) {
                    blocked_c = '';
                }

                var blocked_c_nonb = (this.manual) ? $('#i-blocked_countries_non_branded_man').val() : $('#i-blocked_countries_non_branded').val();
                if(blocked_c_nonb == null) {
                    blocked_c_nonb = '';
                }

                var blocked_c_jackpot = (this.manual) ? $('#i-blocked_countries_jackpot_man').val() : $('#i-blocked_countries_jackpot').val();
                if(blocked_c_jackpot == null) {
                    blocked_c_jackpot = '';
                }

                $.post(
                    '{{ $app['url_generator']->generate('settings.operators.save') }}?id=' + id,
                    $('#frm_page').serialize() +
                    '&blocked_countries=' + blocked_c +
                    '&blocked_countries_non_branded=' + blocked_c_nonb +
                    '&blocked_countries_jackpot=' + blocked_c_jackpot
                )
                    .done(function (res) {
                        if (cb) {
                            cb();
                        } else {
                            location.href = '../edit/?id=' + res.id;
                        }
                    })
                    .fail(function (xhr) {
                        if (xhr.responseJSON) {
                            $.each(xhr.responseJSON, function (idx, val) {
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

                return false;
            },

            loadOperators: function(network) {
                $.get('{{ $app['url_generator']->generate('settings.operators.get-operators') }}', { network: network })
                    .done(function(res) {

                        var json = $.parseJSON(res);

                        var op = $('#i-operator');
                        op.empty();

                        for (var id in json) {
                            op.append($("<option></option>").attr("value", json[id]).text(json[id]));
                        }
                    });
            },

            dlg: null,

            massUpdate: function(id) {
                this.save(id, function() {
                    this.massUpdateCallback(id);
                }.bind(this))
            },

            massUpdateCallback: function(id) {
                confirm.yes = function() {

                    confirm.close();

                    $.post('{{ $app['url_generator']->generate('settings.operators.update-games') }}', { id: id }, function(data) {

                        data = JSON.parse(data);

                        operator.dlg = new BootstrapDialog({
                            title: 'Update Success',
                            message: 'Branded games affected: <b>'+data.result.branded+'</b><br/>' +
                                'Non branded games affected: <b>'+data.result.non_branded+'</b><br/>' +
                                'Jackpot games affected: <b>'+data.result.jackpot+'</b>',
                            buttons: [{
                                label: 'Close',
                                action: function(dlg){
                                    dlg.close();
                                    window.location.reload();
                                }
                            }]
                        });

                        operator.dlg.open();

                        setTimeout(function() {
                            operator.dlg.close();
                        }, 6000);
                    });
                };
                confirm.dlg('Mass Games Update Confirmation', $('#confirmation-block').html());
            }
        };
    </script>
@endsection
