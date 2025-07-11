@extends('admin.layout')
@section('content')
    <div class="container-fluid">
        @include('admin.messaging.partials.topmenu')

        <div class="card">
            <div class="nav-tabs-custom">
                @includeIf("admin.messaging.partials.submenu")

                <div class="modal fade" id="modalReplacers" tabindex="-1" role="dialog" aria-labelledby="modalReplacersLabel"
                    aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title" id="myModalLabel">Available Replacers</h4>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <select id="replacers" name="replacers" class="form-control select2-class"
                                        data-placeholder="Select language" data-allow-clear="true">
                                    <option></option>
                                    @foreach ($replacers as $replacer)
                                        <option value="{{$replacer}}">{{\App\Helpers\ReplacerHelper::getDescription($replacer)}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary btn-insert-replacer">Insert</button>
                            </div>
                        </div>
                    </div>
                </div>
                    <div>
                        <div class="subject-card">
                            <span class="subject-ui">TEMPLATE NAME: <span id="name-field" data-value="{{ $template_name }}">{{ $template_name }}</span></span>
                            <span class="subject-ui">LANGUAGE: <span id="language-field" data-value="{{ $language }}">{{ \App\Helpers\LanguageHelper::languageMapEnglish($language) }}</span></span>
                            <span class="subject-ui">EMAIL SUBJECT: <span id="subject-field" data-value="{{ $subject }}">{{ $subject }}</span></span>
                            <span class="subject-ui">REQUIRED CONSENT: <span id="consent-field" data-value="{{ $consent }}">{{ \App\Models\EmailTemplate::getConsentName($consent) }}</span></span>
                            <a data-toggle="tooltip" data-placement="right" title="Edit language and subject" href="javascript:void(0)"
                            class="subject-ui" id="edit-lang-subj-btn"><i class="fa fa-fw fa-pencil"></i> EDIT</a>
                        </div>
                        <div class="mo-standalone"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $('#replacers').select2();
            $('#edit-lang-subj-btn').on('click',function(event) {
                event.preventDefault();
                $("#modalNewTemplate #myModalLabel").html('Edit language and subject');
                $('#modalNewTemplate #template_name').val($('#name-field').data('value'));
                $('#modalNewTemplate #select-language').select2().val($('#language-field').data('value')).change();
                $('#modalNewTemplate #subject').val($('#subject-field').data('value'));
                var consent = $('#consent-field').data('value');
                $('#modalNewTemplate #select-consent').select2().val(consent == '' ? 'none' : consent).change();
                $("#modalNewTemplate .btn-ok").data('action', 'edit');
                $("#modalNewTemplate").modal('show');
            });
        });

        $(window).bind('beforeunload', function() {
            if (window.savedTemplate != window.viewModel.exportJSON() && !window.downloading)
            {
                return "You have unsaved changes on this page. Do you want to leave this page and discard your changes or stay on this page?";
            }
            window.downloading = false;
        });

    </script>
@endsection

@section('header-css')
    @parent
    <link rel="stylesheet" href="<?=getenv('MOSAICO_URL')?>/dist/mosaico-material.min.css?v=0.10"/>
    <link rel="stylesheet" href="<?=getenv('MOSAICO_URL')?>/dist/vendor/notoregular/stylesheet.css"/>
    <link rel="stylesheet" href="<?=getenv('MOSAICO_URL')?>/bower_components/evol-colorpicker/css/evol.colorpicker.min.css"/>
    <!-- 2k -->
    <style>
        body {
            background-color: inherit;
        }
        #toolbar {
            background-color: #3c8dbc;
        }

        #main-toolbox #tooltabs.ui-tabs .ui-tabs-nav {
            background-color: #3c8dbc;
        }

        #toolbar .ui-button, #preview-toolbar .ui-button {
            background-color: #3c8dbc;
            color: #eee !important;
        }

        .mo .ui-button .ui-button-text, .mo .ui-button .ui-icon {
            color: #eee !important;
        }

        #main-toolcard #tooltabs.ui-tabs .ui-tabs-nav {
            background-color: #3c8dbc !important;
        }

        #main-toolbox #tooltabs.ui-tabs {
            border: none
        }

        #main-toolbox #tooltabs.ui-tabs .ui-tabs-nav li  {
            background-color: #3c8dbc !important;
            border: none;
        }

        .subject-card {
            background-color: #3c8dbc;
            padding-top: 10px;
            padding-bottom: 15px;
        }

        .subject-ui {
            color: #eee !important;
            font-weight: 900;
            vertical-align: middle;
            font-family: Noto Sans,Helvetica Neue,Helvetica,Arial,Nimbus Sans L,Liberation Sans,Arimo,sans-serif;
            margin-left: 12px;
            line-height: 1.5em;
            text-transform: uppercase;
        }

        .subject-ui .subject-btn {
            background-color: #3c8dbc;
        }

        .nav-tabs-custom > .nav-tabs > li {
            margin-bottom: 0;
        }

        .draggable-item {
            vertical-align: top;
            padding-bottom: 5px;
        }

        #page {
            position: absolute;
            top: 260px;
            bottom: 0;
            left: 89px;
            right: 15px;
            overflow: hidden;
        }

    </style>
@endsection

@section('header-javascript')
    @parent
    <script src="<?=getenv('MOSAICO_URL')?>/dist/vendor/jquery.min.js"></script>
    <script src="<?=getenv('MOSAICO_URL')?>/dist/vendor/knockout.js"></script>

@endsection
@section('footer-javascript')
    @parent
    <script src="<?=getenv('MOSAICO_URL')?>/dist/vendor/jquery-ui.min.js"></script>
    <script src="<?=getenv('MOSAICO_URL')?>/dist/vendor/jquery.ui.touch-punch.min.js"></script>
    <script src="<?=getenv('MOSAICO_URL')?>/dist/vendor/load-image.all.min.js"></script>
    <script src="<?=getenv('MOSAICO_URL')?>/dist/vendor/canvas-to-blob.min.js"></script>
    <script src="<?=getenv('MOSAICO_URL')?>/dist/vendor/jquery.iframe-transport.js"></script>
    <script src="<?=getenv('MOSAICO_URL')?>/dist/vendor/jquery.fileupload.js"></script>
    <script src="<?=getenv('MOSAICO_URL')?>/dist/vendor/jquery.fileupload-process.js"></script>
    <script src="<?=getenv('MOSAICO_URL')?>/dist/vendor/jquery.fileupload-image.js"></script>
    <script src="<?=getenv('MOSAICO_URL')?>/dist/vendor/jquery.fileupload-validate.js"></script>
    <script src="<?=getenv('MOSAICO_URL')?>/dist/vendor/knockout-jqueryui.min.js"></script>
    <script src="<?=getenv('MOSAICO_URL')?>/bower_components/evol-colorpicker/js/evol.colorpicker.min.js"></script>
    <script src="<?=getenv('MOSAICO_URL')?>/dist/vendor/tinymce.min.js"></script>
    <script src="<?=getenv('MOSAICO_URL')?>/dist/mosaico.min.js?v=0.11"></script>

    <script>
        $(document).on('DOMSubtreeModified', '#toolimagesgallery', function(){
            $('#toolimagesgallery').find('img').each( function(){
                var flag = $(this).attr('data-label-appended-flag');
                if ((typeof flag !== typeof undefined && flag !== false))
                    return;

                var src = $(this).attr('src');
                if (!(typeof src !== typeof undefined && src !== false))
                    return;

                $(this).attr("data-label-appended-flag", true);

                var fileName = src.replace(/^.*[\\\/]/, '');

                var maxLength = 60;

                var fileNameTrunc = (fileName.length > maxLength) ? fileName.substr(0, maxLength-1) + '&hellip;' : fileName;
                fileNameTrunc = fileNameTrunc.match(/.{1,10}/g);
                fileNameTrunc = fileNameTrunc.join('<br>');

                $(this).parent().parent().append('<div style="font-size:12px" title="'+fileName+'" >'+fileNameTrunc+'</div>')

            });
        });

        $(document).on('load', function () {
            $.fn.modal.Constructor.prototype._enforceFocus = function () {
            };
            $(".btn-insert-replacer").click(function () {
                var replacer = $("#replacers").val();
                tinymce.activeEditor.execCommand('mceInsertContent', false, replacer);
                $("#modalReplacers").modal('hide');
            });

            $("#insert-replacers-btn").click(function () {
                addReplacer(tinymce)
            });

        });
        /**
         * Called from tinymce.
         * @param {type} viewModel
         * @returns {undefined}
         * */
        function addReplacer(editor) {
            $("#modalReplacers").modal('show');
        }

        function insertReplacer(editor, replacer) {
            editor.insertContent(replacer)
        }

        $(function () {

            var hash = "{{ $template->hash }}";
            var subject = "{{ $template->subject }}";
            var basePath = window.location.href.substr(0, window.location.href.lastIndexOf('/'));
                    @if ($template)
            var metadata = <?= $template->metadata ?>;
            var template = <?= $template->template ?>;
                    @endif
            var plugins = [
                    // plugin for integrating save button
                    function (viewModel) {

                        window.viewModel = viewModel;

                        @if (!$is_cloned)
                            window.savedTemplate = viewModel.exportJSON();
                                @endif

                        var saveCmd = {
                                name: 'Save', // l10n happens in the template
                                enabled: ko.observable(true)
                            };

                        var downloadCmd = {
                            name: 'Download', // l10n happens in the template
                            enabled: ko.observable(true)
                        };

                        saveCmd.execute = function () {
                            saveCmd.enabled(false);
                            viewModel.metadata.changed = Date.now();
                            if (typeof viewModel.metadata.key == 'undefined' || '{{ $is_cloned }}' == '1') {
                                var rnd = Math.random().toString(36).substr(2, 7);
                                viewModel.metadata.key = rnd;
                            }

                            var template = viewModel.exportJSON();
                            window.savedTemplate = template;

                            $.ajax({
                                url: "{{$app['url_generator']->generate('messaging.email-templates.save')}}",
                                type: "POST",
                                data: {
                                    'hash': viewModel.metadata.key,
                                    'metadata': viewModel.exportMetadata(),
                                    'template': template,
                                    'subject': $('#subject-field').data('value'),
                                    'template_name': $('#name-field').data('value'),
                                    'html': viewModel.exportHTML(),
                                    'language': $('#language-field').data('value'),
                                    'consent': $('#consent-field').data('value')
                                },
                                success: function (data) {
                                    viewModel.notifier.success(viewModel.t('Successfully saved.'));
                                    if (typeof(data.url) != 'undefined' && data.status == 'success') {
                                        setTimeout(window.location.href = data.url, 1000);
                                    }

                                },
                                error: function (jqXHR, textStatus, errorMessage) {
                                    viewModel.notifier.error(viewModel.t('Saving failed.'));
                                    window.savedTemplate = "";
                                }
                            }).always(function () {
                                saveCmd.enabled(true);
                            });
                        };

                        downloadCmd.execute = function () {
                            window.downloading = true;
                            var emailProcessorBackend = "{{$app['url_generator']->generate('messaging.email-templates.download')}}";
                            downloadCmd.enabled(false);
                            viewModel.notifier.info(viewModel.t("Downloading..."));
                            viewModel.exportHTMLtoTextarea('#downloadHtmlTextarea');
                            document.getElementById('downloadForm').setAttribute("action", emailProcessorBackend);
                            document.getElementById('downloadForm').submit();
                            downloadCmd.enabled(true);
                        };

                        viewModel.save = saveCmd;
                        viewModel.download = downloadCmd;
                    }

                ];
                    $('body').addClass('layout-fixed')
                    @if ($template)
            var ok = Mosaico.start({
                    imgProcessorBackend: basePath + '/img/',
                    emailProcessorBackend: basePath + '/mosaico/dl/',
                    titleToken: "Email Editor",
                    fileuploadConfig: {
                        url: "{{$app['url_generator']->generate('messaging.email-templates.upload')}}"
                    }
                }, '/phive/admin/customization/plugins/mosaico-template/default/template-default.html', metadata, template, plugins);
                    @else
            var ok = Mosaico.start({
                    imgProcessorBackend: basePath + '/img/',
                    emailProcessorBackend: basePath + '/dl/',
                    titleToken: "Email Editor",
                    fileuploadConfig: {
                        url: "{{$app['url_generator']->generate('messaging.email-templates.upload')}}"
                    }
                }, '/phive/admin/customization/plugins/mosaico-template/default/template-default.html', undefined, undefined, plugins);
            @endif
        });


    </script>
@endsection
