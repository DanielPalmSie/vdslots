@extends('admin.layout')

<?php
$u = cu($user->username);
?>

@section('header-css')
    @parent
    {{ loadCssFile("/phive/admin/customization/styles/css/documents.css") }}
    {{ loadCssFile("/diamondbet/css/sourceoffunds.css") }}
    {{ loadCssFile("/diamondbet/css/new-registration.css") }}
@endsection

@section('content')
    @include('admin.user.partials.header.actions')
    @include('admin.user.partials.header.main-info')

    @include('admin.user.documents.partials.replace_file_modal')
    @include('admin.user.documents.partials.reject_reason_modal')


    {{-- TODO: move these 2 partials to the document partial, so they will be rerendered too, but the first try did not work, not sure why --}}
    @if($has_source_of_funds_data)
        @include('admin.user.documents.partials.source_of_funds_modal')
    @endif

    @if($has_historical_source_of_funds_data)
        @include('admin.user.documents.partials.historical_source_of_funds_modals')
    @endif


    <div class='card border-top border-top-3'>
        <div class="card-body">
            <div class="create-buttons">
                @if(!$has_source_of_income_document && p('documents.create.sourceofincome'))
                    <div id="create_document_source_of_income">
                        @include('admin.user.documents.partials.create_document_source_of_income')
                    </div>
                @endif

                @if($show_source_of_wealth_buttons && !$has_source_of_funds_document && p('documents.create.sourceoffunds'))
                    <div id="create_document_source_of_wealth">
                        @include('admin.user.documents.partials.create_document_source_of_wealth')
                    </div>
                @endif

                @if($show_re_request_sow_button && p('documents.create.sourceoffunds'))
                    <div id="rerequest_document_source_of_wealth">
                        @include('admin.user.documents.partials.re_request_source_of_wealth')
                    </div>
                @endif

                @if(p('documents.create.internal.document'))
                    <div id="create_internal_document">
                        @include('admin.user.documents.partials.create_internal_document')
                    </div>
                @endif

                @if($show_proof_of_source_of_wealth_buttons && !$has_proofofwealth_document && p('documents.create.proofofwealth.document'))
                    <div id="create_proofofwealth_document">
                        @include('admin.user.documents.partials.create_proofofwealth_document')
                    </div>
                @endif

                @if($show_proof_of_source_of_wealth_buttons && !$has_proofofsourceoffunds_document && p('documents.create.proofofsourceoffunds.document'))
                    <div id="create_proofofsourceoffunds_document">
                        @include('admin.user.documents.partials.create_proofofsourceoffunds_document')
                    </div>
                @endif
            </div>
            <div id="verify-user-account-container">
                @include('admin.user.documents.partials.verify_user_account_button')
            </div>
        </div>




        <div class="clear"></div>

        @if (is_array($cross_brand_documents))
            <div class="row document-row">
            <?php $i = 1; ?>
            @foreach ($cross_brand_documents as $document)

                @include('admin.user.documents.partials.cross_brand_documents')
                <!-- todo: this works fine on full screen, but is not responsive -->
                    @if ($i % 4 == 0)
            </div>
            <div class="row document-row">
                @endif
                <?php $i++; ?>

                @endforeach
            </div>
        @endif
        @if (is_array($documents))
            <div class="row document-row">
                <?php $i = 1; ?>
                @foreach ($documents as $document)

                    {{-- For now, do not show  --}}
                    @if($document['tag'] == 'bankaccountpic')
{{--                        @if (!$document['supplier'])) {--}}
{{--                            @continue--}}
{{--                        @endif--}}

{{--                        @if ($document['supplier'] !== 'trustly') {--}}
{{--                            @continue--}}
{{--                        @endif--}}
                        @continue
                    @endif

                    @include('admin.user.documents.partials.document')
                    <!-- todo: this works fine on full screen, but is not responsive -->
                    @if ($i % 4 == 0)
                        </div>
                        <div class="row document-row">
                    @endif
                    <?php $i++; ?>

                @endforeach
            </div>
        @else
        <div class="callout callout-warning">
            <h5>
                <i class="fas fa-exclamation-triangle"></i>
                Notice
            </h5>
            {{ t('cannot_get_documents') }}
        </div>
        @endif
    </div>

    <div class="clear"></div>
@endsection

@section('footer-javascript')
    @parent
    {{ loadJsFile("/phive/admin/customization/scripts/documents.js") }}
    {{ loadJsFile("/phive/js/sourceoffunds.js") }}
    <script>
        $(document).ready(function() {
            bindClickEvents();
            if(typeof setupSourceOfFundsBox != 'undefined') {
                setupSourceOfFundsBox();
                $("#submit_source_of_funds_form").height("35px");
            }
        });

        function bindClickEvents() {  // can optionally be moved to documents.js

            enableSubmit();
            showAjaxFlashMessages();

            $("#select-reject-reason").select2().val("");

            $('.replace_file_button').off('click');
            $('.replace_file_button').on('click', function(e) {
                e.preventDefault();
                var self = $(this);
                $('#document_type').val(self.data('documenttype'));
                $('#document_id').val(self.data('documentid'));
                $('#file_id').val(self.data('fileid'));
                $('#subtag').val(self.data('subtag'));
                $('#replace_file_modal_title').text(self.data('title'));
                $('#replace_file_modal_label').text(self.data('label'));
            });

            $('.source_of_funds_button').off('click');
            $('.source_of_funds_button').on('click', function(e) {
                e.preventDefault();
                var self = $(this);
                $('#document_type').val(self.data('documenttype'));
                $('#document_id').val(self.data('documentid'));
//                $('#source_of_funds_modal_title').text(self.data('title'));
//                $('#source_of_funds_modal_label').text(self.data('label'));
            });

            $('.reject_file_button').off('click');
            $('.reject_file_button').on('click', function(e) {
                e.preventDefault();
                var self = $(this);
//                $("#select-reject-reason").select2().val(""); // does not work
                $('.select2-container--default').prop("style", "width: 400px"); // hack
//                $('#select-reject-reason').val(""); // does not work
//                $("#select-reject-reason").select2().val("");  // does not work
                $('#reject_reason_document_type').val(self.data('documenttype'));
                $('#reject_reason_document_id').val(self.data('documentid'));
                $('#reject_reason_file_id').val(self.data('fileid'));
                $('#reject_reason_element_id').val(self.data('elementid'));
                $('#reject_reason_modal_title').text(self.data('title'));
                $('#reject_reason_modal_label').text(self.data('label'));
                $('#reject_reason_modal_form').prop("action", self.data('url'));
            });

            $("#select-reject-reason").on('change', function() {
                if($("#select-reject-reason").val() === '') {
                    $('#submit_reject_reason').prop('disabled', true);
                } else {
                    $('#submit_reject_reason').prop('disabled', false);
                }
            });

            /**
             * Submits the reject reason and rerenders the document
             */
            $('#submit_reject_reason').off('click');
            $('#submit_reject_reason').on('click', function(e) {
                e.preventDefault();
                var self = $(this);
                // post form with ajax, close modal and rerender document on success, show error on failure
                var element_id   = $('#reject_reason_element_id').val();
                var formdata     = new FormData($("#reject_reason_modal_form")[0]);
                var url = $('#reject_reason_modal_form').prop("action");
                $.ajax({
                    type: "POST",
                    url: url,
                    async: false,
                    cache: false,
                    contentType: false,
                    enctype: 'multipart/form-data',
                    processData: false,
                    data: formdata,
                    success: function(response){

                        $('#reject_reason_modal').modal('toggle');

                        var data = jQuery.parseJSON(response);
                        if(data.success === true) {
                            // refresh document
                            refreshDocument(data, element_id);
                        } else {
                            showAjaxFlashMessages();
                        }
                    }
                });
            });

            $('.action-ajax-set-btn').off('click');
            $('.action-ajax-set-btn').on('click', function(e) {
                e.preventDefault();

                var dialogTitle   = $(this).data("dtitle");
                var dialogMessage = $(this).data("dbody");
                var dialogUrl     = $(this).attr('href');
                var element_id    = $(this).data("element_id");

                if(element_id == undefined) {
                    element_id    = $(this).closest('.document').attr('id');  // target a parent with class = 'document'
                }

                if($(this).data("disabled") != 1){

                    // Reload a single document when an action is performed
                    showConfirmBtnAjaxUsingResponse(dialogTitle, dialogMessage, dialogUrl, refreshDocument, element_id, undefined);
                }
            });

            // This is only used by the Verify Account button
            $('.action-ajax-set-btn-2').off('click');
            $('.action-ajax-set-btn-2').on('click', function(e) {
                e.preventDefault();

                var dialogTitle   = $(this).data("dtitle");
                var dialogMessage = $(this).data("dbody");
                var dialogUrl     = $(this).attr('href');
                if($(this).data("disabled") != 1) {

                    // Reload a single document when an action is performed
                    showConfirmBtnAjax(dialogTitle, dialogMessage, dialogUrl, refreshVerifyAccountPartial, '');
                }
            });

            // This is only used by the Create Source of Wealth button, and the Create Internal Document button
            $('.action-ajax-set-btn-3').off('click');
            $('.action-ajax-set-btn-3').on('click', function(e) {
                e.preventDefault();

                var dialogTitle   = $(this).data("dtitle");
                var dialogMessage = $(this).data("dbody");
                var dialogUrl     = $(this).attr('href');
                var element_id    = $("[id*='addresspic']").attr('id');
                var button_id     = $(this).attr('id');
                if($(this).data("disabled") != 1) {

                    // Insert this document on the page if the action is successful
                    showConfirmBtnAjaxUsingResponse(dialogTitle, dialogMessage, dialogUrl, insertNewDocument, element_id, button_id);
                }
            });

            $('.action-ajax-delete-btn').off('click');
            $('.action-ajax-delete-btn').on('click', function(e) {
                e.preventDefault();

                var dialogTitle   = $(this).data("dtitle");
                var dialogMessage = $(this).data("dbody");
                var dialogUrl     = $(this).attr('href');
                var element_id    = $(this).closest('.document').attr('id');
                if($(this).data("disabled") != 1){

                    // Removes the document from the page on success
                    showConfirmBtnAjax(dialogTitle, dialogMessage, dialogUrl, removeElement, element_id);
                }
            });

            $('.action-ajax-deletefile-btn').off('click');
            $('.action-ajax-deletefile-btn').on('click', function(e) {
                e.preventDefault();

                var dialogTitle   = $(this).data("dtitle");
                var dialogMessage = $(this).data("dbody");
                var dialogUrl     = $(this).attr('href');
                var element_id    = $(this).closest('.filecontainer').attr('id');
                if($(this).data("disabled") != 1){

                    // Removes the document from the page on success
                    showConfirmBtnAjax(dialogTitle, dialogMessage, dialogUrl, removeElement, element_id);
                }
            });

            // Files will be uploaded as soon as they are added to the form
            $('.upload_field').off('change');
            $('.upload_field').on('change', function(e) {
                e.preventDefault();

                var element_id   = $(this).closest('.document').attr('id');
                var document_id  = $(this).data("ddocumentid");
                var formdata     = new FormData($("#upload_form_"+document_id)[0]);  // works

                if($(this).data("disabled") != 1){

                    // Upload the files
                    uploadFiles(formdata, element_id);
                }
            });

            // Still used for ID documents
            $('.upload_button').off('click');
            $('.upload_button').on('click', function(e) {
                e.preventDefault();

                var element_id   = $(this).closest('.document').attr('id');
                var document_id  = $(this).data("ddocumentid");
                var formdata     = new FormData($("#upload_form_"+document_id)[0]);  // works

                if($(this).data("disabled") != 1){

                    // Upload the files
                    uploadFiles(formdata, element_id);
                }
            });
        }

        function refreshDocument(response, element_id) {
            var document_json = response.document;

            var url = '{{$app['url_generator']->generate('admin.user-rerender-document', ['user'=> $user->id])}}';

            $.ajax({
                type: "POST",
                url: url,
                data: {document : document_json},
                success: function(response){
                    // rerender the document
                    $('#'+element_id).replaceWith(response);
                    bindClickEvents();
                }
            });
        }

        function removeElement(element_id) {
            $("#"+element_id).hide(1000);
            showAjaxFlashMessages();
        }

        function uploadFiles(formdata, element_id) {
            var url = '{{$app['url_generator']->generate('admin.user-document-add-multiple-files', ['user' => $user->id])}}';
            $.ajax({
                type: "POST",
                url: url,
                async: false,
                cache: false,
                contentType: false,
                enctype: 'multipart/form-data',
                processData: false,
                data: formdata,
                success: function(response){

                    var data = jQuery.parseJSON(response);
                    if(data.success === true) {
                        // refresh document
                        refreshDocument(data, element_id);
                    } else {
                        showAjaxFlashMessages();
                    }
                }
            });
        }

        function refreshVerifyAccountPartial(empty_string) {
            var url = '{{$app['url_generator']->generate('admin.user-rerender-verifyaccount', ['user'=> $user->id])}}';

            var element_id = 'verify-user-account-container';

            $.ajax({
                type: "GET",
                url: url,
                success: function(response){
                    // rerender the partial
                    $('#'+element_id).html(response);
                    bindClickEvents();
                }
            });
        }

        function insertNewDocument(response, element_id, button_id) {
            var document_json = response.document;

            var url = '{{$app['url_generator']->generate('admin.user-rerender-document', ['user'=> $user->id])}}';

            $.ajax({
                type: "POST",
                url: url,
                data: {document : document_json},
                success: function(response){
                    // insert the document
                    $(response).insertAfter('#'+element_id);
                    // remove the button, except for Internal documents
                    if(button_id !== 'create_internal_document_button') {
                        $('#'+button_id).hide(1000);
                    }
                    bindClickEvents();
                }
            });
        }

        function addClassError(object)
        {
            $("#"+$(object).attr("id")).removeClass('valid');
            $("#"+$(object).attr("id")).addClass('error');
        }

        function submitSourceOfFundsAsAdmin(){
            var data = {
                "name_of_account_holder":  $('#name_of_account_holder').val(),
                "address":                 $('#address').val(),
                "salary":                  ($('#salary').is(':checked'))       ? 1 : 0,
                "business":                ($('#business').is(':checked'))     ? 1 : 0,
                "income":                  ($('#income').is(':checked'))       ? 1 : 0,
                "dividend":                ($('#dividend').is(':checked'))     ? 1 : 0,
                "interest":                ($('#interest').is(':checked'))     ? 1 : 0,
                "gifts":                   ($('#gifts').is(':checked'))        ? 1 : 0,
                "pocket_money":            ($('#pocket_money').is(':checked')) ? 1 : 0,
                "others":	           $('#others').val(),
                "occupation":	           $('#occupation').val(),
                "annual_income":           $("input[name='annual_income']:checked").val(),
                "no_income_explanation":   $('#no_income_explanation').val(),
                "name":	                   $('#name').val(),
                "password":                $('#password').val(),
                "submission_day":          $('#submission_day').val(),
                "submission_month":        $('#submission_month').val(),
                "submission_year":         $('#submission_year').val(),
                "document_id":	           $('#document_id').val(),
                "form_version":	           $('#form_version').val(),
                "user_id":	           $('#user_id').val(),
                "your_savings":         $("input[name='your_savings']:checked").val(),
                "savings_explanation":  $('#savings_explanation').val()
            };

            postSourceOfFundsFormAsAdmin(data);
        }

        function postSourceOfFundsFormAsAdmin(data) {
            $.post("/phive/modules/Micro/source_of_funds.php", data, function(data){
                if(data.status != 'error') {
                    $('#source_of_funds_modal').modal('toggle');
                    displayNotifyMessage('success', 'The form was succesfully updated');
                } else {
                    // get errors from the json, and add class error to each field which has an error
        //            alert(JSON.stringify(data.errors));
                    $(".error").removeClass('error');
                    $.each(data.errors, function(key, value) {
                        addClassError($("#"+key));
                    });

                    // remove element to prevent showing double errors
                    $('#errorZone').remove();
                    $('#sourceoffundsbox_step1').after(data.info);  // this adds the errorZone div
                }

            }, "json");
        }

    </script>
@endsection
