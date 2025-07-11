<div class="modal fade" id="single-modal" tabindex="-1" role="dialog"
     aria-labelledby="single-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="single-modal-title"></h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">Close</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="single-modal-form" role="form">
                    <input type="hidden" id="single-key" name="key">
                    <input type="hidden" id="single-type" name="type">
                    <input type="hidden" id="single-subtype" name="subtype">
                    <div class="form-group" id="single-modal-group">
                        <label id="single-modal-input-label" for="single-modal-input-field"></label>
                        <input type="text" name="time" class="form-control" id="single-modal-input-field" placeholder="Enter"/>
                        <span id="single-modal-input-field-helper"></span>
                    </div>
                </form>
                <span id="help-block-single-limits" class="help-block strong"></span>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default single-modal-close-btn" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary single-modal-save-btn">Save changes</button>
            </div>
        </div>
    </div>
</div>
