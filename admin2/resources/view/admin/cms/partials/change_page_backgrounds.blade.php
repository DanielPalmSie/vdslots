@extends('admin.layout')

@section('header-css')
    @parent
    <link rel="stylesheet" type="text/css" href="/phive/admin/customization/styles/css/promotions.css">
@endsection

@section('content')
    <div class="container-fluid">
        @include('admin.cms.partials.topmenu')

        @include('admin.cms.partials.change_page_background_modal')

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Page Backgrounds</h3>
            </div>
            <div class="card-body">
                <p>On this page you can change the background of a page. <br>
                    The background image has to be uploaded in File Uploads (select subfolder file_uploads)
                </p>

                @include('admin.cms.partials.pages_list')

                <div id="filelist" style="display: none">
                    @include('admin.cms.partials.file_list')
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-javascript')
    @parent
    <script src="/phive/admin/plugins/select2/js/select2.min.js"></script>
    <script type="text/javascript">

        $('.change_page_background_link').on('click', function(e) {
            e.preventDefault();
            var self = $(this);
            $('#page_id').val(self.data('pageid'));
            $('#currenturl').attr("src",self.data('currenturl'));
            $('#change_page_background_modal_title').text(self.data('title'));
            $('#change_page_background_modal_label').text(self.data('label'));

            $("#new-background-preview").hide();
            $("#select-new-background").select2().val("");
            $('#change_page_background_submit_button').prop('disabled', true);
            bindClickEventToSelectNewBackground();
        });

        function bindClickEventToSelectNewBackground() {
            $('#select-new-background').on('change', function(e) {
                e.preventDefault();
                var self = $(this);
                $("#new-background-preview").show();
                $('#new-background-url').attr("src",self.val());
                $('#new-background-preview-link').attr("href",self.val());

                if(self.val() === '') {
                    $('#change_page_background_submit_button').prop('disabled', true);
                    $("#new-background-preview").hide();
                } else {
                    $('#change_page_background_submit_button').prop('disabled', false);
                    $('#new_background_filename').val(self.val());
                }

            });
        }

        $("#select-new-background").select2().val("");
    </script>
@endsection
