<div class="modal fade" id="change_page_background_modal"  role="dialog"
     aria-labelledby="change_page_background_modal_label" aria-hidden="true">  <!-- removed tabindex="-1" to make the select2 search working -->
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="change_page_background_modal_title"></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">Close</span>
                </button>
            </div>
            <div class="modal-body">

                <div class="background-preview-container">
                    Current background
                    <br>
                    <img src="" class="background-preview" id="currenturl">
                </div>

                <br><br>

                <form action="{{ $app['url_generator']->generate('change-page-background') }}"
                    id="change_page_background_modal_form"
                    role="form"
                    method="post"
                    enctype="multipart/form-data"
                    >
                    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">

                    <input type="hidden" id="page_id"                 name="page_id">
                    <input type="hidden" id="new_background_filename" name="new_background_filename">

                    <p>Select a new background</p>
                    <div class="form-group col-12 col-md-12 col-lg-12 col-xlg-12 col-fhd-8">
                        <select id="select-new-background" name="select-new-background" class="form-control select2-class"
                                style="width: 100%;" data-placeholder="Select new background" data-allow-clear="true">
                            <option></option>
                            @foreach($files as $file)
                                <option value="{{$file['url']}}">{{$file['name']}}</option>
                            @endforeach
                        </select>
                    </div>

                    <br>
                    <br>
                    <div id="new-background-preview" class="background-preview-container" style="display: none">
                        New background<br>
                        (Click to view full size)<br>
                        <a id="new-background-preview-link" href="" target="_blank">
                            <img src="" class="background-preview" id="new-background-url">
                        </a>
                    </div>

                    <div class="clear"></div>

                    <input id="change_page_background_submit_button" type="submit" value="Confirm" class="btn btn-primary" disabled="disabled"/>
                </form>
            </div>
        </div>
    </div>
</div>