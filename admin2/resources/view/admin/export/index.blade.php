@if(p($permission))

    <style>
        .failed .error {
            display: none;
        }

        .failed.show-error .error {
            display: inline-block;
        }

        .failed.show-error .status {
            display: none;
        }

        .open-error {
            text-decoration: underline #3c8dbc;
            cursor: pointer;
        }

        .close-error {
            cursor: pointer;
            font-size: 1.2em;
            vertical-align: middle;
        }

        .close-error:hover,
        .open-error:hover {
            text-decoration: none;
            color: #3c8dbc;
            cursor: pointer;
        }
        .export-btn {
            color: #3c8dbc;
            cursor: pointer;
        }
        .export-btn:hover{
            color: #83b7d5;
        }
        .disabled {
            cursor: wait !important;
        }
    </style>
    <script>
        function checkStatus(id, type) {
            $.ajax(
                "{{ $app['url_generator']->generate('export.progress.check', ['id' => 'replace_id_here', 'type' => 'replace_type_here']) }}"
                    .replace('replace_id_here', id)
                    .replace('replace_type_here', type),
                {
                    success: function (res) {
                        if (res.status == "{{\App\Models\Export::STATUS_PROGRESS}}") {
                            window.setTimeout(function () {
                                checkStatus(res['export'], res['type']);
                            }, 2000);
                        } else {
                            location.reload();
                        }
                    }
                });
        }

        setTimeout(function () {
            $("#export-{{$id}} .close-error, #export-{{$id}} .open-error").unbind('click');
            $("#export-{{$id}} .close-error, #export-{{$id}} .open-error").click(function () {
                $(this).parent().parent().toggleClass('show-error');
            });
        }, 1000);

        $('body').on('click','.export-btn',function() {
            let btn = $(this);
            let url = btn.attr("data-url");

            if(btn.hasClass('disabled')) {
                return false;
            }
            btn.addClass('disabled');
            $.ajax(url, {
                success: function (res) {
                    if(res.forbidden) {
                        displayNotifyMessage('warning', "You do not have permission for this action.");
                        btn.removeClass('disabled');
                        return;
                    }

                    const successStatuses = [
                        {{\App\Models\Export::STATUS_SCHEDULED}},
                        {{\App\Models\Export::STATUS_PROGRESS}},
                        {{\App\Models\Export::STATUS_FINISHED}}
                    ];

                    if(successStatuses.includes(res.status)) {
                        location.reload();
                    } else {
                        displayNotifyMessage('error', "Export wasn't scheduled. Please reload the page and try again later.");
                    }
                }
            });
        });
    </script>
    <div id="export-{{$id}}" style="display: inline-block">
        @if(is_null($export))
            <span class="export-btn" data-url="{{ $app['url_generator']->generate('export.process', ['type' => $type, 'id' => $id, 'schedule_time' => is_null($schedule_time) ? 'null' : $schedule_time]) }}">
                {{$export_text}}
            </span>
        @else
            @if($export->status == \App\Models\Export::STATUS_SCHEDULED)
                <span><i class="fa fa-refresh fa-spin"></i> The export was scheduled </span> ({{$export->created_at}})
            @endif
            @if($export->status == \App\Models\Export::STATUS_PROGRESS)
                <span><i class="fa fa-refresh fa-spin"></i> Export in progress... </span> ({{$export->created_at}})
                <script>
                    checkStatus("{{$export->id}}", "{{$type}}");
                </script>
            @endif
            @if($export->status == \App\Models\Export::STATUS_FAILED)
                @if($allow_multiple_exports)
                    <span class="export-btn" data-url="{{ $app['url_generator']->generate('export.process', ['type' => $type, 'id' => $id, 'schedule_time' => $schedule_time]) }}">{{$export_text}}</span>
                    |
                @endif
                <span class="failed">
                <span class="error">{{$export->data}} <i class="fa fa-times close-error"></i></span>
                <span class="status">Last export failed (<span class="open-error">why?</span>). Attempts {{$export->attempts}} from {{\App\Models\Export::MAX_ATTEMPTS}} ({{$export->updated_at}})</span>
            </span>
            @endif
            @if($export->status == \App\Models\Export::STATUS_FINISHED)
                @if($allow_multiple_exports)
                    <span class="export-btn" data-url="{{ $app['url_generator']->generate('export.process', ['type' => $type, 'id' => $id, 'schedule_time' => $schedule_time]) }}">{{$export_text}}</span>
                    |
                @endif
                <a href="{{ $export->getFile($app) }}">{{$download_text}} </a> ({{$export->updated_at}})
            @endif
        @endif
    </div>
@endif


