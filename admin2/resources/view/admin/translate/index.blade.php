@extends('admin.layout')

@section('header-css')
    @parent
    <link rel="stylesheet" href="/phive/admin/customization/styles/css/translate.css">
@endsection

@section('content-header')
    <div class="container-fluid">
       <div class="row mb-2">
           <div class="col-sm-6">
               <h1>Translate</h1>
           </div>
           <div class="col-sm-6">
               <ol class="breadcrumb float-sm-right">
                   <li class="breadcrumb-item"><a href="{{ $app['url_generator']->generate('home') }}"><i class="fa fa-cog mr-2"></i>Admin Home</a></li>
                   <li class="breadcrumb-item active">Translate</li>
               </ol>
           </div>
       </div>
       <div class="row">
           <div class="col-12">
               <p class="mb-0">
                   Translation characters usage:
                   <span id="translate-used-characters">{{ $usedCharactersCount }}</span>
                   /
                   <span id="translate-characters-limit">{{ $charactersLimit }}</span>
               </p>
           </div>
       </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 translate-type-radio-buttons">
            <div class="form-check-inline">
                <label>
                    <input id="translate-text-radio" type="radio" name="tab" checked>
                    Translate text
                    </label>
                </div>
                <div class="form-check-inline">
                    <label>
                        <input id="translate-file-radio" type="radio" name="tab">
                        Translate files
                    </label>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-4 col-lg-5 py-2">
                <select
                    id="source-lang-select"
                    class="form-control select2-class"
                    data-placeholder="Detect language"
                    data-allow-clear="true"
                >
                    @foreach($sourceLangs as $lang)
                        <option value="{{$lang->code}}" @if($lang->code === $defaultSourceLangCode) selected @endif>
                            {{ $lang->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-1 col-lg-1 translate-swap-langs align-self-center py-2">
                <i id="swap-langs" class="fa fa-exchange-alt translate-swap-langs-icon"></i>
            </div>
            <div class="col-4 col-lg-5 py-2">
                <select
                    id="target-lang-select"
                    class="form-control select2-class"
                >
                    @foreach($targetLangs as $lang)
                        <option value="{{$lang->code}}" @if($lang->code === $defaultTargetLangCode) selected @endif>
                            {{ $lang->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-3 col-lg-1 text-center py-2">
                <button id="translate-button" class="btn btn-info btn-block" type="button">
                    Translate
                </button>
            </div>
        </div>

        <div id="translate-text" class="row">
            <div class="col-4 col-lg-5">
                <textarea id="to-translate-textarea" class="translate-textarea"></textarea>
            </div>
            <div class="col-1 col-lg-1">
            </div>
            <div class="col-4 col-lg-5">
                <textarea id="translated-textarea" class="translate-textarea"></textarea>
            </div>
            <div class="col-3 col-lg-1">
                <label class="translate-tag-handling-label">
                    Tag handling
                    <select id="tag-handling-select" class="form-control select2-class">
                        <option value="" selected>No</option>
                        <option value="html">HTML</option>
                        <option value="xml">XML</option>
                    </select>
                </label>
            </div>
        </div>

        <div id="translate-file" class="row d-none">
            <div class="col-9 col-lg-11">
                <div id="dropzone">
                    <form id="file-to-translate-upload" action="{{ $app['url_generator']->generate('translate.file') }}" class="dropzone translate-dropzone">
                        <div class="dz-message">
                            Drop .docx, .pptx, .xlsx, .pdf, .html, .txt, .xlf, .xliff file here or click to select file to upload (max file size is 8MB).<br/>
                            <span class="note">
                                <small>Please note that with every submitted document of type .pptx, .docx, .xlsx, or .pdf, you are billed a minimum of 50,000 characters with the DeepL API plan, no matter how many characters are included in the document.</small>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-3 col-lg-1">
                <a id="download-translated-file-link" class="btn btn-info translate-download-link d-none" download>
                    Download
                </a>
                <span id="translate-file-progress" class="d-none">
                    Approximately <span id="translate-file-progress-seconds"></span> seconds left
                </span>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script>
        $(function () {
            const LS_STATE_KEY = 'translate.state';
            const LS_FILE_INFO_KEY = 'translate.file-info';

            const sourceLangCodes = JSON.parse('{!! json_encode($sourceLangCodes, JSON_THROW_ON_ERROR) !!}');
            const targetLangCodes = JSON.parse('{!! json_encode($targetLangCodes, JSON_THROW_ON_ERROR) !!}');

            const translateTextRadio = $('#translate-text-radio');
            const translateFileRadio = $('#translate-file-radio');
            const sourceLangSelect = $('#source-lang-select');
            const targetLangSelect = $('#target-lang-select');
            const tagHandlingSelect = $('#tag-handling-select');
            const translateButton = $('#translate-button');
            const langSwapper= $('#swap-langs');
            const toTranslateTextArea = $('#to-translate-textarea');
            const translatedTextArea = $('#translated-textarea');
            const translateTextSection = $('#translate-text');
            const translateFileSection = $('#translate-file');
            const downloadTranslatedFileLink = $('#download-translated-file-link');
            const translateFileProgress = $('#translate-file-progress');
            const translateFileProgressSeconds = $('#translate-file-progress-seconds');

            Dropzone.autoDiscover = false;

            // Destroy existing Dropzone instance if it exists
            if (Dropzone.instances.length > 0) {
                Dropzone.instances.forEach(function(instance) {
                    instance.destroy();
                });
            }

            const fileToTranslateDropzone = new Dropzone('#file-to-translate-upload', {
                autoProcessQueue: false,
                addRemoveLinks: true,
                parallelUploads: 1,
                maxFiles: 1,
                maxFilesize: 30,
                acceptedFiles: '.docx,.pptx,.xlsx,.pdf,.html,.txt,.xlf,.xliff',
                init: function() {
                    this.on('addedfile', function(file) {
                        if (this.files.length > 1) {
                            this.removeFile(this.files[0]);
                        }
                    });
                }
            });


            function normalizeLangCode(langCode) {
                return langCode.split('-')[0];
            }

            // Source lang codes and target lang codes lists are not the same
            // (for example, target lang codes include 2 english versions: EN-GB and EN-US
            // while source lang codes include only EN). So, we need conversion on lang swap
            function getSourceVersionOfLangCode(langCode) {
                if (sourceLangCodes.includes(langCode)) {
                    return langCode;
                }
                const normalizedLangCode = normalizeLangCode(langCode);

                return sourceLangCodes.find(langCode => langCode.startsWith(normalizedLangCode));
            }

            function getTargetVersionOfLangCode(langCode) {
                if (targetLangCodes.includes(langCode)) {
                    return langCode;
                }
                const normalizedLangCode = normalizeLangCode(langCode);

                return targetLangCodes.find(langCode => langCode.startsWith(normalizedLangCode));
            }

            function swapLangs() {
                const sourceLangCode = sourceLangSelect.val();
                const targetLangCode = targetLangSelect.val();

                sourceLangSelect.val(getSourceVersionOfLangCode(targetLangCode)).trigger('change');
                targetLangSelect.val(getTargetVersionOfLangCode(sourceLangCode)).trigger('change');
            }

            function setStateToLocalStorage() {
                const translateText = translateTextRadio.is(':checked');
                const translateFile = translateFileRadio.is(':checked');
                const sourceLangCode = sourceLangSelect.val();
                const targetLangCode = targetLangSelect.val();
                const tagHandling = tagHandlingSelect.val();

                window.localStorage.setItem(LS_STATE_KEY, JSON.stringify({
                    translateText,
                    translateFile,
                    sourceLangCode,
                    targetLangCode,
                    tagHandling,
                }));
            }

            function applyLocalStorageState() {
                const state = window.localStorage.getItem(LS_STATE_KEY);
                if (!state) {
                    return;
                }

                const {
                    translateText,
                    translateFile,
                    sourceLangCode,
                    targetLangCode,
                    tagHandling
                } = JSON.parse(state);

                if (translateText) {
                    translateTextRadio.prop('checked', translateText);
                }

                if (translateFile) {
                    translateFileSection.removeClass('d-none');
                    translateTextSection.addClass('d-none');
                    translateFileRadio.prop('checked', translateFile);
                }

                if (sourceLangCode) {
                    sourceLangSelect.val(sourceLangCode).trigger('change');
                }

                if (targetLangCode) {
                    targetLangSelect.val(targetLangCode).trigger('change');
                }

                if (tagHandling) {
                    tagHandlingSelect.val(tagHandling).trigger('change');
                }
            }

            async function translateText() {
                const url = "{{ $app['url_generator']->generate('translate.text') }}";
                const text = toTranslateTextArea.val();
                const sourceLang = sourceLangSelect.val();
                const targetLang = targetLangSelect.val();
                const tagHandling = tagHandlingSelect.val();

                await $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        text: text,
                        sourceLang: sourceLang,
                        targetLang: targetLang,
                        tagHandling: tagHandling,
                    },
                    success: function (response) {
                        if (response.error) {
                            alert(response.error);
                            return;
                        }

                        translatedTextArea.val(response.text);
                    },
                    error: ajaxErrorHandler
                });
            }

            function translateFile() {
                const url = "{{ $app['url_generator']->generate('translate.file') }}";
                const file = fileToTranslateDropzone.getAcceptedFiles()[0];
                const fileName = file.name;
                const sourceLang = sourceLangSelect.val();
                const targetLang = targetLangSelect.val();

                const formData = new FormData();
                formData.append('file', file);
                if (sourceLang) {
                    formData.append('sourceLang', sourceLang);
                }
                formData.append('targetLang', targetLang);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    enctype: 'multipart/form-data',
                    success: function (response) {
                        if (response.error) {
                            alert(response.error);
                            return;
                        }

                        window.localStorage.setItem(LS_FILE_INFO_KEY, JSON.stringify({
                            documentId: response.documentId,
                            documentKey: response.documentKey,
                            fileName: fileName,
                        }));

                        checkFileStatus();
                    },
                    error: ajaxErrorHandler
                });
            }

            function checkFileStatus() {
                const url = "{{ $app['url_generator']->generate('translate.file.status') }}";
                const data = JSON.parse(window.localStorage.getItem(LS_FILE_INFO_KEY));

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    success: function (response) {
                        if (response.error || response.status === 'done') {
                            translateButton.prop('disabled', false);
                            translateFileProgress.hide();
                        }

                        if (response.error) {
                            alert(response.error);
                            return;
                        }

                        if (response.status !== 'done') {
                            if (response.secondsRemaining) {
                                translateFileProgress.show();
                                translateFileProgressSeconds.text(response.secondsRemaining);
                            }

                            setTimeout(checkFileStatus, 3000);
                            return;
                        }

                        const downloadLink = '{{ $app['url_generator']->generate('translate.file.download') }}';
                        const params = new URLSearchParams(JSON.parse(window.localStorage.getItem(LS_FILE_INFO_KEY)));

                        downloadTranslatedFileLink.attr('href', downloadLink + '?' +  params);
                        downloadTranslatedFileLink.show();
                    },
                    error: ajaxErrorHandler
                });
            }

            function updateCharactersUsage() {
                const url = "{{ $app['url_generator']->generate('translate.characters-usage') }}";

                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function (response) {
                        $('#translate-used-characters').text(response.usedCharactersCount);
                        $('#translate-characters-limit').text(response.charactersLimit);
                    },
                    error: ajaxErrorHandler
                });
            }

            function ajaxErrorHandler() {
                alert('AJAX ERROR');
                translateButton.prop('disabled', false);
            }

            $([sourceLangSelect, targetLangSelect, tagHandlingSelect]).select2();

            applyLocalStorageState();

            translateTextRadio.on('click', function() {
                translateTextSection.removeClass('d-none');
                translateFileSection.addClass('d-none');
            });

            translateFileRadio.on('click', function() {
                translateTextSection.addClass('d-none');
                translateFileSection.removeClass('d-none');
            });

            sourceLangSelect.on('select2:selecting', function(e) {
                const originalSourceLangCode = sourceLangSelect.val();
                const newSourceLangCode = e.params.args.data.id;
                const targetLangCode = targetLangSelect.val();

                const isLangSwapNeeded = normalizeLangCode(newSourceLangCode) === normalizeLangCode(targetLangCode);

                if (!originalSourceLangCode && isLangSwapNeeded) {
                    sourceLangSelect.val(normalizeLangCode(targetLangCode) === 'EN' ? sourceLangCodes[0] : 'EN');
                }

                if (isLangSwapNeeded) {
                    swapLangs();
                    return;
                }
            });

            targetLangSelect.on('select2:selecting', function(e) {
                const newTargetLangCode = e.params.args.data.id;
                const sourceLangCode = sourceLangSelect.val();

                const isLangSwapNeeded = sourceLangCode
                    && (normalizeLangCode(newTargetLangCode) === normalizeLangCode(sourceLangCode));

                if (isLangSwapNeeded) {
                    swapLangs();
                    return;
                }
            });

            translateTextRadio.on('change', function() {
                setStateToLocalStorage();
            });

            translateFileRadio.on('change', function() {
                setStateToLocalStorage();
            });

            sourceLangSelect.on('change.select2', function() {
                setStateToLocalStorage();
            });

            targetLangSelect.on('change.select2', function() {
                setStateToLocalStorage();
            });

            tagHandlingSelect.on('change.select2', function() {
                setStateToLocalStorage();
            });

            langSwapper.on('click', function() {
                const originalSourceLangCode = sourceLangSelect.val();
                const originalTargetLangCode = targetLangSelect.val();
                const originalTargetValue = translatedTextArea.val();

                if (!originalSourceLangCode) {
                    sourceLangSelect.val(normalizeLangCode(originalTargetLangCode) === 'EN' ? sourceLangCodes[0] : 'EN');
                }

                toTranslateTextArea.val(originalTargetValue);
                translatedTextArea.val('');

                swapLangs();
            });

            translateButton.on('click', async function() {
                translateButton.prop('disabled', true);

                if (translateTextRadio.is(':checked')) {
                    await translateText();
                    updateCharactersUsage();
                    translateButton.prop('disabled', false);
                } else if (translateFileRadio.is(':checked')) {
                    await translateFile();
                }
            });

            downloadTranslatedFileLink.on('click', function() {
                downloadTranslatedFileLink.addClass('d-none');
                setTimeout(updateCharactersUsage, 3000);
            })
        });
    </script>
@endsection
