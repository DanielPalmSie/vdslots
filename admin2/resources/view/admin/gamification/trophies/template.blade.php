@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.gamification.trophies.partials.topmenu')

        <div id="replace-modal" class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                        <h4 class="modal-title">Multiedit "<span id="multiedit-attribute"></span>"</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="col-form-label">Original</label>
                            <div>
                                <input id="multiedit-original" name="multiedit-original" class="form-control" type="text" value="" readonly>
                            </div>
                            <label class="col-form-label">Pattern</label>
                            <div>
                                <input id="multiedit-pattern" name="multiedit-pattern" class="form-control" type="text" value="">
                            </div>
                            <label class="col-form-label">Replacement</label>
                            <div>
                                <input id="multiedit-replacement" name="multiedit-replacement" class="form-control" type="text" value="">
                            </div>
                            <label class="col-form-label">Result</label>
                            <div>
                                <input id="multiedit-result" name="multiedit-result" class="form-control" type="text" value="" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button id="multiedit-replace-btn" type="button" class="btn btn-primary" data-dismiss="modal">Replace</button>
                        <button id="multiedit-replaceall-btn" type="button" class="btn btn-primary" data-dismiss="modal">Replace All</button>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->

        <div class="card card-solid card-primary">
            <div class="card-header">
                <h3 class="card-title">Edit Trophy Template</h3>
                <div style="float: right">
                    <a href="{{ $app['url_generator']->generate('trophies.index') }}"><i class="fa fa-arrow-left"></i> Back to the list</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-12 col-sm-offset-0">
                        <div id="toggle-menu" style="margin-bottom: 8px;">
                            @foreach($columns['list'] as $k => $v)
                            @if($k != '')
                                <button style="margin-bottom: 3px" id="toggle-btn-{{ $k }}"
                                @if(in_array("col-$k", $columns['no_visible'])) class="btn btn-sm btn-default toggle-column-btn" @else class="btn btn-sm btn-warning toggle-column-btn" @endif
                                    data-column="{{ $k }}">{{ $v }}</button>
                                    <!--<button style="margin-bottom: 3px" id="toggle-btn-{{ $k }}" class="btn btn-sm btn-default toggle-column-btn" data-column="col-{{ $k }}">{{ $v }}</button>-->
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-4">
                        <div>
                            <form action="{{ $app['url_generator']->generate('trophies.fileupload') }}" class="dropzone" id="trophies-csv-upload">
                                <div class="dz-message">
                                    Drop .csv file here.<br/>
                                    <span class="note"><small>(Order of data will be same order in the list here.)</small></span>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <div>
                            <form action="{{ $app['url_generator']->generate('trophies.fileupload') }}" class="dropzone" id="trophies-images-upload">
                                <div class="dz-message">
                                    Drop colored image files here or click to select files to upload.<br/>
                                    <span class="note"><small>(Only colored images are need to be uploaded as grey images will be created automatically from them.)</small></span>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-sm-8 col-lg-8">

                        <form id="trophy-template-form" class="" method="post">
                        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                        <!-- TODO: Possibly reuse trophybox template here (after rearranging things a bit). -->
                        @foreach ($all_trophies as $index => $t)
                            <div class="row" data-row="{{ $index }}" data-aliasid="{{ $t->id }}">
                                <input name="trophy[{{ $index }}][id]" class="form-control" type="hidden" value="{{ $t->id }}">
                                <div class="col-12 col-sm-9 col-lg-9">
                                    <div class="form-group row fg-alias" @if(in_array("col-alias", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="alias">Alias <span data-toggle="tooltip" title="Alias which is also used to find the appropriate image." class="badge bg-lightblue">?</span></label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <input name="trophy[{{ $index }}][alias]" class="form-control alias_edit" type="text" value="{{ $t->alias }}">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-secondary edit-alias-btn" data-attribute="alias" type="button"><i class="fa fa-copy"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group row fg-type" @if(in_array("col-type", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="type">Type</label>
                                        <div class="col-sm-9">
                                            <select name="trophy[{{ $index }}][type]" class="form-control select2-class select-type" style="width: 100%;" data-placeholder="No Type selected" data-allow-clear="true">
                                                @foreach ($all_distinct["type"] as $dt)
                                                    @if ($t->type == $dt)
                                                        <option value="{{ $dt }}" selected="true">{{ $dt }}</option>
                                                    @else
                                                        <option value="{{ $dt }}">{{ $dt }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row fg-subtype" @if(in_array("col-subtype", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="subtype">Sub Type</label>
                                        <div class="col-sm-9">
                                            <select name="trophy[{{ $index }}][subtype]" class="form-control select2-class select-subtype" style="width: 100%;" data-placeholder="No Sub Type selected" data-allow-clear="true">
                                            @foreach ($all_distinct["subtype"] as $st)
                                                @if ($t->subtype == $st)
                                                    <option value="{{ $st }}" selected="true">{{ $st }}</option>
                                                @else
                                                    <option value="{{ $st }}">{{ $st }}</option>
                                                @endif
                                            @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row fg-threshold" @if(in_array("col-threshold", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="c">Threshold</label>
                                        <div class="col-sm-9">
                                            <input name="trophy[{{ $index }}][threshold]" class="form-control" type="number" value="{{ $t->threshold }}">
                                        </div>
                                    </div>
                                    <div class="form-group row fg-time_period" @if(in_array("col-time_period", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="time_period">Time Period</label>
                                        <div class="col-sm-9">
                                            <input name="trophy[{{ $index }}][time_period]" class="form-control" type="number" value="{{ $t->time_period }}">
                                        </div>
                                    </div>
                                    <div class="form-group row fg-time_span" @if(in_array("col-time_span", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="time_span">Time Span</label>
                                        <div class="col-sm-9">
                                            <select name="trophy[{{ $index }}][time_span]" class="form-control select2-class select-time_span" style="width: 100%;" data-placeholder="Type" data-allow-clear="true">
                                                @foreach ($all_distinct["time_span"] as $dts)
                                                    @if ($t->time_span == $dts)
                                                        <option value="{{ $dts }}" selected="true">{{ $dts }}</option>
                                                    @else
                                                        <option value="{{ $dts }}">{{ $dts }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row fg-game_ref" @if(in_array("col-game_ref", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="game_ref">Game Ref</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <select name="trophy[{{ $index }}][game_ref]" class="form-control select2-class select-game_ref" style="width: 100%;" data-placeholder="Game Ref" data-allow-clear="true" data-index="{{ $index }}">
                                                    <option value="{{ $games[$index]->ext_game_name }}">{{ $games[$index]->ext_game_name }}</option>
                                                </select>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-secondary edit-gameref-btn" data-attribute="game_ref" type="button"><i class="fa fa-copy"></i></button>
                                                </span>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    Game Name: <span id="span-game_ref_game_name{{ $index }}">{{ $games[$index]->game_name }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group row fg-in_row" @if(in_array("col-in_row", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="in_row">In Row</label>
                                        <div class="col-sm-9">
                                            <input name="trophy[{{ $index }}][in_row]" class="form-control" type="number" value="{{ $t->in_row }}">
                                        </div>
                                    </div>
                                    <div class="form-group row fg-category" @if(in_array("col-category", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="category">Category</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <select name="trophy[{{ $index }}][category]" class="form-control select2-class select-category" style="width: 100%;" data-placeholder="Category" data-allow-clear="false">
                                                    @foreach ($all_distinct["category"] as $c)
                                                        @if ($t->category == $c)
                                                            <option value="{{ $c }}" selected="true">{{ $c }}</option>
                                                        @else
                                                            <option value="{{ $c }}">{{ $c }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-secondary edit-category-btn" data-attribute="category" type="button"><i class="fa fa-copy"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group row fg-sub_category" @if(in_array("col-sub_category", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="sub_category">Sub Category</label>
                                        <div class="col-sm-9">
                                            <div class="input-group">
                                                <select name="trophy[{{ $index }}][sub_category]" class="form-control select2-class select-sub_category" style="width: 100%;" data-placeholder="Sub Category" data-allow-clear="false">
                                                    @foreach ($all_distinct["sub_category"] as $c)
                                                        @if ($t->sub_category == $c)
                                                            <option value="{{ $c }}" selected="true">{{ $c }}</option>
                                                        @else
                                                            <option value="{{ $c }}">{{ $c }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-secondary edit-subcategory-btn" data-attribute="sub_category" type="button"><i class="fa fa-copy"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group row fg-hidden" @if(in_array("col-hidden", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="hidden">Hidden</label>
                                        <div class="col-sm-9">
                                            <input name="trophy[{{ $index }}][hidden]" class="form-control" type="number" value="{{ $t->hidden }}">
                                        </div>
                                    </div>
                                    <div class="form-group row fg-amount" @if(in_array("col-amount", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="amount">Amount</label>
                                        <div class="col-sm-9">
                                            <input name="trophy[{{ $index }}][amount]" class="form-control" type="text" value="{{ $t->amount }}"> <!-- This is varchar in db, so doing as text for now. -->
                                        </div>
                                    </div>
                                    <div class="form-group row fg-award_id" @if(in_array("col-award_id", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="award_id">Award Id</label>
                                        <div class="col-sm-9">
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <select name="trophy[{{ $index }}][award_id]" class="form-control select2-class select-trophyawards-award_id" style="width: 100%;" data-placeholder="Award Id" data-allow-clear="true" data-index="{{ $index }}">
                                                        <option value="{{ $trophy_awards[$index]->id }}">{{ $trophy_awards[$index]->description }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12">
                                                Alias: <span id="span-trophyawards-award_id_alias{{ $index }}">{{ $trophy_awards[$index]->alias }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group row fg-award_id_alt" @if(in_array("col-award_id_alt", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="award_id_alt">Award Id Alt</label>
                                        <div class="col-sm-9">
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <select name="trophy[{{ $index }}][award_id_alt]" class="form-control select2-class select-trophyawards-award_id_alt" style="width: 100%;" data-placeholder="Award Id Alternative" data-allow-clear="true" data-index="{{ $index }}">
                                                        <option value="{{ $trophy_award_alts[$index]->id }}">{{ $trophy_award_alts[$index]->description }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-sm-12">
                                                Alias: <span id="span-trophyawards-award_id_alt_alias{{ $index }}">{{ $trophy_award_alts[$index]->alias }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group row fg-trademark" @if(in_array("col-trademark", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="trademark">Trademark</label>
                                        <div class="col-sm-9">
                                            <input name="trophy[{{ $index }}][trademark]" class="form-control" type="number" value="{{ $t->trademark }}">
                                        </div>
                                    </div>
                                    <div class="form-group row fg-repeatable" @if(in_array("col-repeatable", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="repeatable">Repeatable</label>
                                        <div class="col-sm-9">
                                            <input name="trophy[{{ $index }}][repeatable]" class="form-control" type="number" value="{{ $t->repeatable }}">
                                        </div>
                                    </div>
                                    <div class="form-group row fg-valid_from" @if(in_array("col-valid_from", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="valid_from">Valid From</label>
                                        <div class="col-sm-9">
                                            <input data-provide="datepicker" data-date-format="yyyy-mm-dd" name="trophy[{{ $index }}][valid_from]" class="form-control" type="text" value="{{ $t->valid_from }}">
                                        </div>
                                    </div>
                                    <div class="form-group row fg-valid_to" @if(in_array("col-valid_to", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="valid_to">Valid To</label>
                                        <div class="col-sm-9">
                                            <input data-provide="datepicker" data-date-format="yyyy-mm-dd" name="trophy[{{ $index }}][valid_to]" class="form-control" type="text" value="{{ $t->valid_to }}">
                                        </div>
                                    </div>
                                    <div class="form-group row fg-completed_ids" @if(in_array("col-completed_ids", $columns['no_visible'])) style="display:none" @endif>
                                        <label class="col-sm-3 col-form-label" for="completed_ids">Completed Ids</label>
                                        <div class="col-sm-9">
                                            <input name="trophy[{{ $index }}][completed_ids]" class="form-control" type="text" value="{{ $t->completed_ids }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-sm-3 col-lg-3">
                                    <a href="{{ $app['url_generator']->generate('trophies.edit', ['trophy' => $t->id]) }}">
                                        <img class="img-responsive img-thumbnail alias_thumbnail_{{ $t->id }}" src="{{ $t->img_unfinished }}" title="{{ $t->img_unfinished }}">
                                        <img class="img-responsive img-thumbnail alias_thumbnail_{{ $t->id }}" src="{{ $t->img_finished }}" title="{{ $t->img_finished }}">
                                    </a>
                                </div>
                            </div>

                            @if($index < count($all_trophies)-1)
                                <hr />
                            @endif
                        @endforeach
                        </form>

                    </div>

                    <div class="col-12 col-sm-4 col-lg-4 follow-scroll">

                        <form id="trophy-language-form" class="" method="post">
                        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                            <fieldset>
                                <legend>Localized Strings:</legend>

                                @foreach ($all_localized_strings as $alias => $languages)
                                    <div class="form-group row" style="display:none" data-alias="lang_{{ $alias_to_id[$alias]['aliasid'] }}">
                                        <label id="localized_string_label_{{ $alias_to_id[$alias]['aliasid'] }}">{{ $alias }}</label>
                                        @if (isset($alias_to_id[$alias]['index']))
                                            @foreach ($languages as $language => $text)
                                            <div class="input-group">
                                                <div class="input-group-addon" style="min-width: 45px;">{{ $language }}</div>
                                                <input name="localizedstrings[{{ $alias_to_id[$alias]['index'] }}][{{ $language }}]" data-alias="{{ $alias }}" data-aliasid="{{ $alias_to_id[$alias]['aliasid'] }}" data-language="{{ $language }}" class="form-control" type="text" value="{{ $text }}">
                                            </div>
                                            @endforeach
                                        @endif
                                    </div>
                                @endforeach

                            </fieldset>

                        </form>

                    </div>
                </div>
            </div>
        </div>

        <div id="button-footer">
            <div class="form-group row">
                <div class="col-sm-12 text-center">
                    <button class="btn btn-success" id="save-current-trophy-set-btn">Save Current Set</button>&nbsp;|&nbsp;
                    <button class="btn btn-primary" id="create-new-trophy-set-btn">Create New Set!</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>

    @include('admin.gamification.trophies.partials.trophysharedjs')

    <script type="text/javascript">

        function updateReplacementData() {
            var original    = $('#multiedit-original').val();
            var pattern     = $('#multiedit-pattern').val();
            var replacement = $('#multiedit-replacement').val();
            var regex       = new RegExp(pattern, "g");
            $('#multiedit-result').val(original.replace(regex, replacement));
        }

        function enableFollowAlongScrolling() {
            var element = $('.follow-scroll'),
            originalY = element.offset().top;

            // Space between element and top of screen (when scrolling)
            var topMargin = 10;

            // Should probably be set in CSS; but here just for emphasis
            element.css('position', 'relative');

            $(window).on('scroll', function(event) {
                var scrollTop = $(window).scrollTop();

                element.stop(false, false).animate({
                    top: scrollTop < originalY
                            ? 0
                            : scrollTop - originalY + topMargin
                }, 400);
            });
        }

        function multiEditReplace() {
            var original    = $('#multiedit-original').val();
            var pattern     = $('#multiedit-pattern').val();
            var replacement = $('#multiedit-replacement').val();
            var regex       = new RegExp(pattern, "g");
            var new_value   = original.replace(regex, replacement);

            return new_value;
        }

        function updateGameName(new_game_ref, element) {
            $.ajax({
                url: "{{ $app['url_generator']->generate('game.ajaxfilter') }}",
                dataType: 'json',
                data: {'q': new_game_ref},
                success: function (data, text_status, jqXHR) {
                    if (data && data.length > 0 ) {
                        if (Array.isArray(element)) {
                            $.each(element, function(index, e) {
                                e.text(data[0].game_name);
                            });
                        } else {
                            element.text(data[0].game_name);
                        }
                    } else {
                        if (Array.isArray(element)) {
                            $.each(element, function(index, e) {
                                e.text("");
                            });
                        } else {
                            element.text("");
                        }
                    }
                },
                error: function (jqXHR, text_status, error_thrown) {
                    console.log(error_thrown);
                }
            });
        }

        function commonMultieditSelect2(button, field) {
            var input_element = button.closest('div').find('select');
            var original      = input_element.val();
            $('#multiedit-original').val(original);
            $('#multiedit-pattern').val(".+");
            $('#multiedit-replacement').val("new_"+field);
            updateReplacementData();

            $('#multiedit-attribute').text(field);

            var div_element = button.closest('div [data-row]');
            var row         = div_element.data('row');

            $('#multiedit-replace-btn').click(function(e) {
                var new_field = multiEditReplace();
                var attribute = $('#multiedit-attribute').text();
                var element   = $('[name="trophy['+row+']['+attribute+']');
                // TODO: Do not create a new option, just select if it exists?
                var newOption  = new Option(new_field, new_field, true, true);
                //element.empty();
                element.append(newOption).trigger('change');

                $('#multiedit-replace-btn').unbind("click");
            });

            $('#multiedit-replaceall-btn').click(function(e) {
                var pattern     = $('#multiedit-pattern').val();
                var replacement = $('#multiedit-replacement').val();
                var regex       = new RegExp(pattern, "g");
                var attribute   = $('#multiedit-attribute').text();

                $("select[name$='["+attribute+"]']").each(function(i) {
                    var original  = $(this).val();
                    var new_field = original.replace(regex, replacement);
                    // TODO: Do not create a new option, just select if it exists?
                    var newOption = new Option(new_field, new_field, true, true);
                    //$(this).empty();
                    $(this).append(newOption).trigger('change');
                });

                $('#multiedit-replaceall-btn').unbind("click");
            });

            $('#replace-modal').modal({
                show: 'true'
            });
        }


        function enableEditReplaceButtons() {

            $('.edit-alias-btn').on('click', function(e) {
                e.preventDefault();
                var input_element = $(this).closest('div').find('input');
                var original      = input_element.val();
                $('#multiedit-original').val(original);
                $('#multiedit-pattern').val(".+_(.+)_(.+)");
                $('#multiedit-replacement').val("newname_$1_$2");
                updateReplacementData();

                $('#multiedit-attribute').text('alias');

                var div_element = $(this).closest('div [data-row]');
                var row         = div_element.data('row');
                var aliasid     = div_element.data('aliasid');

                $('#multiedit-replace-btn').click(function(e) {
                    var new_alias = multiEditReplace();
                    var attribute = $('#multiedit-attribute').text();
                    var element   = $('[name="trophy['+row+']['+attribute+']');
                    element.val(new_alias);

                    updateLocalizedStringAndThumbnails(aliasid, new_alias);

                    $('#multiedit-replace-btn').unbind("click");
                });

                $('#multiedit-replaceall-btn').click(function(e) {
                    var pattern     = $('#multiedit-pattern').val();
                    var replacement = $('#multiedit-replacement').val();
                    var regex       = new RegExp(pattern, "g");
                    var attribute   = $('#multiedit-attribute').text();
                    $("input[name$='["+attribute+"]']").each(function(i) {
                         var original  = $(this).val();
                         var new_alias = original.replace(regex, replacement);
                         $(this).val(new_alias);

                         var div_element = $(this).closest('div [data-aliasid]');
                         var aliasid     = div_element.data('aliasid');
                         updateLocalizedStringAndThumbnails(aliasid, new_alias);
                    });

                    $('#multiedit-replaceall-btn').unbind("click");
                });

                $('#replace-modal').modal({
                    show: 'true'
                });
            });


            $('.edit-gameref-btn').on('click', function(e) {
                e.preventDefault();
                var input_element = $(this).closest('div').find('select');
                var original      = input_element.val();
                $('#multiedit-original').val(original);
                $('#multiedit-pattern').val(".+");
                $('#multiedit-replacement').val("new_game_ref");
                updateReplacementData();

                $('#multiedit-attribute').text('game_ref');

                var div_element = $(this).closest('div [data-row]');
                var row         = div_element.data('row');

                $('#multiedit-replace-btn').click(function(e) {
                    var new_game_ref = multiEditReplace();
                    var attribute    = $('#multiedit-attribute').text();
                    var element      = $('[name="trophy['+row+']['+attribute+']');
                    var newOption    = new Option(new_game_ref, new_game_ref, true, true);
                    element.empty();
                    element.append(newOption).trigger('change');

                    var element = $('#span-game_ref_game_name'+row);
                    updateGameName(new_game_ref, element);

                    $('#multiedit-replace-btn').unbind("click");
                });

                $('#multiedit-replaceall-btn').click(function(e) {
                    var pattern     = $('#multiedit-pattern').val();
                    var replacement = $('#multiedit-replacement').val();
                    var regex       = new RegExp(pattern, "g");
                    var attribute   = $('#multiedit-attribute').text();

                    var game_ref_list = {};

                    $("select[name$='["+attribute+"]']").each(function(i) {
                        var original     = $(this).val();
                        var new_game_ref = original.replace(regex, replacement);
                        var newOption    = new Option(new_game_ref, new_game_ref, true, true);
                        $(this).empty();
                        $(this).append(newOption).trigger('change');

                        var index = $(this).data('index');
                        var element = $('#span-game_ref_game_name'+index);
                        if (game_ref_list[new_game_ref] === undefined) {
                            game_ref_list[new_game_ref] = [];
                        }
                        game_ref_list[new_game_ref].push(element);
                    });

                    $.each(game_ref_list, function(game_ref, elements) {
                        updateGameName(game_ref, elements);
                    });

                    $('#multiedit-replaceall-btn').unbind("click");
                });

                $('#replace-modal').modal({
                    show: 'true'
                });
            });


            $('.edit-category-btn').on('click', function(e) {
                e.preventDefault();
                commonMultieditSelect2($(this), "category");
            });

            $('.edit-subcategory-btn').on('click', function(e) {
                e.preventDefault();
                commonMultieditSelect2($(this), "sub_category");
            });

        }

        function updateLocalizedStringAndThumbnails(aliasid, new_alias) {
            $('#localized_string_label_'+aliasid).text(new_alias);
            updateTrophyImages(aliasid, new_alias);
        }

        function updateOnAliasChange(alias_element) {
            var div_element = alias_element.closest('div [data-aliasid]');
            var aliasid     = div_element.data('aliasid');
            var new_alias   = alias_element.val();
            updateLocalizedStringAndThumbnails(aliasid, new_alias);
            checkDuplicateAlias();
        }

        function checkDuplicateAlias() {

            var aliases = {};
            $(".alias_edit").each(function(index) {
                if (aliases[$(this).val()]) {
                    aliases[$(this).val()]++;
                }
                else {
                    aliases[$(this).val()] = 1;
                }
            });

            $(".alias_edit").each(function(index) {
                $(this).removeClass('bg-red');
            });

            for (var val in aliases) {
                if (aliases[val] > 1) {
                    //$("input[value='"+val+"']").each(function(index) { // This doesn't work for some odd reason.
                    $(".alias_edit").each(function(index) {
                        if($(this).val() != val) {
                            return;
                        }
                        $(this).addClass('bg-red');
                    });
                }
            }

        }

        function saveTrophyTemplateSet(newset) {

            var template_form = $('#trophy-template-form,#trophy-language-form').serializeArray();
            //console.log(template_form);

            var url = "{{ $app['url_generator']->generate('trophies.savetrophyset') }}";
            if (newset) {
                url = "{{ $app['url_generator']->generate('trophies.newtrophyset') }}";
            }

            var buttons = $('#save-current-trophy-set-btn #create-new-trophy-set-btn');
            buttons.prop("disabled", true);

            $.ajax({
                url: url,
                type: "POST",
                data: template_form,
                success: function (data, text_status, jqXHR) {

                    if (data.success == true) {

                        msg = 'Trophy set saved successfully.';
                        if (newset) {
                            msg += ' Loading first Trophy in the set...';
                        }

                        displayNotifyMessage('success', msg);

                        if (newset) {
                            setTimeout(function() {
                                var redirect_to = "{{ $app['url_generator']->generate('trophies.edit', ['trophy' => -1]) }}";
                                redirect_to = redirect_to.replace("-1", data.trophies[0].id);
                                window.location.replace(redirect_to);
                            }, 3000);
                        }

                    } else {
                        console.log(data);
                        buttons.prop("disabled", false);

                        var attribute_errors = getAttributeErrors(data);
                        displayNotifyMessage('error', 'Trophy set save failed.<br/>'+attribute_errors+(data.error?(data.error.errorInfo[2] ? "Message: "+data.error.errorInfo[2] : "") : ''));
                    }
                },
                error: function (jqXHR, text_status, error_thrown) {
                    buttons.prop("disabled", false);
                    displayNotifyMessage('error', 'Localized Strings save failed. Error: '+error_thrown);
                }
            });
        }

        $(document).ready(function() {

            enableFollowAlongScrolling();
            enableEditReplaceButtons();
            enableSelect2Controllers();
            enableDropZone();

            $('.toggle-column-btn').on( 'click', function (e) {
                e.preventDefault();

                var from_cookie = JSON.parse(Cookies.get("trophyset-no-visible"));
                var self        = $(this);
                var selector    = ".fg-" + self.attr('data-column');
                var column      = $(selector);

                if ($.inArray('col-'+self.attr('data-column'), from_cookie) == -1) {
                    from_cookie.push('col-'+self.attr('data-column'));
                } else {
                    from_cookie = jQuery.grep(from_cookie, function(value) {
                        return value != 'col-'+self.attr('data-column');
                    });
                }

                var json_cookie = JSON.stringify(from_cookie);
                Cookies.get("trophyset-no-visible", json_cookie);

                if (column.is(':visible')) {
                    self.removeClass("btn-warning").addClass("btn-default");
                    column.hide();
                } else {
                    self.removeClass("btn-default").addClass("btn-warning");
                    column.show();
                }
            });


            $('#save-current-trophy-set-btn').on('click', function(e) {
                e.preventDefault();
                saveTrophyTemplateSet(false);
            });

            $('#create-new-trophy-set-btn').on('click', function(e) {
                e.preventDefault();
                saveTrophyTemplateSet(true);
            });


            $("#multiedit-pattern").change(function() {
                updateReplacementData();
            });
            $("#multiedit-pattern").keyup(function() {
                updateReplacementData();
            });

            $("#multiedit-replacement").keyup(function() {
                updateReplacementData();
            });
            $("#multiedit-replacement").change(function() {
                updateReplacementData();
            });

            $(".alias_edit").focus(function() {
                var div_element = $(this).closest('div [data-aliasid]');
                var aliasid     = div_element.data('aliasid');

                $('[data-alias^="lang_"').each(function(index) {
                  $(this).hide();
                });

                var element = $('[data-alias="lang_'+aliasid+'"]');
                element.show();
            });

            $(".alias_edit").keyup(function() {
                updateOnAliasChange($(this));
            });

            $(".alias_edit").change(function(e) {
                updateOnAliasChange($(this));
            });
        });
    </script>
@endsection
