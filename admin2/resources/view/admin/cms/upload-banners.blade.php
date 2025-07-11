@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        @include('admin.cms.partials.topmenu')

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Banners Uploads</h3>
            </div>
            <div class="card-body">
                <p>On this page you can upload default banners and banners for promotions. </p>

                <p>Here you can also add or edit the texts for those pages that have an accompanying text. </p>

            </div>
        </div>

        @include('admin.cms.partials.selectpage')

        <div class="clear"></div>

        <div id="select-alias-container">

        </div>

        <div id="uploaded-banners-container">

        </div>

        <div id="upload-banners-container">

        </div>

        <div id="edit-strings-container">

        </div>

        <div id="edit-email-container">

        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script src="/phive/js/upload.js"></script>
    <script type="text/javascript">
        var dropzone = null;

        function validateLastPart(value) {
            var regex = new RegExp("^[0-9A-Za-z]{1,}$");
            return regex.test(value);
        }

        $(document).ready(function() {

            // Step 1: select a page
            $('#select-page').change(function() {
                $('#uploaded-banners-container').html('');
                $('#upload-banners-container').html('');
                $('#edit-strings-container').html('');

                var page_alias = $('#select-page').val();
                if(page_alias !== '') {
                    showSelectAlias(page_alias, '');
                } else {
                    $('#select-alias-container').html('');
                }
            });

            function showSelectAlias(page_alias, new_alias) {
                $.get('{{$app['url_generator']->generate('showselectbanneralias')}}'+'?page_alias='+ page_alias, function(response) {
                    $('#select-alias-container').html(response);
                    $("#select-alias").select2().val("");
                    if(new_alias !== '') {
                        $("#select-alias").select2().val(new_alias);
                        $("#select-alias").select2().val(new_alias);
                    }
                    // After the elements are on the page, we can bind the click events
                    bindClickEventToSelectAlias();
                });
            }


            // Step 2: select an alias
            function bindClickEventToSelectAlias() {
                $('#select-alias').change(function() {
                    var page_alias = $('#select-page').val();
                    var alias      = $('#select-alias').val();

                    if(alias !== '') {
                        // Check what kind of page we are dealing with
                        var type_of_page = '';
                        $.get('{{$app['url_generator']->generate('get-type-of-page')}}'+'?page_alias='+ page_alias, function(response) {
                            type_of_page = response;
                            $("#type_of_page").val(type_of_page);

                            switch (type_of_page) {
                                case 'only_banners':
                                    showUploadedBanners(page_alias, alias);
                                    showBannerUploadForm(alias, page_alias);
                                    break;

                                case 'has_text':
                                    showUploadedBanners(page_alias, alias);
                                    showBannerUploadForm(alias, page_alias);
                                    if(alias.indexOf("default") == -1) {
                                        // show editstrings-container
                                        $.get('{{$app['url_generator']->generate('showuploadlocalizedstrings')}}'+'?alias='+ alias, function(response) {
                                            $('#edit-strings-container').html(response);
                                            $("#select-type").select2().val("");
                                            $("#editstringsbutton").show();
                                            bindClickEventToEditStringsButton();
                                        });
                                    }
//                                    // show editstrings-container
//                                    $.get('{{$app['url_generator']->generate('showuploadlocalizedstrings')}}'+'?alias='+ alias, function(response) {
//                                        $('#edit-strings-container').html(response);
//                                        $("#select-type").select2().val("");
////                                        bindClickEventToEditStringsButton();
//                                        // if the selected alias does not contain 'default', show editstrings button immediately
////                                        if(alias.indexOf("default") == -1) {
////                                            $("#editstringsbutton").show();
////                                            bindClickEventToEditStringsButton();
////                                        }
//                                    });
                                    break;

                                case 'only_text':
                                    // show editstrings-container
                                    $.get('{{$app['url_generator']->generate('showuploadlocalizedstrings')}}'+'?alias='+ alias + '&page_alias=' + page_alias, function(response) {
                                        $('#edit-strings-container').html(response);
                                        $("#select-type-for-text").select2().val("");
                                        bindClickEventToSelectTypeForText();
                                        // if the selected alias does not contain 'default', show editstrings button immediately
                                        if(alias.indexOf("default") == -1) {
                                            $("#editstringsbutton").show();
                                            bindClickEventToEditStringsButton();
                                        }
                                    });
                                    break;

                                case 'is_email':
                                    $.get('{{$app['url_generator']->generate('showeditemail')}}'+'?alias='+ alias + '&page_alias=' + page_alias, function(response) {
                                        $('#edit-email-container').html(response);
                                        $("#select-email-type").select2().val("");
                                        $("#select-language").select2().val("");

                                        if(alias == 'welcome.mail' || alias == 'no-deposit-weekly' || alias == 'no-deposit-weekly-2') {
                                            bindClickEventToSelectEmailType();
                                            bindClickEventToSelectLanguage();
                                        } else {
                                            // we selected an email for bonus code
                                            $('#select-banner-type-container').hide();
                                            $('#select_email_language').show();
                                            bindClickEventToSelectLanguage();
                                        }
                                    });
                                    break;

                                default:
                                    // unknown
                                    alert('Error: type of page unknown, please contact devsupport');
                            }
                        });

                    } else {
                        $('#uploaded-banners-container').html('');
                        $('#upload-banners-container').html('');
                        $('#edit-strings-container').html('');
                        $('#edit-email-container').html('');
                    }
                });
            }

            function bindClickEventsToSeeallimages() {
                $("#seeallimages").click(function() {
                    $("#first-banner").hide();
                    $("#allimages").show();
                });

                $("#seeallleftoverimages").click(function() {
                    $("#first-leftover-banner").hide();
                    $("#leftover-images").show();
                });
            }

            function bindClickEventToSelectBannerType() {
                $('#select-banner-type').change(function() {
                    var banner_type  = $('#select-banner-type').val();
                    var type_of_page = $("#type_of_page").val();
                    var alias        = $('#select-alias').val();
                    switch (banner_type) {
                        case 'default':
                            $('#input_new_alias_container').hide();
                            $('#upload-banners').show();
                            $('#editstrings-container').show();
                            showDropzone();
                            enableDropZone();
                            if(type_of_page == 'has_text') {
                                $.get('{{$app['url_generator']->generate('showuploadlocalizedstrings')}}'+'?alias='+ alias, function(response) {
                                    $('#edit-strings-container').html(response);
                                    $("#select-type").select2().val("");
                                    $("#editstringsbutton").show();
                                    bindClickEventToEditStringsButton();
                                });
                            }
                            break;
                        case 'bonus_code':
                            $('#input_new_alias_container').show();
                            $('#upload-banners').hide();
                            $('#editstrings-container').hide();
                            break;
                        default:
                            // nothing selected, so remove elements
                            $('#input_new_alias_container').hide();
                            $('#upload-banners').hide();
                            $('#editstrings-container').hide();
                    }
                });
            }

            function bindClickEventToEditStringsButton() {
                $("#editstrings").click(function() {

                    var alias      = $('#select-alias').val();
                    var new_alias  = $('#newalias').text();
                    if(typeof new_alias != 'undefined' && new_alias != '') {
                        alias = new_alias;
                    }

                    // Use alias, and replace banner with bannertext, and add .html to the end,
                    // unless it already starts with bannertext
                    var localized_string = alias;
                    if (alias.indexOf("bannertext") === -1) {
                        localized_string = alias.replace('banner', 'bannertext') + '.html';
                    }

                    window.open('/phive/modules/Localizer/html/editstrings.php?arg0=en&arg1='+localized_string);
                });
            }

            // this is for only_text pages
            function bindClickEventToSelectTypeForText() {
                $('#select-type-for-text').change(function() {
                    var type = $('#select-type-for-text').val();
                    switch (type) {
                        case 'default':
                            $('#input_new_alias_container').hide();
                            $('#editstringsbutton').show();
                            bindClickEventToEditStringsButton();
                            break;
                        case 'bonus_code':
                            $('#input_new_alias_container').show();
                            bindClickEventToNewAlias('only_text');
                            $('#upload-banners').hide();
                            $('#editstringsbutton').hide();
                            break;
                        default:
                            // nothing selected, so remove elements
                            $('#input_new_alias_container').hide();
                            $('#upload-banners').hide();
                            $('#editstringsbutton').hide();
                    }
                });
            }

            function bindClickEventToSelectEmailType() {
                $('#select-email-type').change(function() {
                    var email_type = $('#select-email-type').val(); // bindClickEventToNewAlias('has_banners');
                    switch (email_type) {
                        case 'default':
                            $('#input_new_alias_container').hide();
                            $('#select_email_language').show();
                            break;
                        case 'bonus_code':
                            $('#input_new_alias_container').show();
                            $('#select_email_language').hide();
                            bindClickEventToNewAlias('is_email');
                            break;
                        default:
                            // nothing selected, so remove elements
                            $('#input_new_alias_container').hide();
                            $('#select_email_language').hide();
                            $('#edit-email-form').html('');
                    }
                });
            }

            function bindClickEventToSelectLanguage() {
                $("#select-language").change(function() {
                    var language = $("#select-language").val();
                    if(language != '') {
                        var page_alias = $('#select-page').val();
                        var alias      = $('#select-alias').val();

                        // Show email editor with AJAX
                        $.get('{{$app['url_generator']->generate('showeditemailform')}}'+'?alias='+ alias + '&language=' + language, function(response) {
                            $('#edit-email-form').html(response);
                            bindClickEventToSaveEmail();
                        });

                    } else {
                        $('#edit-email-form').html('');
                    }
                });
            }

            function bindClickEventToSaveEmail() {
                $("#save_email").click(function(event) {
                    event.preventDefault();

                    formdata = {
                        "subject":        $('#subject').val(),
                        "content":        $('#content').val(),
                        "replacers":	  $('#replacers').val(),
                        "mail_trigger":   $('#mail_trigger').val(),
                        "language":       $('#select-language').val()
                    };

                    var url = '{{$app['url_generator']->generate('save-email')}}';
                    $.ajax({
                        type: "POST",
                        url: url,
                        data: formdata,
                        success: function(response){

                            // show flash messages
                            showAjaxFlashMessages();
                        }
                    });
                });
            }

            function bindClickEventToNewAlias(type_of_page) {
                var element = $("#last_part");
                element.keyup(function() {
                    var value = element.val();

                    if (value != '' && !validateLastPart(value)) {
                        element.addClass('input-error');
                        $('#submit_last_part').attr('disabled', 'disabled');
                        $('#input_new_alias').find('span').html('Please enter only letters and numbers.');
                    } else {
                        element.removeClass('input-error');
                        $('#submit_last_part').removeAttr('disabled');
                        $('#input_new_alias').find('span').html('');
                    }
                });

                $("#submit_last_part").click(function(event) {
                    event.preventDefault();
                    var last_part  = $("#last_part").val();
                    var alias      = $('#select-alias').val();
                    var new_alias  = alias.replace("default", last_part);

                    // TODO: check if the new alias exists already


                    if(type_of_page == 'only_text') {
                        var html = "<p>You will be creating text for a new alias: <strong><span id='newalias'>" + new_alias + "</span></strong></p>";
                        $("#input_new_alias").html(html);
                        $("#select-type-for-text").prop("disabled");
                        $("#editstringsbutton").show();
                        bindClickEventToEditStringsButton();
                    }

                    if(type_of_page == 'has_banners') {
                        var html = "<p>You will be uploading banners for a new alias: <strong><span id='newalias'>" + new_alias + "</span></strong></p>";
                        $("#input_new_alias").html(html);
                        $("#select-banner-type").prop("disabled", true);
                        showDropzone();
                        $("#upload-banners").show();
                        $("#image_id").val("");
                        $("#image_alias").val(new_alias);
                        enableDropZone();
                    }

                    if(type_of_page == 'is_email') {
                        new_alias = alias + '.' + last_part;
                        var html = "<p>You will be creating emails for a new alias: <strong><span id='newalias'>" + new_alias + "</span></strong></p>";
                        $("#input_new_alias").html(html);
                        var language = $("#select-language").val();
                        $.get('{{$app['url_generator']->generate('showeditemailform')}}'+'?alias='+ new_alias + '&language=' + language, function(response) {
                            $('#edit-email-form').html(response);
                            bindClickEventToSaveEmail();
                        });

                    }
                });
            }

            Dropzone.autoDiscover = false;

            function enableDropZone() {
                if(!dropzone) {
                    dropzone = new Dropzone('.dropzone', {
                        init: function() {
                            this.on("queuecomplete", function(file) {
                                showAjaxFlashMessages();
                                // Refresh image previews
                                var alias = $("#select-alias").val();
                                // after creating new bonus code banners,
                                // we need to show images for the new alias, and select the new alias too
                                var new_alias = $("#newalias").text();
                                if(new_alias !== '' && typeof new_alias != 'undefined') {
                                    alias = new_alias;
                                    var page_alias = $('#select-page').val();
                                    showSelectAlias(page_alias, new_alias);
                                }
                                showUploadedBanners(page_alias, alias);

                            });
                        },
                        parallelUploads: 1,
                        maxFilesize: 3,
                        filesizeBase: 1000,
                        headers : {
                            "X-CSRF-TOKEN" : document.querySelector('meta[name="csrf_token"]').content
                        }
                    });
                    dropzone.removeAllFiles();
                }
            }

            function showDropzone() {
                var page_alias = $('#select-page').val();
                var alias      = $('#select-alias').val();

                $("#dropzone_container").show();
                $("#folder").val("image_uploads");
                $("#image_alias").val(alias);
                $("#page_alias").val(page_alias);
            }

            function showUploadedBanners(page_alias, alias) {
                $.get('{{$app['url_generator']->generate('showuploadedbanners')}}'+'?alias='+ alias + '&page_alias=' + page_alias, function(response) {
                    $('#uploaded-banners-container').html(response);
                    bindClickEventsToSeeallimages();
                    bindClickEventToDeleteButtons();
                    bindClickEventToDeleteSelectedFilesButton();
                    bindClickEventToSelectAll();
                    bindClickEventToUnselectAll();
                });
            }

            function showBannerUploadForm(alias, page_alias) {
                $.get('{{$app['url_generator']->generate('showformuploadbanners')}}'+'?alias='+ alias + '&page_alias=' + page_alias, function(response) {
                    $('#upload-banners-container').html(response);
                    $("#select-banner-type").select2().val("");
                    bindClickEventToSelectBannerType();
                    if(alias.indexOf("default") == -1) {
                        showDropzone();
                        enableDropZone();
                    } else {
                        bindClickEventToNewAlias('has_banners');
                    }
                });
            }


            // TODO: this is now ALMOST the same as in upload_images.blade
            function bindClickEventToDeleteButtons() {
                $('.action-set-btn').on('click', function(e) {
                    e.preventDefault();
                    var dialogTitle    = $(this).data("dtitle");
                    var dialogMessage  = $(this).data("dbody");
                    var dialogUrl      = $(this).attr('href');
                    var file_id        = $(this).data('dfileid');
                    if($(this).data("disabled") != 1){
                        // show confirm button, and pass function removeSrc() with param file_id to remove the image scr after the image is deleted
                        showConfirmBtnAjax(dialogTitle, dialogMessage, dialogUrl, removeImageUrl, file_id);
                    }
                });
            }

            // TODO: this is now the same as in upload_images.blade
            function bindClickEventToDeleteSelectedFilesButton() {
                $('.action-delete-selected-btn').on('click', function(e) {
                    e.preventDefault();
                    var dialogTitle    = $(this).data("dtitle");
                    var dialogMessage  = $(this).data("dbody");
                    var dialogUrl      = $(this).attr('href');

                    if($(this).data("disabled") != 1){
                        showConfirmNoUrl(dialogTitle, dialogMessage, deleteSelectedFiles, null);
                    }
                });
            }

            // TODO: this is now ALMOST the same as in upload_images.blade,
            // we can use the functions removeImageUrl or RemoveElement as paramNotUsed
            function deleteSelectedFiles(paramNotUsed) {
                var checkedValues = [];
                $('.markfilefordelete:checked').each(function(){
                    checkedValues.push($(this).val());
                });
                var url = '{{$app['url_generator']->generate('delete-selected-files')}}';
                $.ajax({
                    type: "POST",
                    url: url,
                    data: {selected : checkedValues},
                    success: function(response){
                        // remove all images from the page
                        $.each(checkedValues, function(key, value) {
                            removeImageUrl(value);
                            $("#allimages input[type='checkbox']").prop('checked', false);
                        });

                        // show flash messages
                        showAjaxFlashMessages();
                    }
                });
            }

            function removeImageUrl(image_data_id) {
                $("#image_url_"+image_data_id).attr('src', '');
                // also remove delete button
                $("#deletefile-"+image_data_id).remove();
                $("#markfilefordelete-"+image_data_id).parent().html($("#markfilefordelete-"+image_data_id).parent().children());
                $("#markfilefordelete-"+image_data_id).remove();
            }

            function bindClickEventToSelectAll()
            {
                $("#select-all-images").click(function() {
                    $("#allimages input[type='checkbox']").prop('checked', true);   // .attr does not work a second time
                    return false;
                });
            }

            function bindClickEventToUnselectAll()
            {
                $("#unselect-all-images").click(function() {
                    $("#allimages input[type='checkbox']").prop('checked', false);
                    return false;
                });
            }

            $("#select-page").select2().val("");
        });
    </script>
@endsection


@section('header-css')
    @parent
    {{ loadCssFile("/phive/admin/customization/styles/css/promotions.css") }}
@endsection
