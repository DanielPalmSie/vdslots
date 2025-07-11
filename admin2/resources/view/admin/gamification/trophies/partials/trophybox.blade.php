<div class="card-body">

    @if ($app)

    @if ($buttons['delete'])
    <div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalDeleteLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalDeleteLabel">Confirm Delete</h4>
                </div>

                <div class="modal-body">
                    <p>You are about to delete the Trophie and related Localized Strings. This procedure is irreversible.</p>
                    <p>Do you want to proceed?</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button id="delete-modalbtn" class="btn btn-danger" data-dismiss="modal">Delete</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-sm-12">
            <div id="dropzone">
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
        <div class="col-12 col-sm-6 col-lg-6">
            <form id="trophy-form" class="" method="post">
            <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                @if($trophy)
                    <input name="id" class="form-control" type="hidden" value="{{ $trophy->id }}">
                @endif
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="alias">Alias <span data-toggle="tooltip" title="Alias needs to be unique. It is also used to find the appropriate image." class="badge bg-lightblue">?</span></label>
                    <div class="col-sm-9">
                        @if($trophy)
                            <input id="input-alias" data-uniqueid="{{ $trophy->id }}" name="alias" class="form-control" type="text" value="{{ $trophy->alias }}">
                        @else
                            <input id="input-alias" data-uniqueid="" name="alias" class="form-control" type="text" value="">
                        @endif
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="type">Type</label>
                    <div class="col-sm-9">
                        <select name="type" id="select-type" class="form-control select2-class select-type" style="width: 100%;" data-placeholder="No Type specified" data-allow-clear="true">
                            @foreach ($all_distinct['type'] as $t)
                                @if ($trophy->type == $t)
                                    <option value="{{ $t }}" selected="true">{{ $t }}</option>
                                @else
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="subtype">Sub Type</label>
                    <div class="col-sm-9">
                        <select name="subtype" class="form-control select2-class select-subtype" style="width: 100%;" data-placeholder="No Sub Type specified" data-allow-clear="true">
                            @foreach ($all_distinct['subtype'] as $st)
                                @if ($trophy->subtype == $st)
                                    <option value="{{ $st }}" selected="true">{{ $st }}</option>
                                @else
                                    <option value="{{ $st }}">{{ $st }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="threshold">Threshold</label>
                    <div class="col-sm-9">
                        <input name="threshold" class="form-control" type="number"
                        @if ($trophy)
                            value="{{ $trophy->threshold }}"
                        @else
                            value=""
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="time_period">Time Period</label>
                    <div class="col-sm-9">
                        <input name="time_period" class="form-control" type="number"
                        @if ($trophy)
                            value="{{ $trophy->time_period }}"
                        @else
                            value=""
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="time_span">Time Span</label>
                    <div class="col-sm-9">
                        <select name="time_span" class="form-control select2-class select-time_span" style="width: 100%;" data-placeholder="No Time Span specified" data-allow-clear="true">
                            @foreach ($all_distinct['time_span'] as $t)
                                @if ($trophy->time_span == $t)
                                    <option value="{{ $t }}" selected="true">{{ $t }}</option>
                                @else
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="game_ref">Game Ref</label>
                    <div class="col-sm-9">
                        <div class="row">
                            <div class="col-sm-12">
                                <select name="game_ref" class="form-control select2-class select-game_ref" style="width: 100%;" data-placeholder="No Game Reference specified" data-allow-clear="true">
                                    @if ($game)
                                        <option value="{{ $game->ext_game_name }}">{{ $game->ext_game_name }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                            Game Name: <span id="span-game_ref_game_name">@if($game){{ $game->game_name }}@endif</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="category">Category</label>
                    <div class="col-sm-9">
                        <select name="category" class="form-control select2-class select-category" style="width: 100%;" data-placeholder="No Category specified" data-allow-clear="true">
                            @foreach ($all_distinct['category'] as $c)
                                @if ($trophy->category == $c)
                                    <option value="{{ $c }}" selected="true">{{ $c }}</option>
                                @else
                                    <option value="{{ $c }}">{{ $c }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="sub_category">Sub Category</label>
                    <div class="col-sm-9">
                        <select name="sub_category" class="form-control select2-class select-sub_category" style="width: 100%;" data-placeholder="No Sub Category specified" data-allow-clear="true">
                        @foreach ($all_distinct['sub_category'] as $c)
                            @if ($trophy->sub_category == $c)
                                <option value="{{ $c }}" selected="true">{{ $c }}</option>
                            @else
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endif
                        @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="amount">Amount</label>
                    <div class="col-sm-9">
                        <!-- This is varchar in db, so doing as text for now. -->
                        <input name="amount" class="form-control" type="text"
                        @if ($trophy)
                            value="{{ $trophy->amount }}"
                        @else
                            value=""
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="award_id">Award Id</label>
                    <div class="col-sm-9">
                        <div class="row">
                            <div class="col-sm-12">
                                <select name="award_id" class="form-control select2-class select-trophyawards-award_id" style="width: 100%;" data-placeholder="No Award specified" data-allow-clear="true">
                                    @if ($trophy_award)
                                        <option value="{{ $trophy_award->id }}">{{ $trophy_award->description }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                            Alias: <span id="span-trophyawards-award_id_alias">@if($trophy_award){{ $trophy_award->alias }}@endif</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="award_id_alt">Award Id Alt</label>
                    <div class="col-sm-9">
                        <div class="row">
                            <div class="col-sm-12">
                                <select name="award_id_alt" class="form-control select2-class select-trophyawards-award_id_alt" style="width: 100%;" data-placeholder="No Award Alternative specified" data-allow-clear="true">
                                    @if ($trophy_award_alt)
                                        <option value="{{ $trophy_award_alt->id }}">{{ $trophy_award_alt->description }}</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                            Alias: <span id="span-trophyawards-award_id_alt_alias">@if($trophy_award_alt){{ $trophy_award_alt->alias }}@endif</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="in_row">In Row</label>
                    <div class="col-sm-9">
                        <input id="input-in_row" name="in_row" class="form-control" data-on="Yes" data-off="No" data-toggle="toggle" data-onstyle="success" data-offstyle="danger" type="checkbox"
                        @if ($trophy)
                            value="{{ $trophy->in_row }}" @if ($trophy->in_row) checked @endif
                        @else
                            value=""
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="hidden">Hidden</label>
                    <div class="col-sm-9">
                        <input id="input-hidden" name="hidden" class="form-control" data-on="Yes" data-off="No" data-toggle="toggle" data-onstyle="success" data-offstyle="danger" type="checkbox"
                        @if ($trophy)
                            value="{{ $trophy->hidden }}" @if ($trophy->hidden) checked @endif
                        @else
                            value=""
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="trademark">Trademark</label>
                    <div class="col-sm-9">
                        <input id="input-trademark" name="trademark" class="form-control" data-on="Yes" data-off="No" data-toggle="toggle" data-onstyle="success" data-offstyle="danger" type="checkbox"
                        @if ($trophy)
                            value="{{ $trophy->trademark }}" @if ($trophy->trademark) checked @endif
                        @else
                            value=""
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="repeatable">Repeatable</label>
                    <div class="col-sm-9">
                        <input id="input-repeatable" name="repeatable" class="form-control" data-on="Yes" data-off="No" data-toggle="toggle" data-onstyle="success" data-offstyle="danger" type="checkbox"
                        @if ($trophy)
                            value="{{ $trophy->repeatable }}" @if ($trophy->repeatable) checked @endif
                        @else
                            value=""
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="valid_from">Valid From</label>
                    <div class="col-sm-9">
                        <input data-provide="datepicker" data-date-format="yyyy-mm-dd" name="valid_from" class="form-control" type="text"
                        @if ($trophy)
                            value="{{ $trophy->valid_from }}"
                        @else
                            value=""
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="valid_to">Valid To</label>
                    <div class="col-sm-9">
                        <input data-provide="datepicker" data-date-format="yyyy-mm-dd" name="valid_to" class="form-control" type="text"
                        @if ($trophy)
                            value="{{ $trophy->valid_to }}"
                        @else
                            value=""
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="completed_ids">Completed Ids</label>
                    <div class="col-sm-9">
                        <input name="completed_ids" class="form-control" type="text"
                        @if ($trophy)
                            value="{{ $trophy->completed_ids }}"
                        @else
                            value=""
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="excluded_countries">Excluded countries</label>
                    <div class="col-sm-9">
                        <select class="form-control select2-class" name="excluded_countries[]" id="excluded_countries">
                            @foreach ($all_distinct['countries'] as $c)
                                <option value="{{ $c['id'] }}">{{ $c['text'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label" for="included_countries">Included countries</label>
                    <div class="col-sm-9">
                        <select class="form-control select2-class" name="included_countries[]" id="included_countries">
                            @foreach ($all_distinct['countries'] as $c)
                                <option value="{{ $c['id'] }}">{{ $c['text'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-sm-6 col-sm-offset-3">
                        @if ($buttons['save'])
                            <button class="btn btn-primary" id="save-trophy-btn">
                                {{ $buttons['save'] }}
                            </button>
                        @endif
                        @if ($buttons['save-as-new'])
                            &nbsp; | &nbsp;
                            <button class="btn btn-info" id="save-as-new-trophy-btn">
                                {{ $buttons['save-as-new'] }}
                            </button>
                        @endif
                        @if ($buttons['delete'])
                            &nbsp; | &nbsp;
                            <button class="btn btn-danger" id="delete-trophy-btn" data-toggle="modal" data-target="#confirm-delete">
                                {{ $buttons['delete'] }}
                            </button>
                        @endif
                    </div>
                    @if ($trophy)
                    <div class="col-sm-3">
                        <div class="dropup pull-right">
                            <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Edit/Create
                                &nbsp;<span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenu2">
                                <!--<li><a href="#">Separated link</a></li>
                                <li role="separator" class="divider"></li>-->
                                <li><a href="{{ $app['url_generator']->generate('trophies.templateedit', ['trophy' => $trophy->id]) }}">The Set from this "<span class="trophy_main">{{ $trophy_main }}</span>"</a></li>
                            </ul>
                        </div>
                    </div>
                    @endif
                </div>
            </form>

        </div>

        <div class="col-12 col-sm-6 col-lg-6">

            <form id="trophy-language-form" class="" method="post">
                <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">

                @foreach ($all_localized_strings as $language => $localized_string)
                    <div class="form-group row">

                        <div class="input-group">
                            <div class="input-group-addon" style="min-width: 45px;">{{ $language }}</div>
                            @if ($localized_string)
                                <input name="{{ $trophy->alias }}[{{ $language }}]" class="form-control" type="text" value="{{ $localized_string->value }}">
                            @else
                                <input name="{{ $trophy->alias }}[{{ $language }}]" class="form-control" type="text" value="">
                            @endif
                        </div>

                    </div>
                @endforeach

                <div class="row">
                    <div class="col-12 col-sm-12 col-lg-12">
                        @if ($trophy)
                            <img class="img-responsive img-thumbnail alias_thumbnail_{{ $trophy->id }}" src="{{ $trophy->img_unfinished }}" title="{{ $trophy->img_unfinished }}">
                            <img class="img-responsive img-thumbnail alias_thumbnail_{{ $trophy->id }}" src="{{ $trophy->img_finished }}" title="{{ $trophy->img_finished }}">
                        @else
                            <img class="img-responsive img-thumbnail alias_thumbnail_" src="{{ getMediaServiceUrl() }}/file_uploads/events/noname.png" title="">
                            <img class="img-responsive img-thumbnail alias_thumbnail_" src="{{ getMediaServiceUrl() }}/file_uploads/events/naname.png" title="">
                        @endif
                        <span class="pull-right">
                            @if ($buttons['save-language'])
                            <!--<button class="btn btn-default" disabled="true">Use Google Translate</button>-->
                            <button class="btn btn-primary" id="save-language-btn">{{ $buttons['save-language'] }}</button>
                            @endif
                        </span>
                    </div>
                </div>

                @if ($all_trophies)
                <hr />
                <div class="row">
                    <div class="col-6 col-sm-6 col-lg-6">
                        @foreach ($all_trophies as $t)
                            <a href="{{ $app['url_generator']->generate('trophies.edit', ['trophy' => $t->id]) }}">
                            @if ("{$t->alias}" == "{$trophy->alias}")
                                <img class="img-responsive img-thumbnail alias_thumbnail_{{ $t->id }}" style="border-color: grey;" src="{{ $t->img_unfinished }}" title="{{ $t->img_unfinished }}">
                            @else
                                <img class="img-responsive img-thumbnail" src="{{ $t->img_unfinished }}" title="{{ $t->img_unfinished }}">
                            @endif
                            </a>
                        @endforeach
                    </div>
                    <div class="col-6 col-sm-6 col-lg-6">
                        @foreach ($all_trophies as $t)
                            <a href="{{ $app['url_generator']->generate('trophies.edit', ['trophy' => $t->id]) }}">
                            @if ("{$t->alias}" == "{$trophy->alias}")
                                <img class="img-responsive img-thumbnail alias_thumbnail_{{ $t->id }}" style="border-color: grey;" src="{{ $t->img_finished }}" title="{{ $t->img_finished }}">
                            @else
                                <img class="img-responsive img-thumbnail" src="{{ $t->img_finished }}" title="{{ $t->img_finished }}">
                            @endif
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif

            </form>

        </div>
    </div>

    <div class="row">
        <div class="col-12 col-sm-12 col-lg-12">
            @if ($buttons['save-all'])
            <hr />
            <div class="text-center">
                <button class="btn btn-primary" id="save-all-btn">{{ $buttons['save-all'] }}</button>
            </div>
            @endif
        </div>
    </div>

    @push('extrajavascript')
    <script type="text/javascript">
        $(document).ready(function() {
            $('#excluded_countries').select2({
                selectOnBlur: true,
                multiple: true,
                tags: true,
                allowClear: true,
            });
            $('#excluded_countries').val({!! json_encode($trophy->excluded_countries ?? []) !!});
            $('#excluded_countries').change();

            $('#included_countries').select2({
                selectOnBlur: true,
                multiple: true,
                tags: true,
                allowClear: true,
            });
            $('#included_countries').val({!! json_encode($trophy->included_countries ?? []) !!});
            $('#included_countries').change();

            $('#delete-trophy-btn').on('click', function(e) {
                e.preventDefault();
            });
        });
    </script>
    @endpush

    @endif

</div>
