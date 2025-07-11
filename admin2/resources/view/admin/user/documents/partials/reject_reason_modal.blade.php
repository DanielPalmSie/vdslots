<div class="modal fade" id="reject_reason_modal" tabindex="-1" role="dialog"
     aria-labelledby="reject_reason_modal_label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">Close</span>
                </button>
                <h4 class="modal-title" id="reject_reason_modal_title"></h4>
            </div>
            <div class="modal-body">
                <form action=""
                    id="reject_reason_modal_form"
                    role="form"
                    method="post"
                    enctype="multipart/form-data"
                    >
                    <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
                    <input type="hidden" id="reject_reason_document_type" name="document_type">
                    <input type="hidden" id="reject_reason_document_id" name="document_id">
                    <input type="hidden" id="reject_reason_file_id" name="file_id">
                    <input type="hidden" id="reject_reason_element_id" name="element_id">
                    <input type="hidden" name="status" value="rejected">
                    <!--<p><span id="reject_reason_modal_label"></span></p>-->
                    <div class="form-group" id="reject_reason_modal_group" style="width: 436px">

                        <select name="reject_reason" id="select-reject-reason" class="form-control select2"
                            data-placeholder="Select reject reason" data-allow-clear="true">
                            <option></option>
                            @foreach($reject_reasons as $key => $reject_reason)
                                <option value="{{ $key }}">{{ $reject_reason }}</option>
                            @endforeach
                        </select>

                    </div>

                    <input id="submit_reject_reason" type="submit" value="Send email with reject reason" class="btn btn-primary" disabled="disabled"/>
                </form>
            </div>
        </div>
    </div>
</div>



