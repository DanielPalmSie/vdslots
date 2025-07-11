<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title">Document Information settings</h3>
    </div>
    <!-- /.box-header -->
    <!-- form start -->
    <form id="document-information-form" class="form"
          action="{{ $app['url_generator']->generate('admin.userprofile-settings-update', ['user' => $user->id]) }}"
          method="post">
        <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
        <input type="hidden" name="form_id" id="form_id" value="settings">
        <div class="card-body">
            <div class="form-group">
                <label for="select-birth_country">Document Type</label>
                <select name="document_type" id="select-document-type"
                        class="form-control"
                        style="width: 100%;" data-placeholder="Select a Document type">
                    <option value="{{lic('getDocumentTypeList', [], $user->id)[$user->repo->getSetting('doc_type')]}}}">{{lic('getDocumentTypeList', [], $user->id)[$user->repo->getSetting('doc_type')]}}</option>
                     @foreach(lic('getDocumentTypeList', [], $user->id) as $doc_type)
                        <option value="{{ $doc_type }}">
                            ({{ $doc_type }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="document_number">Document Number</label>
                <textarea class="form-control" rows="3" name="document_number" placeholder="Document_numbe">{{ $user->repo->getSetting('doc_number') }}</textarea>
            </div>

            <div class="form-group">
                <label for="date_of_issue">Document Date of Issue</label>
                <textarea class="form-control" rows="3" name="date_of_issue" placeholder="Document_date">{{ \Carbon\Carbon::createFromDate($user->repo->getSetting('doc_year'), $user->repo->getSetting('doc_month'), $user->repo->getSetting('doc_date'))->format('Y m d') }}</textarea>
            </div>


            <div class="form-group">
                <label for="place_of_issue">Document Place of Issue</label>
                <textarea class="form-control" rows="3" name="place_of_issue" placeholder="Document_place">{{ $user->repo->getSetting('doc_issued_by') }}</textarea>
            </div>


        </div>
        <!-- /.box-body -->
        <div class="card-footer bg-white">
            @if(p('change.contact.info'))
                <button id="edit-setting-btn" value="settings" type="submit" class="btn btn-info float-right">Update Document settings</button>
            @endif
        </div>
        <!-- /.box-footer -->
    </form>
</div>


@section('footer-javascript')
    @parent
    <script type="text/javascript">
        $(function() {
            $(".select2-edit-segment").select2().select2('val', '{{ $settings->segment }}');
            $('#document-information-form').submit(function (e) {
                e.preventDefault();

                var dialogTitle = 'Edit Document information settings';
                var dialogMessage = 'Are you sure you want to edit the user Document settings?';
                var form = $("#settings-form");
                showConfirmInForm(dialogTitle, dialogMessage, form);
            });
        });
    </script>
@endsection
