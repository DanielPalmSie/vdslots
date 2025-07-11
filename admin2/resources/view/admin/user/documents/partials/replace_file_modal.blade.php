<div class="modal fade" id="replace_file_modal" tabindex="-1" role="dialog"
     aria-labelledby="replace_file_modal_label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="replace_file_modal_title"></h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">Close</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ $app['url_generator']->generate('admin.user-documents-replacefile',
                    [
                        'user' => $user->id,
                    ]) }}"
                    id="replace_file_modal_form"
                    role="form"
                    method="post"
                    enctype="multipart/form-data"
                    >
                    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                    <input type="hidden" id="document_type" name="document_type">
                    <input type="hidden" id="document_id"   name="document_id">
                    <input type="hidden" id="file_id"       name="file_id">
                    <input type="hidden" id="subtag"        name="subtag">

                    <p><span id="replace_file_modal_label"></span></p>
                    <div class="form-group" id="replace_file_modal_group">
                        <input type="file" id="" name="file">
                    </div>

                    <input id="replace_file_submit_button" type="submit" value="Upload and replace" class="btn btn-primary" disabled="disabled"/>
                </form>
            </div>
        </div>
    </div>
</div>

