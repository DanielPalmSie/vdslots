@extends('admin.layout')

@section('header-css')
    @parent
    <link rel="stylesheet" type="text/css" href="/phive/admin/customization/styles/css/promotions.css">
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.cms.partials.topmenu')
        <?php $jurisdictions = lic('getBannerJurisdictions'); ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Image Uploads</h3>
            </div>
            <div class="card-body">
                <p>On this page you can upload files to the folder 'image_uploads'. </p>

                <p>Please select an image alias</p>

                <form action="">
                    <div class="form-group col-12 col-md-12 col-lg-8 col-xlg-6 col-fhd-4">
                        <select name="image_alias" id="select-alias" class="form-control select2"
                            data-placeholder="Type alias" data-allow-clear="true">
                            <option></option>
                        </select>
                    </div>
                </form>

                {{--
                    TODO: make an option to select a page by it's url, and show the image aliasses that are used on that page.
                    We need to get all pages that have a DynamicImageBox,
                    and get the alias for that box_id

                    SELECT p.page_id, p.alias, p.cached_path, b.box_id FROM pages p
                    JOIN boxes b ON p.page_id = b.page_id
                    WHERE b.box_class = 'FullImageBox'
                    ORDER BY p.cached_path

                --}}

                <div class="clear"></div>

                <p>OR, select a landing page to change the image(s) on that page</p>

                <form action="">
                    <div class="form-group col-12 col-md-12 col-lg-8 col-xlg-6 col-fhd-4">
                        <select name="select-landing-page" id="select-landing-page" class="form-control select2"
                            data-placeholder="Select landing page" data-allow-clear="true">
                            <option></option>
                        </select>
                    </div>
                </form>

                <div class="clear"></div>

                <div id="image_boxes"></div>

                <p>
                    Images need to be named on the following form: <strong>some-description_EUR_EN.ext
                    </strong>,
                    <strong>EN</strong> is the language{{!empty($jurisdictions) ? '(or the jurisdiction)' : ''}} and <strong>EUR</strong> is the currency, ext is one of
                    <strong>jpg, png or gif</strong>.
                </p>
                <p>If the language can't be determined it will default to <strong>any</strong>.
                If the currency can't be determined it will default to
                <strong><?php echo phive('Currencer')->getSetting('base_currency') ?></strong>.</p>
                <p>To for instance upload an English USD image that should show in all languages simply name it xxxx_USD_AN.jpg.
                Since <strong>an</strong> is not a recognized language it will default to <strong>any</strong>.</p>
                @if(!empty($jurisdictions))
                    <p><strong>Available jurisdictions:</strong> {{implode(', ', $jurisdictions)}}</p>
                @endif



                @include('admin.cms.partials.uploadfiles-dropzone')

                <div class="clear"></div>

                <div id="preview_images">

                </div>

            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script type="text/javascript">
        var dropzone = null;

        // Load aliasses by ajax request
        $("#select-alias").select2({
            ajax: {
                url: "{{$app['url_generator']->generate('get-image_aliasses')}}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search_string: params.term, // search term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            },
        });

        // Load landing pages by ajax request
        $("#select-landing-page").select2({
            ajax: {
                url: "{{$app['url_generator']->generate('get-landing-pages')}}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search_string: params.term, // search term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            },
        });

        $("#select-alias").change(function() {
            if($("#select-alias").val() == '') {
                $("#dropzone_container").hide();
                $("#preview_images").hide();
            } else {
                $("#dropzone_container").show();
                $("#preview_images").show();
                var image_id = $("#select-alias").val();
                previewImages(image_id);
                if(dropzone) {
                    dropzone.removeAllFiles();
                }
                $("#image_id").val(image_id);
            }
        });

        $("#select-landing-page").change(function() {
            if($("#select-landing-page").val() == '') {
                $("#dropzone_container").hide();
                $("#preview_images").hide();
                $("#image_boxes").hide();
                $("#select-alias").val('');
            } else {
                $("#image_boxes").show();
                var box_id      = $("#select-landing-page").val();
                var image_alias = 'fullimagebox.'+box_id;

                var page_id      = $("#select-landing-page").val();
                showImageBoxes(page_id);
            }
        });

        function showImageBoxes(page_id) {
            var image_boxes;
            $.get('{{$app['url_generator']->generate('show-image-boxes')}}'+'?page_id='+ page_id, function(response) {
                // Render a div with the image boxes, that contains all the aliases
                $('#image_boxes').html(response);
            });
        }

        function select2_search($el, term) {
            $el.select2('open');

            var $search = $el.data('select2').dropdown.$search;

            $search.val(term).trigger('input');
            setTimeout(function() { $('.select2-results__option').trigger("mouseup"); }, 500);
        }

        function selectAlias(alias) {

            select2_search($('#select-alias'), alias);
        }

        $(document).ready(function() {
            enableDropZone();
        });

        var get_alias = '{{$_GET['alias']}}';
        var get_image_id = '{{$_GET['image_id']}}';
        if(get_alias != '' && get_image_id != '') {
            var option = $('<option selected>'+get_alias+'</option>').val(get_image_id);
            $("#select-alias").append(option).trigger('change'); // append the option and update Select2
        }

        function bindClickEventToDeleteButtons() {
            $('.action-set-btn').on('click', function(e) {
                e.preventDefault();
                var dialogTitle    = $(this).data("dtitle");
                var dialogMessage  = $(this).data("dbody");
                var dialogUrl      = $(this).attr('href');
                var file_id        = $(this).data('dfileid');
                if($(this).data("disabled") != 1){
                    // show confirm button, and pass function removeSrc() with param file_id to remove the image scr after the image is deleted
                    showConfirmBtnAjax(dialogTitle, dialogMessage, dialogUrl, removeElement, file_id);
                }
            });
        }

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

        function deleteSelectedFiles(paramNotUsed) {
            var checkedValues = [];
            $('.markfilefordelete:checked').each(function(){
                checkedValues.push($(this).val())
            });
            var url = '{{$app['url_generator']->generate('delete-selected-files')}}';
            $.ajax({
                type: "POST",
                url: url,
                data: {selected : checkedValues},
                success: function(response){
                    // remove all elements from the page
                    $.each(checkedValues, function(key, value) {
                        removeElement(value);
                    });

                    // show flash messages
                    showAjaxFlashMessages();
                }
            });
        }

        function removeElement(image_data_id) {
            $("#image_url_"+image_data_id).parent().hide(1000);
        }

        // Renders the preview partial view with ajax
        function previewImages(image_id) {
            $.get('{{$app['url_generator']->generate('show-images')}}'+'?image_id='+ image_id, function(response) {
                $('#preview_images').html(response);
                // After the elements are on the page, we can bind the click event to the delete buttons
                bindClickEventToDeleteButtons();
                bindClickEventToDeleteSelectedFilesButton();
                bindClickEventToSelectAll();
                bindClickEventToUnselectAll();
            });
        }

        function showAllImages() {
            $("#first-banner").hide();
            $("#allimages").show();
        }

        Dropzone.autoDiscover = false;

        function enableDropZone() {
            if(!dropzone) {
                dropzone = new Dropzone('.dropzone', {
                    init: function() {
                        this.on("queuecomplete", function(file) {
                            var image_id = $("#select-alias").val();
                            previewImages(image_id);
                        });
                    },
                    parallelUploads: 1,
                    maxFilesize: 3,
                    filesizeBase: 1000,
                    headers : {
                        "X-CSRF-TOKEN" : document.querySelector('meta[name="csrf_token"]').content
                    }
                });
            }
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

    </script>
@endsection
