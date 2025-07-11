<div class="modal fade" id="modalNewTemplate" tabindex="-1" role="dialog" aria-labelledby="modalReplacersLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel"></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <label class="d-block">Template name</label>
                <input name="template_name" id="template_name" class="form-control" type="text" value="{{ $template_name }}">
                <label class="d-block">Languages</label>
                <select name="language" id="select-language" class="form-control select2-class"
                        data-placeholder="Select language" data-allow-clear="true" style="width: 100%">
                    @foreach(\App\Helpers\LanguageHelper::getAllLanguagesWithEngVersion() as $language)
                        <option value="{{ $language->alias }}">{{ $language->value }}</option>
                    @endforeach
                </select>
                <label class="d-block">Required consent</label>
                <select name="consent" id="select-consent" class="form-control select2-class"
                        data-placeholder="Select consent" data-allow-clear="true" style="width: 100%">
                    @foreach(\App\Models\EmailTemplate::getConsentList() as $title => $key)
                        <option value="{{ $key }}">{{ $title }}</option>
                    @endforeach
                </select>
                <label class="d-block">Subject</label>
                <input name="subject" id="subject" class="form-control" type="text" value="{{ $subject }}">
                <input type="hidden" name="url-template" id="url-template" class="form-control">

                <div class="row">
                    <div class="col-sm-8">
                        <div class="form-group">
                            <label for="select-replacer" class="d-block">Replacers</label>
                            <select id="select-replacer" class="form-control select2-class"
                                    data-placeholder="Select a replacer">
                                <option></option>
                                @foreach($replacers as $r)
                                    <option value="{{ $r  }}">{{ \App\Helpers\ReplacerHelper::getDescription($r) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group float-right mt-4">
                            <button class="btn btn-flat btn-info" id="add-replacer-btn">Add field</button>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" data-action="default" class="btn btn-primary btn-ok">Ok</button>
            </div>
        </div>
    </div>
</div>
@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>
    <script>


        function setReplacers() {
            @foreach( (new \App\Repositories\ReplacerRepository())->getAllowedReplacers() as $r)
                var newState = new Option("{{ \App\Helpers\ReplacerHelper::getDescription($r) }}", "{{ $r }}", true, true);
                 $("#select-replacer").append(newState);
            @endforeach

            $("#select-replacer").select2().val('').change();
        }

        $(document).ready(function () {
            var language_select = $('#modalNewTemplate #select-language');
            var consent_select = $('#modalNewTemplate #select-consent');
            language_select.select2().val('en').change();
            consent_select.select2().val('none').change();

            $('#newTemplate').on('click', function (event) {
                event.preventDefault();
                language_select.select2().val('en').change();
                consent_select.select2().val('none').change();
                $("#myModalLabel").html('New Email template');
                $("#url-template").val("{{ $app['url_generator']->generate('messaging.email-templates.new') }}");

                $("#subject").val("{{ $subject }}");

                $("#modalNewTemplate").modal('show');

                setReplacers();
            });

            $('.duplicate-template-link').on('click', function (event) {
                event.preventDefault();
                var self = $(this);
                language_select.select2().val(self.data('language')).change();
                consent_select.select2().val(self.data('consent')).change();
                $('#subject').val(self.data('subject'));
                $("#myModalLabel").html('Clone Email template');
                $("#url-template").val(self.attr("href"));

                $("#modalNewTemplate").modal('show');

                setReplacers();
            });

            //Following line prevent issues between modal window and Mosaico / Tinymce
            if ($.fn.modal && $.fn.modal.Constructor) {
                $.fn.modal.Constructor.prototype._enforceFocus = function () {};
            } else {
                console.error("Bootstrap modal plugin is not loaded");
            }

            $("#modalNewTemplate .btn-ok").click(function (e) {
                e.preventDefault();
                if ($("#modalNewTemplate .btn-ok").data('action') == 'edit') {
                    $('#name-field').data('value', $("#template_name").val());
                    $('#name-field').html($("#template_name").val());
                    $('#language-field').data('value', $("select[name=language]").val());
                    $('#language-field').html($("select[name=language]").val());
                    $('#consent-field').data('value', $("select[name=consent]").val());
                    $('#consent-field').html($("select[name=consent]").val());
                    $('#subject-field').data('value', $("#subject").val());
                    $('#subject-field').html($("#subject").val());
                    $("#modalNewTemplate").modal('hide');
                    return;
                }
                var template_name = $("#template_name").val();
                var url = $("#url-template").val();
                var subject = $("#subject").val();
                var language = $("select[name=language]").val();
                var consent = $("select[name=consent]").val();
                if (subject != '' && language != '') {
                    document.location = url + "?subject=" + subject + "&language=" + language + "&template_name=" + template_name + "&consent=" + consent;
                }
                return false;
            });

            $('#add-replacer-btn').on('click', function(e){
                e.preventDefault();
                var selectReplacers = $('#select-replacer');

                if(selectReplacers.val()){
                    var subject = $("#subject");
                    var caretPos = subject[0].selectionStart;
                    var textAreaTxt = subject.val();
                    var txtToAdd = selectReplacers.val();

                    subject.val(textAreaTxt.substring(0, caretPos) + txtToAdd + textAreaTxt.substring(caretPos) );
                }
            });

        });
    </script>
@endsection
