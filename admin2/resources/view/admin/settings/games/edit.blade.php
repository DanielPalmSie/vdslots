@extends('admin.layout')

@section('header-css')
    @parent
    <link rel="stylesheet" href="/phive/admin/customization/styles/css/tokenize2.min.css">
@endsection

@section('content')

    @include('admin.settings.games.partials.topmenu')

    <?php
        /** @var \App\Repositories\GamesRepository $games_repo */
        /** @var \App\Models\Game $item */
        $session_data = pluckSessionValue('edit_game_data') ?? [];
        $session_errors = pluckSessionValue('edit_game_errors') ?? [];

        $item->customFillAttributes($session_data, $games_repo);

        if (empty($session_data['themes'])) {
            $themes = $id == 0 ? explode(',', $old_data->themes) : null;
        } else {
            $themes = explode(',', $session_data['themes']);
        }

        $themes = $games_repo->getGameThemes($id, false, $themes);
        $tags = $item->getTagsList();
        $mobile_game_name = empty($item->mobile_id) ? "" : \App\Models\Game::where('id', $item->mobile_id)->first()['game_name'];
    ?>

    <style>
        .error-message {
            color: red;
        }
        #frm_page .select2 {
            width: 100% !important;
        }
    </style>
    <div class="card card-solid card-primary" id="overrides-general">
        <div class="card-header">
            <h3 class="card-title">{{empty($id) ? 'New' : 'Edit'}} Game</h3>
            <div class="float-right">
                <a href="{{ $app['url_generator']->generate('settings.games.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
            </div>
        </div>

        <form role="form" id="frm_page" enctype="multipart/form-data" action="{{ $app['url_generator']->generate('settings.games.save', ['id' => $id]) }}" method="POST">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="i-game_name">Name *</label>
                            <input type="text" class="form-control" id="i-game_name" name="game_name" placeholder="Enter name" value="{{ $item->game_name }}">
                            <i class="error-message help-block" id="game_name_error"></i>
                        </div>
                        <div class="form-group">
                            <label for="i-game_id">Game ID *</label>
                            <input type="text" class="form-control" id="i-game_id" name="game_id" placeholder="Enter ID" value="{{ $item->game_id }}">
                            <i class="error-message help-block" id="game_id_error"></i>
                        </div>
                        <div class="form-group">
                            <label for="i-ext_game_name">External Game Name *</label>
                            <input type="text" class="form-control" id="i-ext_game_name" name="ext_game_name" placeholder="Enter external game name" value="{{ $item->ext_game_name }}" @if($item->played_times > 0) disabled @endif>
                            <i class="error-message help-block" id="ext_game_name_error"></i>
                        </div>
                        <div class="form-group">
                            <label for="i-game_url">Url *</label>
                            <input type="text" class="form-control" id="i-game_url" name="game_url" placeholder="Enter URL" value="{{ $item->game_url }}">
                            <i class="error-message help-block" id="game_url_error"></i>
                        </div>
                        <div class="form-group">
                            <label for="i-network">Network</label>
                            <select id="i-network" name="network" class="form-control apply-select2" data-tags="true" onchange="game.getNetworkOperators(this.value)">
                                @foreach($games_repo->getNetworks() as $row)
                                    <option {{$row == $item->network ? 'selected="selected"' : ''}}>{{ $row }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="i-operator" >Operator</label>
                            <select id="i-operator" name="operator" class="form-control apply-select2" data-tags="true">
                                @foreach($games_repo->getOperators($item->network) as $id=>$row)
                                    <option {{$row == $item->operator ? 'selected="selected"' : ''}} value="{{$row}}">{{ $row }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="i-tag">Tag</label>
                            <select id="i-tag" name="tag[]" class="form-control apply-select2" onchange="game.changeValues(0, this.value.indexOf('jackpot') > -1)">
                                @foreach($game_tags as $tag)
                                    <option {{ count($tags) > 0 ? in_array($tag, $tags) : $tag == 'videoslots' ? 'selected="selected"' : '' }}  value="{{$tag}}">{{ $tag }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="i-device_type">Device Type</label>
                            <select id="i-device_type" name="device_type" class="form-control apply-select2">
                                @foreach($games_repo->getDevices() as $row)
                                    <option {{$row == $item->device_type ? 'selected="selected"' : ''}}>{{ $row }}</option>
                                @endforeach
                            </select>
                            <i class="error-message help-block" id="device_type_error"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="i-payout_percent">RTP</label>
                            <input type="text" class="form-control" id="i-payout_percent" name="payout_percent" placeholder="Payout percent" value="{{ $item->payout_percent }}">
                            <i class="error-message help-block" id="payout_percent_error"></i>
                        </div>
                        <div class="form-group">
                            <label for="i-min_bet">Minimum Bet</label>
                            <input type="text" class="form-control" id="i-min_bet" name="min_bet" placeholder="Minimal Bet" value="{{ $item->min_bet }}">
                            <i class="error-message help-block" id="min_bet_error"></i>
                        </div>
                        <div class="form-group">
                            <label for="i-max_bet">Maximum Bet</label>
                            <input type="text" class="form-control" id="i-max_bet" name="max_bet" placeholder="Maximal Bet" value="{{ $item->max_bet }}">
                            <i class="error-message help-block" id="max_bet_error"></i>
                        </div>
                        <div class="form-group">
                            <label for="i-max_win">Max Win Multiplier</label>
                            <input type="text" class="form-control" id="i-max_win" name="max_win" placeholder="Maximal Win" value="{{ $item->id === null ? '' : $item->max_win }}">
                            <i class="error-message help-block" id="max_win_error"></i>
                        </div>
                        <div class="form-group">
                            <label for="i_volatility">Volatility</label>
                            <select id="i_volatility" name="volatility" class="form-control apply-select2">
                                @for($i=1; $i<=9; $i++)
                                    <option {{$i == $item->volatility ? 'selected="selected"' : ''}}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="i-num_lines">Number of Lines</label>
                            <input type="text" class="form-control" id="i-num_lines" name="num_lines" placeholder="Number of Lines" value="{{ $item->num_lines }}">
                            <i class="error-message help-block" id="num_lines_error"></i>
                        </div>
                        <div class="form-group">
                            <?php /*<div class="col-md-6">
                                <div class="form-group">
                                    <label for="i-jackpot">Jackpot</label>
                                    <select id="i-jackpot" name="jackpot" class="form-control" onchange="game.changeValues(0, this.value)">
                                        <option value="0" @if($item->jackpot == 0)selected="selected"@endif>NO</option>
                                        <option value="1" @if($item->jackpot == 1)selected="selected"@endif>YES</option>
                                    </select>
                                </div>
                            </div>*/?>

                                <div class="form-group">
                                    <label for="i-jackpot_contrib">Jackpot contribution</label>
                                    <input type="text" class="form-control" id="i-jackpot_contrib" onkeyup="game.changeValues(0, this.value);" name="jackpot_contrib" placeholder="Enter contribution" value="{{ $item->jackpot_contrib }}">
                                </div>
                                <i class="error-message help-block" id="jackpot_contrib_error"></i>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="i-branded">Branded Game</label>
                                    <select id="i-branded" name="branded" class="form-control apply-select2" onchange="game.changeValues(this.value)">
                                        <option value="{{ \App\Models\GameTypes::Unknown }}"></option>
                                        <option value="{{ \App\Models\GameTypes::Branded }}" {{ $item->branded == \App\Models\GameTypes::Branded ? 'selected="selected"' : '' }}>YES</option>
                                        <option value="{{ \App\Models\GameTypes::NonBranded }}" {{ $item->branded == \App\Models\GameTypes::NonBranded ? 'selected="selected"' : '' }}>NO</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="network-dependencies">
                            <div class="form-group">
                                <label for="i-module_id">Module ID</label>
                                <input type="text" class="form-control" id="i-module_id" name="module_id" placeholder="Module ID" value="{{ $item->module_id }}">
                                <i class="error-message help-block" id="module_id_error"></i>
                            </div>
                            <div class="form-group">
                                <label for="i-client_id">Client ID</label>
                                <input type="text" class="form-control" id="i-client_id" name="client_id" placeholder="Client ID" value="{{ $item->client_id }}">
                                <i class="error-message help-block" id="client_id_error"></i>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="i-ribbon_pic">Ribbon Pic</label>
                            <select id="i-ribbon_pic" name="ribbon_pic" class="form-control apply-select2">
                                <option value="{{$item->id === null ? 'newgameicon' : ''}}" {{$item->id === null ? 'selected="selected"' : ''}}>
                                    {{$item->id === null ? 'newgameicon' : ''}}
                                </option>
                                @foreach($ribbon_pictures as $picture)
                                    <option {{ $item->ribbon_pic == $picture ? 'selected="selected"' : ''}}>{{ $picture }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="i-languages">
                                Languages
                                <button class="btn btn-default fill-languages" data-target="i-languages" style="margin-top: -10px">Select all</button>
                                <button class="btn btn-default remove-languages" data-target="i-languages" style="margin-top: -10px">Clear</button>
                            </label>
                            <?php
                            $item_languages = array_filter(explode(',', $item->languages));
                            $all_selected = empty($item_languages) && empty($id);
                            ?>
                            <select id="i-languages" name="languages[]" class="form-control apply-select2" multiple="multiple" style="height: 160px;">

                                @foreach($all_languages as $key => $row)
                                    <?php $select_language_item = $all_selected || in_array($key, $chosen_languages); ?>
                                    <option  {{$select_language_item ? 'selected="selected"' : ''}} value="{{ $key }}">{{ $row }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="i-included_countries">
                                Included countries
                                <button class="btn btn-default remove-countries" data-target="i-included_countries" style="margin-top: -10px">Clear</button>
                            </label>
                            <select id="i-included_countries" multiple="multiple" class="form-control" data-formatswitch="included_countries">
                                @foreach(\App\Helpers\DataFormatHelper::formatCountries($item->included_countries) as $key => $row)
                                    <option value="{{ $key }}" selected="selected">{{ $row }}</option>
                                @endforeach
                            </select>
                            <i class="error-message help-block" id="included_countries_error"></i>
                        </div>
                        <div class="form-group">
                            <label for="i-themes">Themes</label>
                            <select id="i-themes" multiple="multiple" class="form-control">
                                @foreach($themes as $key => $row)
                                    <option value="{{ $key }}" selected="selected">{{ $row }}</option>
                                @endforeach
                            </select>
                            <i class="error-message help-block" id="themes_error"></i>
                        </div>
                        <div class="form-group">
                            <label for="i-mobile_id">Mobile ID</label>
                            <select {{$item->device_type === 'html5' ? 'disabled' : ''}} id="i-mobile_id" name="mobile_id" class="form-control">
                                @if(!empty($item->mobile_id)) {{--editing an existing game--}}
                                    <option value="{{ $item->mobile_id }}" selected="selected">{{ $mobile_game_name }}</option>
                                @elseif($item->mobile_id === 0) {{--editing a mobile game--}}
                                    <option value="0" selected="selected">Mobile ID cannot be changed</option>
                                @else {{--creating a new game--}}
                                    <option value="{{ $item->mobile_id }}" selected="selected">{{ $mobile_game_name }}</option>
                                @endif
                            </select>
                            <i class="error-message help-block" id="mobile_id_error"></i>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label for="i-width">Width (px)</label>
                                <input type="text" class="form-control" id="i-width" name="width" placeholder="Game Width" value="{{ $item->width }}" maxlength="4">
                            </div>
                            <div class="col-md-6">
                                <label for="i-height">Height (px)</label>
                                <input type="text" class="form-control" id="i-height" name="height" placeholder="Game Height" value="{{ $item->height }}" maxlength="4">
                            </div>
                            <i class="error-message help-block" id="width_error"></i>
                            <i class="error-message help-block" id="height_error"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="col-md-12" style="background-color: #efefef;">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="enabled" {{$item->enabled || $item->id == 0 ? 'checked="checked"' : ''}} value="1"> Enabled
                                    </label>
                                    <p class="help-block">If not enabled "Under Construction" sign will be shown</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="multi_channel" {{$item->multi_channel == 1 ? 'checked="checked"' : '' }} value="{{$item->multi_channel}}"> Multi Channel
                                    </label>
                                    <p class="help-block">Use mobile URL to open game on PC if enabled</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="stretch_bkg" {{$item->stretch_bkg || $item->id == 0 ? 'checked="checked"' : '' }} value="1"> Stretch Background
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="active" value="{{$item->active}}" {{$item->active == 1 ? 'checked' : ''}}> Active
                                    </label>
                                </div>
                            </div>
                        </div>
                        @if(p('settings.games.section.payout_extra_percent'))
                            <div class="form-group">
                                <label for="i-payout_extra_percent">
                                    Weekend Booster Perc.<br>
                                    <span style="font-weight: normal">(0 use default, 1.1 means adding 10%, max precision XX.YYYYYY)</span>
                                </label>
                                <input type="text" class="form-control" id="i-payout_extra_percent" name="payout_extra_percent"
                                       placeholder="Percentage 1 = 100%, 1.1 = 110%" value="{{ $item->payout_extra_percent }}" {{!p('settings.games.section.payout_extra_percent') ? 'disabled="disabled"' : ''}}>
                                <i class="error-message help-block" id="payout_extra_percent_error"></i>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="i-blocked_countries">
                                Blocked countries
                                <button class="btn btn-default remove-countries" data-target="i-blocked_countries" style="margin-top: -10px">Clear</button>
                            </label>
                            <select id="i-blocked_countries" multiple="multiple" class="form-control" data-formatswitch="blocked_countries">
                                @foreach(\App\Helpers\DataFormatHelper::formatCountries($item->blocked_countries) as $id => $row)
                                    <option value="{{ $id }}" selected="selected">{{ $row }}</option>
                                @endforeach
                            </select>
                            <i class="error-message help-block" id="blocked_countries_error"></i>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="i-blocked_provinces">
                                Blocked Provinces
                                <button class="btn btn-default remove-provinces" data-target="i-blocked_provinces">Clear</button>
                            </label>
                            <select id="i-blocked_provinces" multiple="multiple" class="form-control" data-formatswitch="blocked_provinces">
                                @foreach(\App\Helpers\DataFormatHelper::formatProvince($item->blocked_provinces) as $province)
                                    <option value="{{ $province['value'] }}" selected="selected">{{ $province['name'] }}</option>
                                @endforeach
                            </select>
                            <i class="error-message help-block" id="blocked_provinces_error"></i>
                        </div>
                    </div>
                </div>
                @foreach($session_errors['required_certificates'] as $country)
                    @include('admin.settings.games.partials.certificate', compact('country', 'session_data', 'session_errors', 'id', 'certificates'))
                @endforeach
            </div>
            <!-- /.card-body -->

            <div class="card-footer">
                <div class="float-right">
                    <input type="button" value="Switch countries format"  class="btn btn-warning" id="switch-format-button">
                    @if($item->id != 0)
                        <button type="submit" onclick="game.saveas(0);return false;" class="btn btn-primary">Clone</button>
                    @endif
                    @if(false && $item->id)
                        <a class="btn btn-danger" onclick="game.remove({{ $item->id }});return false;">Delete</a>
                    @endif
                    <button type="submit" class="btn btn-primary submit-game-edit-form">Save</button>
                </div>

                <a href="{{ $app['url_generator']->generate('settings.games.index') }}" class="btn btn-default float-left">Cancel</a>
            </div>
        </form>
    </div>

    @if($game_id > 0)
    <div class="row">
        <div class="col-lg-6 col-md-12" id="edit-images">
            {!! \App\Repositories\GamesRepository::instance($app)->getImagesView($game_id) !!}
        </div>
        <div class="col-lg-6 col-md-12" id="edit-game-tag">
            {!! \App\Repositories\GamesRepository::instance($app)->getGameTagView($game_id) !!}
        </div>
        <div class="col-lg-12 col-md-12" id="edit-overrides">
            {!! \App\Repositories\GamesRepository::instance($app)->getOverridesView($game_id) !!}
        </div>
        <div class="col-lg-12 col-md-12" id="edit-features">
            {!! \App\Repositories\GamesRepository::instance($app)->getFeaturesView($game_id) !!}
        </div>
        @if(p('settings.games.section.changes-log'))
            <div class="col-lg-12 col-md-12" id="changes-log">
                {!! \App\Repositories\GamesRepository::instance($app)->getChangesLogView($game_id) !!}
            </div>
        @endif
    </div>
    @endif

    <!-- Modal -->
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

    <!-- Modal -->
    <div id="modal_settings" class="modal fade" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Box Settings</h4>
                </div>
                <div class="modal-body">
                    <p>Loading...</p>
                </div>
            </div>

        </div>
    </div>

    <input style="display: none;" class="form-control" type="text" id="man-placeholder" placeholder="" value="">
@endsection



@section('footer-javascript')
    <script src="/phive/admin/customization/scripts/tokenize2.min.js"></script>
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    @parent
    <script>
        $(".unlock_op_fee").click(function () {
            $("#i-op_fee-placeholder").remove();
            $("#i-op_fee").removeAttr('disabled');
            $(this).remove();
        });
        $.fn.extend({
            qcss: function(css) {
                return $(this).queue(function(next) {
                    $(this).css(css);
                    next();
                });
            },
            processing: function(item, val) {
                return $(this).queue(function(next) {
                    if (input.processing[item] != null) {
                        input.processing[item] = val;
                    }
                    next();
                });
            },
            tokenizeElement: function() {
                return $(this).tokenize2({
                    tokensMaxItems: 167,
                    tokensAllowCustom: true,
                    dataSource: '{{$app['url_generator']->generate('settings.games.search-country', [])}}'
                });
            }
        });

        var input  = {

            processing: [],

            attention: function(elm) {

                if (this.processing[elm] == null || this.processing[elm] == false) {

                    this.processing[elm] = true;

                    $(elm)
                        .qcss({backgroundColor: '#ff9e9e'}).delay(1600)
                        .qcss({backgroundColor: '#ffb2b2'}).delay(30)
                        .qcss({backgroundColor: '#ffc6c6'}).delay(30)
                        .qcss({backgroundColor: '#ffcccc'}).delay(30)
                        .qcss({backgroundColor: '#f7caca'}).delay(30)
                        .qcss({backgroundColor: '#ffe0e0'}).delay(30)
                        .qcss({backgroundColor: '#ffeaea'}).delay(30)
                        .qcss({backgroundColor: '#fff4f4'}).delay(30)
                        .qcss({backgroundColor: '#fff9f9'}).delay(30)
                        .qcss({backgroundColor: '#ffffff'}).processing(elm, false);
                }
            }
        };

        var game = {
            handleSaveErrors: function(errors) {
                if(errors) {
                    $.each(errors, function(idx, val) {
                        var obj;
                        if(obj = $('#'+idx+'_error')) {
                            obj.show();
                            obj.html(val[0]);
                            setTimeout(function() {
                                obj.fadeOut(1000, function() { obj.html('').css('opacity', 100); });
                            }, 15000);
                        }
                    });
                }
            },

            id: parseInt('{{$id}}'),
            op_values: [],
            jackpot: '{{ $item->jackpot_contrib }}',

            switchFormat: function (countries, callback) {
                /*
                * countries: ["#el1", "#el2"]
                * manual: "#el1_man1", "#el2_man2"
                * */
                function el(name) {
                    return $(`[data-formatswitch='${name}']`);
                }

                function manEl(name) {
                    return $(`[data-formatswitchman='${name}']`);
                }

                /**
                 * To work with the tokenize library
                 * @param objectData need to in the form of:
                 * { blocked_countries: { MT: 'Malta', UK: 'United Kingdom'} }
                 */
                function tokenizeElements(objectData) {
                    Object.entries(objectData).forEach(function (elements) {
                        [key, elements] = elements;
                        el(key).html('');
                        el(key).find('option').remove();

                        Object.entries(elements).forEach(function (jurisdiction) {
                            [iso, jurisdiction] = jurisdiction;
                            el(key).append("<option value='" + iso + "'>" + jurisdiction + "</option>");
                            el(key).tokenize2().trigger('tokenize:tokens:add', [iso, jurisdiction, true]);
                        });

                        el(key).val(Object.keys(elements));
                        el(key).tokenizeElement();
                        el(key).parent().find('.tokenize').show();

                        manEl(key).hide();
                    });
                }
                if(callback == null) {
                    callback = function() {};
                }

                countries.forEach(function(country) {
                    if (manEl(country).length > 0) {
                        return;
                    }

                    el(country).parent().append(
                        ($("#man-placeholder").clone())
                            .attr('placeholder', el(country).attr('placeholder'))
                            .attr('data-formatswitchman', country)
                            .removeAttr('id')
                    );
                });

                if (this.manual) {
                    let req = countries.reduce(function (carry, country) {
                        carry[country] = manEl(country).val();
                        return carry;
                    }, {});
                    // split the countries from the provinces
                    var provinces_req = {'blocked_provinces': req.blocked_provinces}
                    delete req.blocked_provinces

                    $.post('{{ $app['url_generator']->generate('settings.operators.countries.formatted') }}', req)
                        .done(function (res) {
                            tokenizeElements(res)
                            callback();
                        })
                        .fail(function (xhr) {
                            if (xhr.responseJSON) {
                                alert(val[0]);
                            }
                            callback();
                        });

                    $.post('{{ $app['url_generator']->generate('settings.games.format-provinces') }}', provinces_req)
                        .done(function (data) {
                            var res = JSON.parse(data)

                            var province_data = {
                                blocked_provinces: {}
                            }

                            for (var key in res) {
                                if (res.hasOwnProperty(key)) {
                                    var value = res[key].value;
                                    var name = res[key].name;
                                    province_data.blocked_provinces[value] = name;
                                }
                            }
                            tokenizeElements(province_data)
                            callback();
                        })
                        .fail(function (xhr) {
                            if (xhr.responseJSON) {
                                alert(val[0]);
                            }
                            callback();
                        });

                } else {
                    countries.forEach(function (country) {
                        let val = el(country).val();
                        if (val == null) {
                            val = [];
                        }

                        manEl(country).val(val.join(' ')).show();

                        el(country).parent().find('.tokenize').hide();
                    });
                    callback();
                }

                this.manual = !this.manual;
            },

            reloadCountriesList: function(elms) {
                $(elms).tokenize2({
                    tokensMaxItems: 167,
                    tokensAllowCustom: true,
                    dataSource: '<?php echo e($app['url_generator']->generate('settings.games.search-country', [])); ?>'
                });
            },

            reloadProvinceList: function (elms) {
              $(elms).tokenize2({
                  tokensMaxItems: 167,
                  tokensAllowCustom: true,
                  dataSource: '<?php echo e($app['url_generator']->generate('settings.games.search-provinces', []))?>'
              })
            },

            reloadThemes: function(elms) {
                $(elms).tokenize2({
                    tokensMaxItems: 167,
                    tokensAllowCustom: false,
                    dataSource: '<?php echo e($app['url_generator']->generate('settings.games.search-theme', [])); ?>'
                });
            },
            save: function(id) {
                if (!game.manual) {
                    this.saveCallback(id);
                } else { // added else otherwise was triggering twice the save action
                    game.switchFormat(["blocked_countries", "included_countries", 'blocked_provinces'], this.saveCallback.bind(this, id));
                }
            },
            saveCallback: function(id) {
                $form = $('#frm_page');
                window.location.hash = "overrides-general";
                var included_c = $('#i-included_countries').val();
                if (included_c == null) {
                    included_c = '';
                }

                var blocked_c = $('#i-blocked_countries').val();
                if (blocked_c == null) {
                    blocked_c = '';
                }

                var themes = $('#i-themes').val();
                if (themes == null) {
                    themes = '';
                }

                var blocked_p = $('#i-blocked_provinces').val()
                if(blocked_p == null) {
                    blocked_p = ''
                }
                $(".error-message").hide();

                var checkboxes = ["active", "enabled", "multi_channel", "stretch_bkg"];
                checkboxes.forEach(element => {
                    if ($form.find("[name='"+ element +"']").is(":checked")) {
                        $form.find("[name='"+ element +"']").attr('value', 1);
                    } else {
                        $form.append("<input type='hidden' name='"+ element +"' value='0'>");
                    }
                });

                $form.append("<input type='hidden' name='themes' value='"+themes+"'>");
                $form.append("<input type='hidden' name='blocked_countries' value='"+blocked_c+"'>");
                $form.append("<input type='hidden' name='included_countries' value='"+included_c+"'>");
                $form.append("<input type='hidden' name='blocked_provinces' value='"+blocked_p+"'>")
                $form.submit();

                return false;
            },

            saveas: function() {
                if (!game.manual) {
                    return this.saveasCallback();
                }
                game.switchFormat(["blocked_countries", "included_countries", "blocked_provinces"], this.saveasCallback.bind(this));
            },
            saveasCallback: function() {
                // if *.val() is null, set default value to ''
                var included_c = $('#i-included_countries').val() || '';
                var blocked_c = $('#i-blocked_countries').val() || '';
                var themes = $('#i-themes').val() || '';
                var blocked_p = $('#i-blocked_provinces').val() || ''

                $.post('{{ $app['url_generator']->generate('settings.games.saveas') }}?id=0',
                    $('#frm_page').serialize()
                    + '&themes=' + themes
                    + '&blocked_countries=' + blocked_c
                    + '&included_countries=' + included_c
                    + '&blocked_provinces=' + blocked_p
                )
                    .done(function(res) {
                        location.href='../edit/?id=0&results='+res;
                    })
                    .fail(function(xhr, textStatus, errorThrown) {
                        if(xhr.responseJSON) {
                            $.each(xhr.responseJSON, function(idx, val) {
                                var obj;
                                if(obj = $('#'+idx+'_error')) {
                                    obj.show();
                                    obj.html(val[0]);
                                    setTimeout(function() {
                                        obj.fadeOut(1000, function() { obj.html('').css('opacity', 100); });
                                    }, 15000);
                                }
                            });
                        };
                    });

                return false;

            },

            getNetworkOperators: function(network) {
                this.loadOperators(network);
            },
            loadOperators: function(network) {
                $.get('{{ $app['url_generator']->generate('settings.games.get-operators') }}', { network: network })
                    .done(function(res) {
                        var op_values = $.parseJSON(res);
                        game.op_values = op_values;

                        var op = $('#i-operator');
                        op.empty();

                        for (var id in op_values) {
                            if (op_values[id] !== null || op_values[id] !== undefined || op_values[id] !== '') {
                                op.append($("<option></option>").attr("value", op_values[id]).text(op_values[id]));
                            }
                        }
                    });
            },

            changeValues: function (val, jackpot) {
                if (val == -1) return false;
                if (jackpot == this.jackpot && jackpot != null) return false;

                // check if value was changed
                if (jackpot != null) {
                    this.jackpot = jackpot > 0 ? 1 : 0;
                }

            },

            createGameUrl: function (v1, v2) {
                return v1.trim().toLowerCase().replace(/[ ']/g, '-')
                    + '-'
                    + v2.trim().toLowerCase().replace(/[ ']/g, '-')
            },

            nameKeyUp: function(){
                let game_url = '#i-game_url';
                let previous_url = $(game_url).val();
                $(game_url).val(game.createGameUrl(this.value, $('#i-operator')[0].value));

                if (previous_url !== $(game_url).val()) {
                    input.attention(game_url);
                }
            },
            operatorChange: function(){
                let game_url = '#i-game_url';
                let previous_url = $(game_url).val();
                $(game_url).val(game.createGameUrl($('#i-game_name')[0].value, $('#i-operator :selected').text()));

                if (previous_url !== $(game_url).val()) {
                    input.attention(game_url);
                }
            }
        };

        $(document).ready(function(){
            game.reloadCountriesList('#i-blocked_countries,#i-included_countries');
            game.reloadProvinceList('#i-blocked_provinces')
            game.reloadThemes('#i-themes');
            $(".apply-select2").select2();

            $('#i-game_name').keyup(game.nameKeyUp);
            $('#i-operator').change(game.operatorChange);
            $("#i-game_name").focus();

            $("#switch-format-button").click(function($e) {
                $e.preventDefault();
                game.switchFormat(["blocked_countries", "included_countries", "blocked_provinces"]);
                return false;
            })

            function formatOption (el) {
                return el.id + " (" + el.text+ ")";
            }

            $('#i-mobile_id').select2({
                ajax: {
                    url: '{{ $app['url_generator']->generate('settings.games.search-mobile') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term, // search term
                            search: params.term, // search term
                            page: params.page
                        };
                    },
                    processResults: function (data, params) {
                        return {
                            results: $.map(data, function(data) {
                                return { id: data.id, text: data.text };
                            })
                        };
                    },
                    cache: true
                },
                placeholder: 'Search game by name or id',
                minimumInputLength: 1,
                multiple: false,
                templateResult: formatOption,
                templateSelection: formatOption
            });

            if (empty($("#i-operator").val())) {
                $("#i-network").trigger('change');
            }

            $(".submit-game-edit-form").click(function(e) {
                e.preventDefault();

                game.save({{ intval($item->id) }});

                return false;
            });

            game.handleSaveErrors({!! json_encode($session_errors) !!});

            $(".fill-languages").click(function(e) {
                e.preventDefault();

                $("#" + $(this).data('target'))
                    .val({!! json_encode(array_keys(\App\Helpers\DataFormatHelper::getLanguages())) !!})
                    .trigger('change');
            });

            $(".remove-languages").click(function(e) {
                e.preventDefault();
                $("#" + $(this).data('target')).val([]).trigger('change');
            });

            $(".remove-countries").click(function(e) {
                e.preventDefault();

                $("#" + $(this).data('target')).trigger('tokenize:clear');
            });
            $(".remove-provinces").click(function(e) {
                e.preventDefault();

                $("#" + $(this).data('target')).trigger('tokenize:clear');
            });
            $("#i-device_type").on('change', function(e) {
                e.preventDefault();

                if(this.options[this.selectedIndex].value === 'html5'){
                    $("#i-mobile_id").prop('disabled', true);
                }
                else{
                    $("#i-mobile_id").prop('disabled', false);
                }
            });
        });
    </script>

@endsection
