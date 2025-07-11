@extends('admin.layout')

@section('content')
    <div class="container-fluid">

        @include('admin.messaging.partials.topmenu')
        <div class="card">
            <div class="nav-tabs-custom">
                @includeIf("admin.messaging.partials.submenu")
                <div class="tab-content">
                    <div class="tab-pane active">
                        <div class="card-body">
                            <form id="sms-template-form" method="post">
                                <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                                <div class="row">
                                    <div class="form-group col-6 col-lg-3 col-fhd-3">
                                        <label for="sms-template-name">Template name</label>
                                        <input name="template_name" id="sms-template-name" type="text" class="form-control"
                                            value="{{ $smsTemplate['template_name'] }}" placeholder="Enter a descriptive template name">
                                    </div>
                                    <div class="form-group col-6 col-lg-3 col-fhd-3">
                                        <label for="select-language">Language</label>
                                        <select name="language" id="select-language" class="form-control select2-class"
                                                style="width: 100%;"
                                                data-placeholder="Select the language"
                                                data-allow-clear="true">
                                            <option></option>
                                            @foreach(\App\Repositories\UserRepository::getLanguages() as $key => $title)
                                                <option value="{{ $key }}">{{ $title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-6 col-lg-3 col-fhd-3">
                                        <label for="select-consent">Requires consent</label>
                                        <select name="consent" id="select-consent" class="form-control select2-class"
                                                style="width: 100%;"
                                                data-placeholder="Select the consent"
                                                data-allow-clear="true">
                                            @foreach(\App\Models\SMSTemplate::getConsentList() as $title => $value)
                                                <option value="{{ $value  }}">{{ $title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-12 col-sm-12 col-md-10 col-lg-8 col-xl-6 col-fhd-6">
                                        <label for="template">SMS Template Text</label> <span class="float-right">Characters left: <b><span id="char-num">150</span></b></span>
                                        <textarea name="template" id="template-text" onkeyup="countChar(this)" class="form-control"
                                                placeholder="Enter SMS content, could contain replacers" rows="3">{!! $smsTemplate['template'] or '' !!}</textarea>
                                    </div>
                                </div>
                                <div class="row align-items-end">
                                    <div class="form-group col-12 col-sm-6 col-md-6 col-lg-4 col-xl-3 col-fhd-3">
                                        <label for="select-replacer">Replacers</label>
                                        <select id="select-replacer" class="form-control select2-class" style="width: 100%;"
                                                data-placeholder="Select a replacer" data-allow-clear="true">
                                            <option></option>
                                            @foreach($replacers as $r)
                                                <option value="{{ $r  }}">{{ \App\Helpers\ReplacerHelper::getDescription($r) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-12 col-sm-6 col-md-6 col-lg-4 col-xl-3 col-fhd-3">
                                        <button class="btn btn-flat btn-info" id="add-replacer-btn">Add field</button>
                                        <button class="float-right btn btn-flat btn-primary w-50" id="save-template-btn">Save</button>
                                    </div>
                                </div>
                                <input type="hidden" name="id" id="sms_template_id">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="errorModal" class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Info</h4>
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
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>

    <script type="text/javascript">
        function countChar(val) {
            var len = val.value.length;
            if (len >= 150) {
                val.value = val.value.substring(0, 150);
            } else {
                $('#char-num').text(150 - len);
            }
        }

        $(document).ready(function() {

            var selectConsent = $("#select-consent");
            var selectLanguage = $("#select-language");
            var selectReplacers = $('#select-replacer');
            var templateText = $('#template-text');
            var form = $('#sms-template-form');

            @if($smsTemplate)
                selectLanguage.select2().val('{{ $smsTemplate['language'] }}').trigger("change");
                selectConsent.select2().val('{{ $smsTemplate->getConsent() }}').trigger("change");
            @else
                selectLanguage.select2();
                selectConsent.select2();
            @endif

            selectReplacers.select2();

            $('#add-replacer-btn').on('click', function(e){
                e.preventDefault();
                if(selectReplacers.val()){
                    var caretPos = templateText[0].selectionStart;
                    var textAreaTxt = templateText.val();
                    var txtToAdd = selectReplacers.val();
                    templateText.val(textAreaTxt.substring(0, caretPos) + txtToAdd + textAreaTxt.substring(caretPos) );
                }
            });
            $('#save-template-btn').on('click', function(e){
                e.preventDefault();
                var language = selectLanguage.val();
                if(!language){
                    modalErrorMessage('You have to select one language.');
                } else {
                    form.submit();
                }
            });



        });

        function modalErrorMessage(message){
            $(".modal-body").text(message);
            $('#errorModal').modal('show');
            return false;
        };
    </script>
@endsection
