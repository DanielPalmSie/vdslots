<div class="modal fade" id="cancelModal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 id="cancelModalTitle"class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pendingIdForCancel">
                <button type="button" class="btn btn-success" onclick="cancelPending('delete', 'yes')" data-dismiss="modal">Cancel With Email</button>
                <button type="button" class="btn btn-warning" onclick="cancelPending('delete', 'no')" data-dismiss="modal">Cancel without Email</button>
            </div>
        </div>
    </div>
</div>

@section('footer-javascript')
    @parent
    <script>
        function cancelModal(currentElement, pID, user_id, email) {
            document.getElementById('pendingIdForCancel').value = pID;
            document.getElementById('cancelModalTitle').innerHTML = `<strong>Cancel</strong>: #${pID}`;

            let isCancelAction = true;

            $('#confirmationModal').off('hidden.bs.modal');

            $('#confirmationModal').on('hidden.bs.modal', () => {
                if (isCancelAction) {
                    $('#cancelModal').modal('show');
                    isCancelAction = false;
                }
            });
        }
    </script>
@endsection
